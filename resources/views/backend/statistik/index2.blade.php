@extends('backend.app')
@section('content')
    <div class="row" style="margin-top:-200px">
        <div class="col-md-12 text-white">
            <h3 class="font-weight-bold">Statistik Kinerja {{ $jenis }}</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mt-4">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        @foreach (['PK' => 'Perjanjian Kinerja (PK)', 'KPI' => 'Key Performance Indicator (KPI)'] as $key => $nama)
                            <li class="nav-item"><a class="nav-link {{ $jenis === $key ? 'active' : '' }}"
                                    href="{{ route('statistik.index2', ['jenis' => $key, 'tahun' => $tahunId, 'bulan' => $bulan]) }}">{{ $nama }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <form action="{{ route('statistik.index2') }}" method="GET" class="form-row mb-4 mt-5">
                        <input type="hidden" name="jenis" value="{{ $jenis }}">

                        <div class="input-group mb-3">
                            {{-- <input type="text" class="form-control" id="searchInput" placeholder="Cari ..."> --}}
                            <select name="tahun" class="form-control">
                                @foreach ($tahuns as $t)
                                    <option value="{{ $t->id }}" @selected($tahunId == $t->id)>{{ $t->tahun }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="bulan" class="form-control">
                                @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $index => $nama)
                                    <option value="{{ $index + 1 }}" @selected($bulan === $index + 1)>{{ $nama }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                {{-- <button id="btnCari">
                                    <i class="bi bi-search"></i> &nbsp; Cari
                                </button> --}}
                                <button style="height: 38px;" class="input-group-text"  
                                class="btn btn-primary btn-block" type="submit">Cari</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        @foreach ([['Jumlah IKK', $data['total'], 'bg-gradient-primary'], ['Tercapai', $data['tercapai'], 'bg-gradient-success'], ['Belum Tercapai', $data['belumTercapai'], 'bg-gradient-danger'], ['Rerata Capaian', $data['rata'] === null ? 'N/A' : number_format($data['rata'], 2, ',', '.') . '%', 'bg-gradient-info']] as $info)
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card {{ $info[2] }} text-white p-2">
                                    <div class="card-body">
                                        <h6>{{ $info[0] }}</h6>
                                        <i class="bi bi-bar-chart float-right"></i>
                                        <h2>{{ $info[1] }}</h2>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- <p class="text-muted small">Tahun {{ $tahun }} • Data kumulatif sampai bulan
                        ke-{{ $bulan }}. {{ $data['tanpaTarget'] }} indikator tanpa target positif tidak dihitung
                        dalam rata-rata capaian. Hanya IKK yang ditampilkan dan dihitung dalam grafik; indikator turunan
                        tersedia di halaman detail. Realisasi IKK dihitung dari total realisasi kumulatif indikator terbawah
                        dibagi total target indikator terbawah, sesuai perhitungan halaman Kontrak.</p> --}}
                    @if ($data['total'] === 0)
                        <div class="alert alert-info">Belum ada IKK {{ $jenis }} pada tahun ini. Pastikan kode IKK
                            diawali IKK, misalnya IKK 1.</div>
                    @else
                        <div class="row">
                            <div class="col-lg-12 mb-5 mt-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <div id="chartStatus" style="height:330px"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 mb-5">
                                <div class="card border">
                                    <div class="card-body">
                                        <div id="chartCapaian" style="height:{{ max(360, $data['total'] * 42 + 110) }}px">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-5">
                                <div class="card border">
                                    <div class="card-body">
                                        <div id="chartTren" style="height:410px"></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabelKinerja">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Kode / Indikator</th>
                                    <th>Kelompok</th>
                                    <th>Capaian</th>
                                    <th>Status</th>
                                    {{-- <th>Detail</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['items'] as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><b>{{ $item['kode'] }}</b><br>{{ $item['nama'] }}</td>
                                        <td>{{ $item['kelompok'] }}</td>
                                        {{-- <td>{{ $item['jenis_target'] }}</td>
                                        <td class="text-right">
                                            {{ $item['target'] === null ? '—' : number_format($item['target'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            {{ $item['realisasi'] === null ? '—' : number_format($item['realisasi'], 2, ',', '.') }}
                                        </td> --}}
                                        <td>
                                            @if ($item['capaian'] !== null)
                                                <div class="progress">
                                                    <div class="progress-bar bg-success"
                                                        style="width:{{ min(100, max(0, $item['capaian'])) }}%"></div>
                                                </div>{{ number_format($item['capaian'], 2, ',', '.') }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $item['status'] }}</td>
                                        {{-- <td><a class="btn btn-sm btn-outline-primary" href="{{ $item['url'] }}">Detail</a>
                                        </td> --}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <script>
        (function() {
            const items = @json($data['items']);
            const statuses = @json($data['statuses']);
            const kelompok = @json($data['kelompok']);
            const satuan = @json($data['satuan']);
            const selectedMonth = @json($bulan);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const num = v => Highcharts.numberFormat(v, 2, ',', '.');
            Highcharts.setOptions({
                lang: {
                    thousandsSep: '.',
                    decimalPoint: ',',
                    viewFullscreen: 'Layar penuh',
                    printChart: 'Cetak grafik',
                    downloadPNG: 'Unduh PNG',
                    downloadJPEG: 'Unduh JPEG',
                    downloadSVG: 'Unduh SVG',
                    downloadCSV: 'Unduh CSV',
                    downloadXLS: 'Unduh XLS'
                }
            });
            if (!items.length) return;
            Highcharts.chart('chartStatus', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Status IKK'
                },
                xAxis: {
                    categories: Object.keys(statuses)
                },
                yAxis: {
                    min: 0,
                    allowDecimals: false,
                    title: {
                        text: 'Jumlah indikator'
                    }
                },
                series: [{
                    name: 'Jumlah',
                    colorByPoint: true,
                    data: Object.values(statuses)
                }]
            });

            const capaian = items.filter(i => i.capaian !== null);
            Highcharts.chart('chartCapaian', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Capaian per IKK (%)'
                },
                xAxis: {
                    categories: capaian.map(i => (i.kode ? i.kode + ' — ' : '') + i.nama),
                    labels: {
                        style: {
                            fontSize: '11px'
                        }
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Persen (%)'
                    },
                    plotLines: [{
                        value: 100,
                        color: '#2e7d32',
                        width: 2,
                        dashStyle: 'Dash',
                        label: {
                            text: 'Target 100%'
                        }
                    }]
                },
                tooltip: {
                    pointFormat: 'Capaian: <b>{point.y:.2f}%</b>'
                },
                plotOptions: {
                    series: {
                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function() {
                                    if (this.options.custom?.url) window.location.href = this.options.custom
                                        .url;
                                }
                            }
                        }
                    }
                },
                series: [{
                    name: 'Capaian',
                    colorByPoint: true,
                    data: capaian.map(i => ({
                        y: i.capaian,
                        name: i.nama,
                        custom: {
                            url: i.url
                        }
                    }))
                }]
            });

            const top = [...capaian].sort((a, b) => b.capaian - a.capaian).slice(0, 10);

            const trend = months.map((m, idx) => {
                if (idx + 1 > selectedMonth) return null;
                const eligible = items.filter(i => i.target !== null && i.target > 0);
                if (!eligible.length) return null;
                return +(eligible.reduce((acc, i) => acc + ((i.bulanan[idx] ?? 0) / i.target * 100), 0) /
                    eligible.length).toFixed(2);
            });
            Highcharts.chart('chartTren', {
                chart: {
                    type: 'line'
                },
                title: {
                    text: 'Tren Rerata Capaian Kumulatif'
                },
                xAxis: {
                    categories: months
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Capaian (%)'
                    },
                    plotLines: [{
                        value: 100,
                        color: '#2e7d32',
                        width: 1,
                        dashStyle: 'Dash'
                    }]
                },
                tooltip: {
                    valueSuffix: '%'
                },
                series: [{
                    name: 'Rerata capaian',
                    data: trend
                }]
            });
        })();
    </script>
@endpush
