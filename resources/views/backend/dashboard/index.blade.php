@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Dashboard</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mt-4">
            @php
                // Mapping nama bulan ke angka
                function bulanIndex()
                {
                    return [
                        'Januari' => 1,
                        'Februari' => 2,
                        'Maret' => 3,
                        'April' => 4,
                        'Mei' => 5,
                        'Juni' => 6,
                        'Juli' => 7,
                        'Agustus' => 8,
                        'September' => 9,
                        'Oktober' => 10,
                        'November' => 11,
                        'Desember' => 12,
                    ];
                }

                // Fungsi menentukan apakah cell aktif
                function checkActive($proker, $bulanNama, $minggu)
                {
                    $bulan = bulanIndex()[$bulanNama]; // convert nama → angka

                    if ($bulan < intval($proker->bulan_mulai)) {
                        return false;
                    }

                    if ($bulan > intval($proker->bulan_akhir)) {
                        return false;
                    }

                    if ($bulan == intval($proker->bulan_mulai) && $minggu < intval($proker->minggu_mulai)) {
                        return false;
                    }

                    if ($bulan == intval($proker->bulan_akhir) && $minggu > intval($proker->minggu_akhir)) {
                        return false;
                    }

                    return true;
                }
            @endphp

            <div class="card">
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="input-group mb-3">
                            @php
                                $unit = DB::table('units')->get();
                                $tahun = DB::table('tahuns')->get();
                            @endphp
                            <select name="id_unit" id="id_unit" class="form-control" required>
                                <option value="">Pilih Unit ...</option>
                                @foreach ($unit as $item)
                                    <option {{ Request('id_unit') == $item->id ? 'selected' : '' }}
                                        value="{{ $item->id }}">{{ $item->unit }}</option>
                                @endforeach
                            </select>
                            <select name="id_tahun" id="id_tahun" class="form-control" required>
                                <option value="">Pilih Tahun ...</option>
                                @foreach ($tahun as $t)
                                    <option {{ Request('id_tahun') == $t->id ? 'selected' : '' }}
                                        value="{{ $t->id }}">{{ $t->tahun }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button style="height: 38px;" class="input-group-text" id="btnCari">
                                    <i class="bi bi-search"></i> &nbsp; Cari
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive" style="height: 500px;">
                        <table class="table table-bordered">
                            <thead class="table-secondary">
                                <tr>
                                    <th rowspan="2" class="p-3">Rencana Proker</th>

                                    {{-- Header Bulan --}}
                                    @foreach ($bulanList as $bulan)
                                        <th colspan="4" class="text-center p-2">{{ $bulan }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    {{-- Header Minggu --}}
                                    @foreach ($bulanList as $bulan)
                                        @foreach ($bulanMinggu[$bulan] as $mg)
                                            <th class="p-2 text-center">M{{ $mg }}</th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($prokers as $proker)
                                    <tr>
                                        <td class="text-start p-3"
                                            style="width: 350px !important; min-width: 350px !important; max-width: 350px !important;">
                                            <a href="#" style="font-size: 14px;" data-toggle="modal"
                                                data-target="#modal" data-rencana-proker="{{ $proker->rencana_proker }}"
                                                data-waktu-pengerjaan="Bulan {{ $proker->bulan_mulai }}, Minggu {{ $proker->minggu_mulai }} → Bulan {{ $proker->bulan_akhir }}, Minggu {{ $proker->minggu_akhir }}"
                                                data-total-progress="{{ $proker->total_progress }}">
                                                {{ $proker->rencana_proker }}
                                            </a>
                                        </td>

                                        {{-- Generate kotak minggu --}}
                                        @foreach ($bulanList as $bulan)
                                            @foreach ($bulanMinggu[$bulan] as $mg)
                                                @php
                                                    $active = checkActive($proker, $bulan, $mg);
                                                @endphp

                                                <td class="p-1">
                                                    @if ($active)
                                                        {!! $proker->total_progress == 100
                                                            ? '<div class="bg-success" style="border-radius: 8px; width: 35px; height: 35px;"></div>'
                                                            : '<div class="bg-warning" style="border-radius: 8px; width: 35px; height: 35px;"></div>' !!}
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach

                                    </tr>
                                @empty
                                    <td colspan="48" class="text-center"><i>Tidak Ada Data yang Ditampilkan</i></td>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2" id="exampleModalLabel">Detail Rencana Proker</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">

                        <div class="form-group">
                            <label>Rencana Proker</label>
                            <textarea cols="10" id="rencana_proker" class="form-control" rows="5" readonly></textarea>
                        </div>
                        <div class="form-group">
                            <label>Waktu Pengerjaan</label>
                            <input type="text" id="waktu_pengerjaan" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Status Progress</label>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar" role="progressbar"></div>
                            </div>
                            <label id="progress_label">Persentase Kegiatan</label>
                        </div>
                    </div>
                    <div class="modal-footer p-3">
                        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
                        <button id="tombol_kirim" class="btn btn-primary btn-sm">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        $('#modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            var a = button.data('rencana-proker');
            var b = button.data('waktu-pengerjaan');
            var c = button.data('total-progress');

            var modal = $(this);

            // Set textarea
            modal.find('#rencana_proker').val(a);

            // Set input waktu pengerjaan
            modal.find('input[readonly].form-control').val(b);

            // Set progress bar
            modal.find('.progress-bar')
                .css('width', c + '%')
                .attr('aria-valuenow', c);

            // Set label persentase
            modal.find('.progress-bar')
                .parent().next('label')
                .html('Persentase Kegiatan ' + c + '%');
        });
    </script>
@endpush
