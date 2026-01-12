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
                                        <i class="bi bi-bar-chart float-right"></i>
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
                                        <i class="bi bi-bar-chart float-right"></i>
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
                                        <i class="bi bi-bar-chart float-right"></i>
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
                                    <div id="chartProker" style="height: 1200px;"></div>
                                </div>
                                <div class="tab-pane fade show" id="profile" role="tabpanel"
                                    aria-labelledby="profile-tab">
                                    <table id="myTable" class="table table-striped" style="width: 100%;">
                                        <thead class="bg-info text-white">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="50%">Unit</th>
                                                <th width="25%" class="text-center">Progress</th>
                                                <th class="text-center">Total Proker</th>
                                                <th class="text-center">Selesai</th>
                                                <th class="text-center">Belum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($chart as $k => $item)
                                                <tr>
                                                    <td>{{ $k + 1 }}</td>
                                                    <td>{{ $item->unit }}</td>
                                                    <td>
                                                        <div class="progress mb-2"
                                                            style="border: 1px solid rgb(193, 188, 188); height: 20px !important;">
                                                            <div class="progress-bar" role="progressbar"
                                                                style="width:{{ $item->selesai + $item->belum > 0
                                                                    ? number_format(($item->selesai / ($item->selesai + $item->belum)) * 100, 0)
                                                                    : 0 }}%;"
                                                                aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <b>Persentase:
                                                            {{ $item->selesai + $item->belum > 0
                                                                ? number_format(($item->selesai / ($item->selesai + $item->belum)) * 100, 0)
                                                                : 0 }}%
                                                        </b>
                                                    </td>
                                                    <td class="text-center"><b>{{ $item->selesai + $item->belum }}</b>
                                                    </td>
                                                    <td class="text-center"><b>{{ $item->selesai }}</b></td>
                                                    <td class="text-center"><b>{{ $item->belum }}</b></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
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
        Highcharts.chart('chartProker', {
            chart: {
                type: 'bar'
            },
            title: {
                text: 'Progress Rencana Proker per Unit'
            },
            xAxis: {
                categories: {!! json_encode($units) !!}
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Jumlah Rencana'
                },
                stackLabels: {
                    enabled: true
                }
            },
            legend: {
                reversed: true
            },
            plotOptions: {
                series: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            series: [{
                    name: 'Belum Selesai',
                    data: {!! json_encode($belum) !!}
                },
                {
                    name: 'Selesai',
                    data: {!! json_encode($selesai) !!}
                }
            ]
        });
    </script>
@endpush
