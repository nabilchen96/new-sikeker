@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Data Rencana Proker</h3>
                    <h4>Unit {{ $proker->unit }} Tahun {{ $proker->tahun }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mt-4">
            <div class="card w-100">
                <div class="card-body">

                    <button type="button" class="btn btn-primary btn-md mb-4 d-none d-md-inline-block" data-toggle="modal"
                        data-target="#modal">
                        Tambah
                    </button>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="searchInput" placeholder="Cari ...">
                        <div class="input-group-append">
                            <button style="height: 38px;" class="input-group-text" id="btnCari">
                                <i class="bi bi-search"></i> &nbsp; Cari
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="myTable" class="table table-striped" style="width: 100%;">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th width="5px">No</th>
                                    <th width="300px">Rencana Proker</th>
                                    <th>Waktu Pengerjaan</th>
                                    <th>Status Pengerjaan</th>
                                    <th>Created At</th>
                                    <th width="5px">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Form Rencana Proker</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="id_proker" id="id_proker" value="{{ Request('id_proker') }}">

                        <div class="form-group">
                            <label>Rencana Proker</label>
                            <textarea name="rencana_proker" id="rencana_proker" cols="10" rows="10" required class="form-control"
                                placeholder="Rencana Proker"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Bulan Mulai</label>
                            <select name="bulan_mulai" id="bulan_mulai" class="form-control" required>
                                <option value="">Pilih Bulan ...</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                                <option>6</option>
                                <option>7</option>
                                <option>8</option>
                                <option>9</option>
                                <option>10</option>
                                <option>11</option>
                                <option>12</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Minggu Mulai</label>
                            <select name="minggu_mulai" id="minggu_mulai" class="form-control" required>
                                <option value="">Pilih Minggu ...</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Bulan Akhir</label>
                            <select name="bulan_akhir" id="bulan_akhir" class="form-control" required>
                                <option value="">Pilih Bulan ...</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                                <option>6</option>
                                <option>7</option>
                                <option>8</option>
                                <option>9</option>
                                <option>10</option>
                                <option>11</option>
                                <option>12</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Minggu Akhir</label>
                            <select name="minggu_akhir" id="minggu_akhir" class="form-control" required>
                                <option value="">Pilih Minggu ...</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                            </select>
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
    <script src="{{ asset('js/backend/rencana_proker/index.js') }}"></script>
@endpush
