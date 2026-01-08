@extends('backend.app')
@section('content')
    <style>
        td {
            vertical-align: top !important;
        }

        .ts-control {
            min-height: 38px;
        }
    </style>
    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <div class="row">
                <div class="col-12 col-xl-8 mb-xl-0">
                    <h3 class="font-weight-bold">Data Aksi Proker</h3>
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
                    <form action="">
                        <div class="d-flex gap-2 align-items-start">
                            @php
                                $rencana = DB::table('rencana_prokers')
                                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                                    ->select('rencana_prokers.*', 'units.unit')
                                    ->where('tahuns.status', 'Aktif')
                                    ->where('prokers.status_approval', 'Diterima')
                                    ->get();

                                $unit = DB::table('units');
                                if (Auth::user()->role == 'Admin') {
                                    $unit = $unit->get();
                                } else {
                                    $unit = $unit->where('id', Auth::user()->id_unit)->get();
                                }
                            @endphp
                            <div class="flex-grow-1">
                                <select name="id_rencana_proker" id="id_rencana_proker">
                                    <option value="">Pilih Proker Unit ...</option>
                                    @foreach ($unit as $item)
                                        <option value="{{ $item->id }}">
                                            {{ $item->unit }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button style="height: 38px;" class="input-group-text" id="btnCari">
                                <i class="bi bi-search"></i> &nbsp; Cari
                            </button>

                        </div>
                        {{-- <div class="input-group mb-3">
                        @php
                        $rencana = DB::table('rencana_prokers')
                        ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                        ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                        ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                        ->select('rencana_prokers.*', 'units.unit')
                        ->where('tahuns.status', 'Aktif')
                        ->where('prokers.status_approval', 'Diterima')
                        ->get();
                        @endphp
                        <select name="id_rencana_proker" id="id_rencana_proker" style="width: 100% !important">
                            <option value="">Pilih Proker Unit ...</option>
                            @foreach ($rencana as $item)
                            <option {{ Request('id_rencana_proker')==$item->id ? 'selected' : '' }}
                                value="{{ $item->id }}">{{ $item->rencana_proker }}
                                [ Unit:
                                {{ $item->unit }}
                                ]</option>
                            @endforeach
                        </select>
                        <div class="input-group-append">
                            <button style="height: 38px;" class="input-group-text" id="btnCari">
                                <i class="bi bi-search"></i> &nbsp; Cari
                            </button>
                        </div>
                    </div> --}}
                    </form>

                    <div class="table-responsive">
                        <table id="myTable" class="table table-striped" style="width: 100%;">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th width="5px">No</th>
                                    <th width="350px">Rencana / Kegiatan</th>
                                    <th width="200px">Waktu Pengerjaan / Unit</th>
                                    <th width="200px">File Kegiatan / Created At</th>
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
                <form id="form" enctype="multipart/form-data">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Form Rencana Proker</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="id_proker" id="id_proker" value="{{ Request('id_proker') }}">

                        @php

                            $user = DB::table('users')
                                ->where('id', Auth::user()->id)
                                ->first();
                            $role = $user->role;

                            if ($role != 'Anggota') {
                                $rencana2 = DB::table('rencana_prokers')
                                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                                    ->select('rencana_prokers.*', 'units.unit')
                                    ->where('tahuns.status', 'Aktif')
                                    ->where('prokers.status_approval', 'Diterima')
                                    ->get();
                            } else {
                                $rencana2 = DB::table('rencana_prokers')
                                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                                    ->select('rencana_prokers.*', 'units.unit')
                                    ->where('tahuns.status', 'Aktif')
                                    ->where('prokers.status_approval', 'Diterima')
                                    ->where('prokers.id_unit', $user->id_unit)
                                    ->get();
                            }
                        @endphp

                        <div class="form-group">
                            <label>Rencana Proker</label>
                            <select name="id_rencana_proker" id="id_rencana_proker2">
                                <option value="">Pilih Proker Unit ...</option>
                                @foreach ($rencana2 as $item)
                                    <option value="{{ $item->id }}">{{ $item->rencana_proker }} [ Unit:
                                        {{ $item->unit }} ]</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kegiatan Proker</label>
                            <textarea name="kegiatan_proker" id="kegiatan_proker" cols="10" rows="10" required class="form-control"
                                placeholder="Jelaskan Kegiatan Proker"></textarea>
                        </div>

                        <div class="form-group">
                            <label>File Kegiatan</label>
                            <input type="file" class="form-control" name="bukti_kegiatan" id="bukti_kegiatan">
                            {{-- <div id="previewFile" class="mt-2"></div> --}}
                            <span class="text-info" style="font-size: 13px;">
                                *Hanya file pdf, word, excel, image, maksimal: 2MB
                            </span>
                        </div>

                        {{-- <div class="form-group">
                        <label>Penambahan Progress (%)</label>
                        <input placeholder="Isi dengan angka" type="number" class="form-control"
                            placeholder="Penambahan Progress" required name="progress" id="progress">
                        <span class="text-info" style="font-size: 13px;">
                            *Setiap progress dari rencana proker yang sama akan dijumlahkan
                        </span>
                    </div> --}}
                        <div class="form-group">
                            <label>Tgl Pengerjaan</label>
                            <input type="date" name="tgl_pengerjaan" id="tgl_pengerjaan" required class="form-control">
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

    <!-- Modal  Edit-->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEdit" enctype="multipart/form-data">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Form Rencana Proker</h5>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="id_proker" id="id_proker" value="{{ Request('id_proker') }}">

                        @php

                            $user = DB::table('users')
                                ->where('id', Auth::user()->id)
                                ->first();
                            $role = $user->role;

                            if ($role != 'Anggota') {
                                $rencana2 = DB::table('rencana_prokers')
                                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                                    ->select('rencana_prokers.*', 'units.unit')
                                    ->where('tahuns.status', 'Aktif')
                                    ->where('prokers.status_approval', 'Diterima')
                                    ->get();
                            } else {
                                $rencana2 = DB::table('rencana_prokers')
                                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                                    ->select('rencana_prokers.*', 'units.unit')
                                    ->where('tahuns.status', 'Aktif')
                                    ->where('prokers.status_approval', 'Diterima')
                                    ->where('prokers.id_unit', $user->id_unit)
                                    ->get();
                            }
                        @endphp

                        <div class="form-group">
                            <label>Rencana Proker</label>
                            <select name="id_rencana_proker" id="id_rencana_proker3">
                                <option value="">Pilih Proker Unit ...</option>
                                @foreach ($rencana2 as $item)
                                    <option value="{{ $item->id }}">{{ $item->rencana_proker }} [ Unit:
                                        {{ $item->unit }} ]</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kegiatan Proker</label>
                            <textarea name="kegiatan_proker" id="kegiatan_proker" cols="10" rows="10" required class="form-control"
                                placeholder="Jelaskan Kegiatan Proker"></textarea>
                        </div>

                        <div class="form-group">
                            <label>File Kegiatan</label>
                            <input type="file" class="form-control" name="bukti_kegiatan" id="bukti_kegiatan">
                            {{-- <div id="previewFile" class="mt-2"></div> --}}
                            <span class="text-info" style="font-size: 13px;">
                                *Hanya file pdf, word, excel, image, maksimal: 2MB
                            </span>
                        </div>

                        {{-- <div class="form-group">
                        <label>Penambahan Progress (%)</label>
                        <input placeholder="Isi dengan angka" type="number" class="form-control"
                            placeholder="Penambahan Progress" required name="progress" id="progress">
                        <span class="text-info" style="font-size: 13px;">
                            *Setiap progress dari rencana proker yang sama akan dijumlahkan
                        </span>
                    </div> --}}
                        <div class="form-group">
                            <label>Tgl Pengerjaan</label>
                            <input type="date" name="tgl_pengerjaan" id="tgl_pengerjaan" required
                                class="form-control">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#id_rencana_proker', {
            placeholder: 'Pilih Proker Unit ...',
            maxItems: 1,
            closeAfterSelect: true
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#id_rencana_proker2', {
            placeholder: 'Pilih Proker Unit ...',
            maxItems: 1,
            closeAfterSelect: true
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        id_rencana_proker3 = new TomSelect('#id_rencana_proker3', {
            placeholder: 'Pilih Proker Unit ...',
            maxItems: 1,
            closeAfterSelect: true
        });
    });
</script>
@push('script')
    <script src="{{ asset('js/backend/aksi_proker/index.js') }}"></script>
@endpush
