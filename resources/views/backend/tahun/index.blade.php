@extends('backend.app')
@section('content')

    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Data Tahun</h3>
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
                            <thead  class="bg-info text-white">
                                <tr>
                                    <th width="5px">No</th>
                                    <th>Tahun</th>
                                    <th>Status</th>
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
                        <h5 class="modal-title m-2">Form Tahun</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id" id="id">

                        <div class="form-group">
                            <label>Tahun</label>
                            <input type="text" name="tahun" id="tahun" class="form-control form-control-sm"
                                required placeholder="tahun">
                        </div>

                        <div class="form-group">
                            <label>Tahun</label>
                            <select name="status" id="status" class="form-control" required>
                                <option>Aktif</option>
                                <option>Tidak Aktif</option>
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
    <script src="{{ asset('js/backend/tahun/index.js') }}"></script>
@endpush
