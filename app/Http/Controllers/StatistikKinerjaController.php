<?php

namespace App\Http\Controllers;

use App\Services\StatistikKinerjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StatistikKinerjaController extends Controller
{
    public function index(Request $request, StatistikKinerjaService $service)
    {
        $request->validate([
            'jenis' => ['nullable', Rule::in(['PK', 'KPI'])],
            'tahun' => ['nullable', 'integer', 'min:1'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
        ]);
        // Sesuaikan kolom 'tahun' jika di tabel tahuns bernama lain.
        $tahuns = DB::table('tahuns')->select('id', 'tahun')->orderByDesc('tahun')->get();
        $tahunAktif = DB::table('tahuns')->where('status', 'Aktif')->value('id');
        $tahunId = (int) ($request->input('tahun') ?: ($tahunAktif ?? $tahuns->first()->id ?? 0));
        $pilihanTahun = $tahuns->firstWhere('id', $tahunId);
        abort_unless($pilihanTahun && preg_match('/^\d{4}$/', (string) $pilihanTahun->tahun), 404, 'Tahun tidak ditemukan');
        $tahun = (int) $pilihanTahun->tahun;
        $bulan = (int) $request->input('bulan', min(12, $tahun === (int) date('Y') ? (int) date('n') : 12));
        $jenis = $request->input('jenis', 'PK');
        $data = $service->build($jenis, $tahunId, $tahun, $bulan);
        return view('backend.statistik.index2', compact('data', 'tahuns', 'tahunId', 'tahun', 'bulan', 'jenis'));
    }

    public function detail(Request $request, string $jenis, int $kontrak, StatistikKinerjaService $service)
    {
        abort_unless(in_array($jenis, ['pk', 'kpi'], true), 404);
        $request->validate(['tahun' => ['required','integer','min:1'], 'bulan' => ['nullable','integer','between:1,12']]);
        $tahunId = (int) $request->query('tahun');
        $row = DB::table('tahuns')->select('id', 'tahun')->where('id', $tahunId)->first();
        abort_unless($row && preg_match('/^\d{4}$/', (string) $row->tahun), 404);
        $tahun = (int) $row->tahun;
        $bulan = (int) $request->query('bulan', 12);
        $jenisUpper = strtoupper($jenis);
        $data = $service->build($jenisUpper, $tahunId, $tahun, $bulan);
        $item = $data['items']->firstWhere('id', $kontrak);
        abort_unless($item, 404, 'Indikator tidak ditemukan');
        $anak = $service->descendants($data['allItems'], $kontrak);
        return view('backend.statistik.detail2', compact('item','anak','jenisUpper','tahunId','tahun','bulan'));
    }
}
