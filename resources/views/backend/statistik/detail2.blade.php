@extends('backend.app')
@section('content')
    <div class="row" style="margin-top:-200px">
        <div class="col-md-12 text-white">
            <h3 class="font-weight-bold">Detail Indikator {{ $jenisUpper }}</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mt-4">
            <div class="card">
                <div class="card-body">
                    <a class="btn btn-outline-primary mb-3"
                        href="{{ route('statistik.index', ['jenis' => $jenisUpper, 'tahun' => $tahunId, 'bulan' => $bulan]) }}">←
                        Kembali ke Statistik</a>
                    <h4>{{ $item['kode'] }} — {{ $item['nama'] }}</h4>
                    <p class="text-muted">Kelompok: {{ $item['kelompok'] }} | Tahun {{ $tahun }} | Periode sampai
                        bulan {{ $bulan }}</p>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body"><small>Target</small>
                                    <h4>{{ $item['target'] === null ? '—' : number_format($item['target'], 2, ',', '.') }}</h4>
                                    <small>{{ $item['jenis_target'] }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body"><small>Realisasi Kumulatif</small>
                                    <h4>{{ number_format($item['realisasi'], 2, ',', '.') }}</h4>
                                    <small>{{ $item['jenis_target'] }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body"><small>Capaian</small>
                                    <h4>{{ $item['capaian'] === null ? '—' : number_format($item['capaian'], 2, ',', '.') . '%' }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body"><small>Status</small>
                                    <h4>{{ $item['status'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-7 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <div id="detailKumulatif" style="height:400px"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <div id="detailPerubahan" style="height:400px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small">Realisasi adalah kumulatif per bulan; perubahan bulanan dihitung dari
                        selisih dua posisi kumulatif berturut-turut. Jika suatu bulan tidak memiliki catatan, nilai terakhir
                        dibawa ke depan. Selisih negatif menunjukkan koreksi data.</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Realisasi kumulatif</th>
                                    <th>Perubahan bulan ini</th>
                                    <th>Capaian terhadap target</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $idx => $nama)
                                    @if ($idx < $bulan)
                                        <tr>
                                            <td>{{ $nama }}</td>
                                            <td>{{ number_format($item['bulanan'][$idx], 2, ',', '.') }}</td>
                                            <td>{{ number_format($item['kenaikan'][$idx], 2, ',', '.') }}</td>
                                            <td>{{ $item['target'] !== null && $item['target'] > 0 ? number_format((100 * $item['bulanan'][$idx]) / $item['target'], 2, ',', '.') . '%' : '—' }}
                                            </td>
                                        </tr>
                                    @endif
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
    <script>
        (function() {
            const item = @json($item);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const common = {
                xAxis: {
                    categories: months
                },
                yAxis: {
                    title: {
                        text: item.jenis_target
                    }
                },
                tooltip: {
                    valueDecimals: 2
                }
            };
            Highcharts.chart('detailKumulatif', {
                ...common,
                chart: {
                    type: 'line'
                },
                title: {
                    text: 'Realisasi Kumulatif vs Target'
                },
                series: [{
                    name: 'Realisasi',
                    data: item.bulanan
                }, {
                    name: 'Target',
                    dashStyle: 'Dash',
                    data: months.map((m, i) => item.bulanan[i] === null ? null : item.target)
                }]
            });
            Highcharts.chart('detailPerubahan', {
                ...common,
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Perubahan Realisasi Bulanan'
                },
                series: [{
                    name: 'Perubahan',
                    data: item.kenaikan
                }]
            });
        })();
    </script>
@endpush
