@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12">
                    <h3 class="font-weight-bold">Data Rencana Proker</h3>
                    <h4>Unit {{ $proker->unit }} Tahun {{ $proker->tahun }}</h4>
                    <div style="background-color: #fff3cd;
                        border-color: #ffeeba;"
                        class="alert alert-warning p-2">
                        <strong>Status Proker!</strong>
                        Status proker anda saat ini adalah: {{ $proker->status_approval }}.
                        {{ $proker->keterangan_ditolak != '-' ? 'Keterangan Ditolak: ' . $proker->keterangan_ditolak : '' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-body">

                    
                    @if ($proker->status_approval == 'Belum Mengajukan' || 
                        $proker->status_approval == 'Ditolak' || 
                        $proker->status_approval == 'Direvisi')
                        <button type="button" class="btn btn-primary btn-md mb-4 d-none d-md-inline-block" data-toggle="modal"
                            data-target="#modal">
                            Tambah
                        </button>
                        <button type="button" onclick="ajukanProker({{ $proker->id }})"
                            class="btn btn-info btn-md mb-4 d-none d-md-inline-block">
                            Ajukan Proker
                        </button>
                    @endif

                    @if (Auth::user()->role == 'Approval')
                        <button type="button" data-toggle="modal" data-target="#modalUbahStatusProker"
                            class="btn btn-info btn-md mb-4 d-none d-md-inline-block">
                            Ubah Status Pengajuan
                        </button>
                    @endif

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
                                    <th>Jenis Proker</th>
                                    <th>Waktu Pengerjaan</th>
                                    <th>Status Pengerjaan</th>
                                    {{-- <th>Created At</th> --}}
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

                        {{-- <div class="form-group">
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
                        </div> --}}

                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input class="form-control" type="date" name="tgl_mulai" id="tgl_mulai" required placeholder="Tanggal Mulai">
                        </div>

                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input class="form-control" type="date" name="tgl_selesai" id="tgl_selesai" required placeholder="Tanggal Selesai">
                        </div>

                        <div class="form-group">
                            <label>Jenis Proker</label>
                            <select name="jenis_proker" id="jenis_proker" class="form-control" required>
                                <option value="">Pilih Jenis Proker ...</option>
                                <option>Utama</option>
                                <option>Tambahan</option>
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

    <div class="modal fade" id="modalUbahStatusProker" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formUbahStatusProker">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Ubah Status Pengajuan</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id_proker" id="id_proker" value="{{ Request('id_proker') }}">

                        <div class="form-group">
                            <label>Status Proker</label>
                            <select name="status_approval" id="status_approval" class="form-control" required>
                                <option value="">Pilih Status ...</option>
                                <option>Ditolak</option>
                                <option>Direvisi</option>
                                <option>Diterima</option>
                            </select>
                        </div>


                        <div class="form-group">
                            <label>Keterangan Ditolak</label>
                            <textarea name="keterangan_ditolak" id="keterangan_ditolak" cols="10" class="form-control" rows="10"
                                placeholder="Keterangan Hanya Jika Ditolak"></textarea>
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
