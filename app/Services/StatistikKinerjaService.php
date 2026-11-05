<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class StatistikKinerjaService
{
    /**
     * Menyamakan perhitungan dengan KontrakController:
     * - Sasaran adalah kontrak tanpa id_parent.
     * - IKK adalah anak sasaran yang kodenya dimulai dengan IKK.
     * - Target IKK 100%; realisasinya total realisasi leaf / total target leaf * 100.
     * - Nilai realisasi sumber adalah nilai kumulatif terakhir sampai bulan pilihan.
     */
    public function build(string $jenis, int $tahunId, int $tahun, int $bulan): array
    {
        $kontraks = DB::table('kontraks')
            ->where('jenis', $jenis)
            ->where('id_tahun', $tahunId)
            ->orderBy('id')->get();

        $byId = $kontraks->keyBy(fn ($row) => (string) $row->id);
        $children = $kontraks->groupBy(fn ($row) => (string) ($row->id_parent ?? 'root'));
        $sasaran = $kontraks->filter(fn ($row) => $row->id_parent === null || $row->id_parent === '')->values();
        $ikk = $kontraks->filter(function ($row) use ($byId) {
            return preg_match('/^IKK\b/iu', trim((string) $row->kode)) === 1
                && $row->id_parent !== null
                && $byId->has((string) $row->id_parent);
        })->values();

        $allRealisasi = DB::table('realisasis')
            ->whereIn('id_kontrak', $kontraks->pluck('id')->all())
            ->whereDate('periode', '<=', sprintf('%04d-%02d-%02d', $tahun, $bulan, cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun)))
            ->orderBy('periode')->orderBy('id')->get()
            ->groupBy(fn ($row) => (string) $row->id_kontrak);

        $lastValue = function ($id, int $month) use ($allRealisasi, $tahun) {
            $history = $allRealisasi->get((string) $id, collect());
            $latest = $history->filter(fn ($r) => substr((string) $r->periode, 0, 7) <= sprintf('%04d-%02d', $tahun, $month))
                ->last();
            return $latest ? (float) $latest->nilai : 0.0;
        };

        $sumLeaf = function ($id, int $month, array $seen = []) use (&$sumLeaf, $children, $byId, $lastValue) {
            $key = (string) $id;
            if (isset($seen[$key]) || !$byId->has($key)) return ['target' => 0.0, 'realisasi' => 0.0];
            $seen[$key] = true;
            $offspring = $children->get($key, collect());
            if ($offspring->isEmpty()) {
                $node = $byId->get($key);
                return ['target' => is_numeric($node->target) ? (float) $node->target : 0.0,
                        'realisasi' => $lastValue($id, $month)];
            }
            $result = ['target' => 0.0, 'realisasi' => 0.0];
            foreach ($offspring as $child) {
                $part = $sumLeaf($child->id, $month, $seen);
                $result['target'] += $part['target'];
                $result['realisasi'] += $part['realisasi'];
            }
            return $result;
        };

        $unitOfLeaf = function ($id, array $seen = []) use (&$unitOfLeaf, $children, $byId) {
            $key = (string) $id;
            if (isset($seen[$key]) || !$byId->has($key)) return null;
            $seen[$key] = true;
            $offspring = $children->get($key, collect());
            if ($offspring->isEmpty()) return $byId->get($key)->jenis_target;
            foreach ($offspring as $child) {
                $unit = $unitOfLeaf($child->id, $seen);
                if ($unit) return $unit;
            }
            return null;
        };

        $toItem = function ($node) use ($byId, $sumLeaf, $unitOfLeaf, $bulan, $tahunId, $jenis, $children) {
            $isIkk = preg_match('/^IKK\b/iu', trim((string) $node->kode)) === 1;
            $isParent = $children->get((string) $node->id, collect())->isNotEmpty();
            $parent = $node->id_parent === null ? null : $byId->get((string) $node->id_parent);
            $monthly = []; $changes = []; $previous = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                if ($m > $bulan) { $monthly[] = null; $changes[] = null; continue; }
                $total = $sumLeaf($node->id, $m);
                $value = $isIkk
                    ? ($total['target'] > 0 ? $total['realisasi'] / $total['target'] * 100 : null)
                    : $total['realisasi'];
                $monthly[] = $value === null ? null : round($value, 2);
                $changes[] = $value === null ? null : round($value - $previous, 2);
                if ($value !== null) $previous = $value;
            }
            $totals = $sumLeaf($node->id, $bulan);
            $target = $isIkk ? 100.0 : ($isParent ? $totals['target'] : (is_numeric($node->target) ? (float) $node->target : null));
            $value = $isIkk ? ($totals['target'] > 0 ? $totals['realisasi'] / $totals['target'] * 100 : null) : $totals['realisasi'];
            $percentage = $target !== null && $target > 0 && $value !== null ? round($value / $target * 100, 2) : null;
            $status = $percentage === null ? 'Tanpa target' : ($percentage >= 100 ? 'Tercapai' : ($percentage > 0 ? 'Berjalan' : 'Belum mulai'));
            return [
                'id' => (int) $node->id, 'parent_id' => $node->id_parent,
                'kode' => $node->kode, 'nama' => $node->nama,
                'kelompok' => $parent?->nama ?? 'Sasaran Kinerja',
                'jenis_target' => $isIkk ? 'persen' : ($isParent ? ($unitOfLeaf($node->id) ?: '—') : ($node->jenis_target ?: '—')),
                'target' => $target, 'realisasi' => $value, 'capaian' => $percentage,
                'status' => $status, 'bulanan' => $monthly, 'kenaikan' => $changes,
                'url' => route('statistik.detail2', [
                    'jenis' => strtolower($jenis), 'kontrak' => $node->id,
                    'tahun' => $tahunId, 'bulan' => $bulan,
                ]),
            ];
        };

        $items = $ikk->map($toItem)->values();
        $allItems = $kontraks->map($toItem)->values();
        $statuses = ['Tercapai' => 0, 'Berjalan' => 0, 'Belum mulai' => 0, 'Tanpa target' => 0];
        foreach ($items as $item) $statuses[$item['status']]++;
        $valid = $items->filter(fn ($item) => $item['capaian'] !== null);
        $rata = $valid->isEmpty() ? null : round($valid->avg('capaian'), 2);
        $kelompok = $items->groupBy('kelompok')->map(fn ($list, $name) => [
            'nama' => $name, 'jumlah' => $list->count(),
            'rata' => $list->filter(fn ($i) => $i['capaian'] !== null)->avg('capaian'),
        ])->values();
        $satuan = $items->groupBy('jenis_target')->map(fn ($list, $name) => [
            'nama' => $name, 'jumlah' => $list->count(),
        ])->values();
        return compact('items', 'allItems', 'statuses', 'kelompok', 'satuan', 'rata') + [
            'total' => $items->count(), 'tercapai' => $statuses['Tercapai'],
            'belumTercapai' => $statuses['Berjalan'] + $statuses['Belum mulai'],
            'tanpaTarget' => $statuses['Tanpa target'],
        ];
    }

    public function descendants(Collection $allItems, int $ikkId): Collection
    {
        $children = $allItems->groupBy(fn ($item) => (string) ($item['parent_id'] ?? 'root'));
        $result = collect();
        $visited = [(string) $ikkId => true];
        $walk = function ($parentId, $depth) use (&$walk, &$result, &$visited, $children) {
            foreach ($children->get((string) $parentId, collect()) as $child) {
                $key = (string) $child['id'];
                if (isset($visited[$key])) continue;
                $visited[$key] = true;
                $child['kedalaman'] = $depth;
                $result->push($child);
                $walk($key, $depth + 1);
            }
        };
        $walk($ikkId, 1);
        return $result;
    }
}
