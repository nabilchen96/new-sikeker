@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">
                        {{-- <a href="{{ url()->previous() }}">
                            <i class="bi bi-arrow-left-circle"></i>
                        </a> --}}
                        Data Detail Statistik
                    </h3>
                    <h4>Unit {{ $statistik->unit }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mt-4">
            <div class="card w-100">
                <div class="card-body">

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
                            <table id="myTable" class="table table-striped" style="width: 100%;">
                                <thead class="bg-info text-white">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="50%">Rencana Proker</th>
                                        <th>Jenis Proker</th>
                                        <th class="text-center">Waktu Pengerjaan</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $k => $item)
                                        <tr>
                                            <td>{{ $k + 1 }}</td>
                                            <td>{{ $item->rencana_proker }}</td>
                                            <td>
                                                {{ $item->jenis_proker }}
                                            </td>
                                            <td class="text-center">
                                                {{ $item->tgl_mulai }} → {{ $item->tgl_selesai }}
                                            </td>
                                            <td class="text-center">{{ $item->status_rencana ?? 'Belum' }}</td>
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
@endsection

@push('script')
    <script>
        $("#myTable").DataTable({})
    </script>
@endpush
