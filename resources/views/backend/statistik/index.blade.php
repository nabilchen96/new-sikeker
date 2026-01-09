@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Data Statistik</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mt-4">
            <div class="card w-100">
                <div class="card-body">
                    <form method="GET">
                        <div class="input-group mb-3 mt-3">
                            <select name="bulan" class="form-control">
                                <option value="">Semua Bulan</option>
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $i => $b)
                                    <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                                        {{ $b }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button style="height: 38px;" class="input-group-text" id="btnCari">
                                    <i class="bi bi-search"></i> &nbsp; Cari
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <div class="card bg-gradient-primary card-img-holder text-white">
                                <div class="card-body">
                                    <img src="https://themewagon.github.io/purple-react/static/media/circle.953c9ca0.svg"
                                        class="card-img-absolute" alt="circle">
                                    <h4 class="font-weight-normal mb-3">
                                        Total
                                        <i class="bi bi-person-circle float-right"></i>
                                    </h4>
                                    <h2>
                                        {{ $statistik->total_proker ?? 0 }}
                                    </h2>
                                    <span>Proker</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <div class="card bg-gradient-info card-img-holder text-white">
                                <div class="card-body">
                                    <img src="https://themewagon.github.io/purple-react/static/media/circle.953c9ca0.svg"
                                        class="card-img-absolute" alt="circle">
                                    <h4 class="font-weight-normal mb-3">
                                        Proker
                                        <i class="bi bi-person-circle float-right"></i>
                                    </h4>
                                    <h2>
                                        {{ $statistik->proker_selesai ?? 0 }}
                                    </h2>
                                    <span>Selesai</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <div class="card bg-gradient-danger card-img-holder text-white">
                                <div class="card-body">
                                    <img src="https://themewagon.github.io/purple-react/static/media/circle.953c9ca0.svg"
                                        class="card-img-absolute" alt="circle">
                                    <h4 class="font-weight-normal mb-3">
                                        Proker
                                        <i class="bi bi-person-circle float-right"></i>
                                    </h4>
                                    <h2>
                                        {{ $statistik->proker_belum ?? 0 }}
                                    </h2>
                                    <span>Belum Selesai</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home"
                                        type="button" role="tab" aria-controls="home" aria-selected="true">
                                        <i class="bi bi-bar-chart"></i> Grafik
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile"
                                        type="button" role="tab" aria-controls="profile" aria-selected="false">
                                        <i class="bi bi-table"></i> Data Tabel
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="home" role="tabpanel"
                                    aria-labelledby="home-tab">
                                    <div id="chart-proker"></div>
                                </div>
                                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <script>
        console.log(@json($categories));
        console.log(@json($dataSelesai));
        console.log(@json($dataBelum));

        Highcharts.chart('chart-proker', {
            chart: {
                type: 'bar'
            },
            title: {
                text: 'Progress Pengerjaan Proker Perunit'
            },
            xAxis: {
                categories: @json($categories),
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: {
                    text: 'Jumlah Proker',
                    align: 'high'
                }
            },
            tooltip: {
                shared: true
            },
            plotOptions: {
                bar: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            series: [{
                name: 'Selesai',
                data: @json($dataSelesai),
                color: '#28a745'
            }, {
                name: 'Belum',
                data: @json($dataBelum),
                color: '#dc3545'
            }]
        });
    </script>
@endpush
