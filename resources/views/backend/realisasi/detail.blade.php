@extends('backend.app')

@section('content')
    {{-- <style>
        td {
            padding: 4px !important;
            font-size: 13px !important;
        }
    </style> --}}

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="row" style="margin-top: -200px;">

        <div class="col-md-12 text-white">

            <div class="row">

                <div class="col-12 col-xl-8 mb-xl-0">

                    <h3 class="font-weight-bold">
                        Data Detail Kontrak
                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        CONTENT
    ========================================================== --}}

    <div class="row">

        <div class="col-12 mt-4">

            <div class="card w-100">

                <div class="card-body">

                    <a href="{{ url('realisasi') }}" class="btn btn-primary btn-md mb-4 d-none d-md-inline-block">
                        Kembali
                    </a>



                    <div class="table-responsive">
                        <table class="mb-4 table table-bordered table-striped table-hover" style="width: 100%;">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th>Indikator Kinerja</th>
                                    <th class="text-center">Target</th>
                                    <th class="text-center">Realisasi Terbaru</th>
                                    <th class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $indikator->nama }}</td>
                                    <td class="text-center">{{ $indikator->target }}</td>
                                    <td class="text-center">{{ @$realisasiTerbaru->nilai }}</td>
                                    <td class="text-center">{{ number_format($persentase, 2, ',', '.') }}%</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mb-4">
                            <div id="realisasiChart" style="width: 100%; height: 400px;"></div>
                        </div>

                        <hr class="mb-4">
                    </div>
                    <div class="table-responsive">
                        <table id="myTable" class="table table-bordered table-striped" style="width: 100%;">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Periode</th>
                                    <th width="5%">Tanggal Pengisian</th>
                                    <th width="5%">Realisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayat as $item)
                                    <tr>
                                        <td>{{ $item['bulan'] }}</td>
                                        <td>Bulan {{ $item['nama_bulan'] }}</td>
                                        <td>
                                            {{ $item['tanggal_pengisian'] ? $item['tanggal_pengisian']->format('d-m-Y') : '-' }}
                                        </td>
                                        <td>
                                            {{ $item['nilai'] !== null ? number_format($item['nilai'], 2, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </div>
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bulan = [
                'Januari', 'Februari', 'Maret', 'April',
                'Mei', 'Juni', 'Juli', 'Agustus',
                'September', 'Oktober', 'November', 'Desember'
            ];

            // Nilai realisasi per bulan dari Controller
            const dataRealisasi = @json(collect($riwayat)->mapWithKeys(function ($item) {
                    return [
                        (int) $item['bulan'] => $item['nilai'] !== null ? (float) $item['nilai'] : null,
                    ];
                }));

            // Susun data berurutan Januari sampai Desember
            const nilaiBulanan = bulan.map((nama, index) => {
                const nomorBulan = index + 1;

                return Object.prototype.hasOwnProperty.call(
                        dataRealisasi,
                        nomorBulan
                    ) ?
                    dataRealisasi[nomorBulan] :
                    null;
            });

            Highcharts.chart('realisasiChart', {
                chart: {
                    type: 'line',
                    backgroundColor: '#ffffff'
                },

                title: {
                    text: null
                },

                subtitle: {
                    text: 'Realisasi bulanan indikator kinerja'
                },

                xAxis: {
                    categories: bulan,
                    title: {
                        text: 'Periode'
                    },
                    tickmarkPlacement: 'on'
                },

                yAxis: {
                    title: {
                        text: 'Nilai Realisasi'
                    },
                    min: 0,
                    allowDecimals: true
                },

                tooltip: {
                    shared: true,
                    pointFormat: '<span style="color:{point.color}">●</span> ' +
                        '{series.name}: <b>{point.y:,.2f}</b><br/>'
                },

                plotOptions: {
                    line: {
                        connectNulls: false,
                        marker: {
                            enabled: true,
                            radius: 4
                        }
                    }
                },

                series: [{
                    name: 'Realisasi',
                    data: nilaiBulanan,
                    color: '#17a2b8',
                    lineWidth: 3
                }],

                credits: {
                    enabled: false
                },

                accessibility: {
                    enabled: false
                }
            });
        });
    </script>
@endsection
