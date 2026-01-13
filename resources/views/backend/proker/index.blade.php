@extends('backend.app')
@section('content')
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Data Proker</h3>
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

                    <a data-toggle="modal" data-target="#modalExport" href="#" target="_blank">
                        <button class="btn btn-danger btn-md mb-4 d-none d-md-inline-block">
                            Export PDF
                        </button>
                    </a>
                    <div class="modal fade" id="modalExport" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ url('export-rencana-proker') }}" method="GET">
                                    <div class="modal-header p-3">
                                        <h5 class="modal-title m-2">Export Proker</h5>
                                    </div>

                                    <div class="modal-body">


                                        @php
                                            $unit = DB::table('units');

                                            if (Auth::user()->role == 'Admin') {
                                                $unit = $unit->get();
                                            } elseif (Auth::user()->role == 'Anggota') {
                                                $unit = $unit->where('id', Auth::user()->id_unit)->get();
                                            } elseif (Auth::user()->role == 'Approval') {
                                                // ambil semua unit yang dibawahi approval
                                                $unitIds = DB::table('approvals')
                                                    ->where('id_user', Auth::id())
                                                    ->pluck('id_unit');

                                                $unit = $unit->whereIn('id', $unitIds)->get();
                                            }
                                        @endphp

                                        <div class="form-group">
                                            <label>Unit</label>
                                            <select name="id_unit" id="id_unit" class="form-control" required>
                                                <option value="">Pilih Unit ....</option>
                                                @if (Auth::user()->role == 'Admin')
                                                    <option value="">Semua Unit ....</option>
                                                @endif
                                                @foreach ($unit as $i)
                                                    <option value="{{ $i->id }}">{{ $i->unit }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Bulan</label>
                                            <select name="id" id="id" class="form-control">
                                                <option value="">Pilih Bulan ....</option>
                                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $i => $b)
                                                    <option value="{{ $i }}"
                                                        {{ request('bulan') == $i ? 'selected' : '' }}>
                                                        {{ $b }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer p-3">
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-dismiss="modal">Close</button>
                                        <button id="tombol_kirim" class="btn btn-primary btn-sm">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

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
                                    <th>Proker</th>
                                    <th>Tahun</th>
                                    <th>Status Pengajuan</th>
                                    <th>Keterangan Ditolak</th>
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
                        <h5 class="modal-title m-2">Form Proker</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id" id="id">

                        @php
                            $user = DB::table('users')
                                ->where('id', Auth::user()->id)
                                ->first();
                            $role = $user->role;
                        @endphp
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="id_unit" id="id_unit" class="form-control" required>
                                <option value="">Pilih Unit ....</option>
                                @php
                                    if ($role != 'Anggota') {
                                        $unit = DB::table('units')->get();
                                    } elseif ($role == 'Anggota') {
                                        $unit = DB::table('units')->where('id', $user->id_unit)->get();
                                    }

                                @endphp
                                @foreach ($unit as $u)
                                    <option value="{{ $u->id }}">{{ $u->unit }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tahun</label>
                            <select name="id_tahun" id="id_tahun" class="form-control" required>
                                <option value="">Pilih Tahun ....</option>
                                @php
                                    $tahun = DB::table('tahuns')->get();
                                @endphp
                                @foreach ($tahun as $un)
                                    <option value="{{ $un->id }}">{{ $un->tahun }}</option>
                                @endforeach
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
    <script src="{{ asset('js/backend/proker/index.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
@endpush
