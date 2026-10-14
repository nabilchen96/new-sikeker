@extends('backend.app')

@section('content')
    <style>
        td {
            padding: 4px !important;
            font-size: 13px !important;
        }
    </style>

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="row" style="margin-top: -200px;">

        <div class="col-md-12 text-white">

            <div class="row">

                <div class="col-12 col-xl-8 mb-xl-0">

                    <h3 class="font-weight-bold">
                        Data Kontrak
                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        CONTENT
    ========================================================== --}}

    <div class="row">

        <div class="col-12 mt-4">

            <div class="card w-100">

                <div class="card-body">


                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home"
                                type="button" role="tab" aria-controls="home" aria-selected="true">
                                Perjanjian Kinerja
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile"
                                type="button" role="tab" aria-controls="profile" aria-selected="false">
                                KPI
                            </button>
                        </li>
                    </ul>

                    {{-- =================================================
                        SEARCH
                    ================================================== --}}

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                            <div class="table-responsive">

                                <button type="button" class="btn btn-primary btn-md mb-4 d-none d-md-inline-block"
                                    data-toggle="modal" data-target="#modalSk">
                                    Tambah
                                </button>

                                <table id="myTable" class="table table-bordered table-hover" style="width: 100%;">

                                    <thead class="bg-info text-white">

                                        <tr>

                                            <th width="5%" class="text-center align-middle">
                                                No
                                            </th>

                                            <th width="25%" class="text-center align-middle">
                                                Sasaran Kinerja
                                            </th>

                                            <th width="5%">

                                            </th>
                                            <th width="30%" class="text-center align-middle">
                                                Indikator Kinerja
                                            </th>
                                            <th width="5px" class="text-center align-middle">
                                                Target
                                            </th>
                                            <th width="5px" class="text-center align-middle">
                                                Realisasi
                                            </th>
                                            <th width="5px" class="text-center align-middle">
                                                Satuan
                                            </th>
                                            <th width="5px" class="text-center align-middle">
                                                Aksi
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        @forelse ($data as $index => $item)
                                            @foreach ($item['rows'] as $rowIndex => $row)
                                                @php

                                                    $kontrak = $row['model'];

                                                    $level = $row['level'];

                                                @endphp


                                                <tr>


                                                    {{-- =================================
                                                NO
                                            ================================== --}}

                                                    @if ($rowIndex === 0)
                                                        <td rowspan="{{ $item['rowspan'] }}"
                                                            class="text-center align-middle">

                                                            {{ $index + 1 }}

                                                        </td>


                                                        {{-- =============================
                                                    SASARAN KINERJA
                                                ============================== --}}

                                                        <td rowspan="{{ $item['rowspan'] }}" class="align-middle">

                                                            <div
                                                                style="
                                                            font-weight: 600;
                                                            line-height: 1.5;
                                                        ">

                                                                {{ $item['sasaran']->nama }}
                                                                <br>

                                                                <div class="dropdown">
                                                                    <a class="text-success" href="#"
                                                                        data-toggle="dropdown">
                                                                        <i class="bi bi-three-dots"
                                                                            style="font-size:1.5rem"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item text-primary"
                                                                            data-toggle="modal"
                                                                            data-target="#modalTambahBagian"
                                                                            href="javascript:void(0)" {{-- data-bs-id="{{ $kontrak->id }}" --}}
                                                                            data-bs-id_parent="{{ $kontrak->id_parent }}"
                                                                            data-bs-level="{{ $level }}"
                                                                            data-bs-indikator_induk="{{ $item['sasaran']->nama }}"
                                                                            data-bs-target="{{ $kontrak->target }}"
                                                                            data-bs-jenis_target="{{ $kontrak->jenis_kontrak }}">
                                                                            <i class="bi bi-plus-circle"></i> &nbsp; Add
                                                                            Sub
                                                                        </a>
                                                                        <a class="dropdown-item text-success"
                                                                            data-toggle="modal" data-target="#modalSk"
                                                                            href="javascript:void(0)"
                                                                            data-bs-id="{{ $item['sasaran']->id }}"
                                                                            data-bs-nama="{{ $item['sasaran']->nama }}">
                                                                            <i class="bi bi-grid"></i> &nbsp; Edit
                                                                        </a>
                                                                        @if ($kontrak->childrenRecursive->isEmpty())
                                                                            <a class="dropdown-item text-danger"
                                                                                onclick="hapusDataSasaranKinerja({{ $kontrak->id }})">
                                                                                <i class="bi bi-trash"></i> &nbsp; Hapus
                                                                            </a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </td>
                                                    @endif


                                                    {{-- =================================
                                                INDIKATOR
                                            ================================== --}}

                                                    <td class="text-center"
                                                        {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}>

                                                        @if ($kontrak->id_parent)
                                                            <strong>

                                                                {{ $kontrak->kode }}

                                                            </strong>
                                                        @endif

                                                    </td>
                                                    <td {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}>
                                                        @if ($kontrak->id_parent)
                                                            @if ($level === 0)
                                                                <strong>
                                                                    {{ $kontrak->nama }}
                                                                    {{-- <br>
                                                                    <a style="border-radius: 8px !important; font-size: 9px;" href="#"
                                                                        class="badge bg-info text-white">
                                                                        <i class="bi bi-pencil"></i> Edit
                                                                    </a>
                                                                    <a style="border-radius: 8px !important; font-size: 9px;" href="#"
                                                                        class="badge bg-info text-white">
                                                                        <i class="bi bi-plus-circle"></i> Tambah Bagian
                                                                    </a> --}}
                                                                </strong>
                                                            @else
                                                                @if ($level === 1)
                                                                    <strong>
                                                                @endif
                                                                {{ $kontrak->nama }}
                                                                </strong>
                                                            @endif
                                                        @endif
                                                    </td>

                                                    

                                                    {{-- TARGET --}}

                                                    <td {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}
                                                        class="text-center align-middle">
                                                        @if ($kontrak->id_parent)
                                                            @if ($row['total_target'] !== null)
                                                                <strong>

                                                                    {{ rtrim(rtrim(number_format($row['total_target'], 2, ',', '.'), '0'), ',') }}

                                                                    @if (strtoupper(trim($kontrak->kode ?? '')) && str_starts_with(strtoupper(trim($kontrak->kode ?? '')), 'IKK'))
                                                                        %
                                                                    @endif

                                                                </strong>
                                                            @else
                                                                <strong>
                                                                    0 {{ $level === 0 ?? "%"}}
                                                                </strong>
                                                            @endif
                                                        @endif
                                                    </td>

                                                    {{-- REALISASI --}}
                                                    <td {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}
                                                        class="text-center align-middle">
                                                        @if ($kontrak->id_parent)
                                                            @if ($row['total_realisasi'] !== null)
                                                                <strong>

                                                                    {{ rtrim(rtrim(number_format($row['total_realisasi'], 2, ',', '.'), '0'), ',') }}

                                                                    @if ($row['satuan_tampilan'] === 'persen')
                                                                        %
                                                                    @endif
                                                                </strong>
                                                            @else
                                                                <strong>
                                                                    0 {{ $level === 0 ? "%" : ""}}
                                                                </strong>
                                                            @endif
                                                        @endif

                                                    </td>

                                                    <td {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}
                                                        class="text-center align-middle">

                                                        @if ($row['satuan_tampilan'])
                                                            <strong>
                                                                {{ $row['satuan_tampilan'] }}
                                                            </strong>
                                                        @endif

                                                    </td>
                                                    <td {{ $level === 0 ? 'style=background:#FFF2CC; ' : '' }}
                                                        class="text-center">
                                                        @if ($kontrak->id_parent)
                                                            <div class="dropdown">
                                                                <a class="text-success" href="#"
                                                                    data-toggle="dropdown">
                                                                    <i class="bi bi-three-dots"
                                                                        style="font-size:1.5rem"></i>
                                                                </a>
                                                                <div class="dropdown-menu">
                                                                    <a class="dropdown-item text-primary"
                                                                        data-toggle="modal"
                                                                        data-target="#modalTambahBagian"
                                                                        href="javascript:void(0)" {{-- data-bs-id="{{ $kontrak->id }}" --}}
                                                                        data-bs-id_parent="{{ $kontrak->id }}"
                                                                        data-bs-level="{{ $level+1 }}"
                                                                        data-bs-indikator_induk="{{ $kontrak->nama }}"
                                                                        data-bs-target="{{ $kontrak->target }}"
                                                                        data-bs-jenis_target="{{ $kontrak->jenis_kontrak }}">
                                                                        <i class="bi bi-plus-circle"></i> &nbsp; Add Sub
                                                                    </a>
                                                                    @if($level === 0)
                                                                    {{-- <a class="dropdown-item text-info" data-toggle="modal"
                                                                        data-target="#modalTambahCasecading"
                                                                        href="javascript:void(0)"
                                                                        data-bs-id="{{ $kontrak->id }}">
                                                                        <i class="bi bi-plus-circle"></i> &nbsp; Casecading
                                                                    </a> --}}
                                                                    @endif
                                                                    <a class="dropdown-item text-success"
                                                                        data-toggle="modal" data-target="#modalTambahBagian"
                                                                        href="javascript:void(0)"
                                                                        data-bs-id="{{ $kontrak->id }}"
                                                                        data-bs-id_parent="{{ $kontrak->parent?->id }}"
                                                                        data-bs-indikator_induk="{{ $kontrak->parent?->nama }}"
                                                                        data-bs-sub_indikator="{{ $kontrak->nama }}"
                                                                        data-bs-target="{{ $kontrak->target }}"
                                                                        data-bs-jenis_target="{{ $kontrak->jenis_target }}"
                                                                        data-bs-level="{{ $level }}"
                                                                        data-bs-kode="{{ $kontrak->kode }}">
                                                                        <i class="bi bi-grid"></i> &nbsp; Edit
                                                                    </a>
                                                                    @if ($kontrak->childrenRecursive->isEmpty())
                                                                        <a class="dropdown-item text-danger"
                                                                            onclick="hapusDataBagian({{ $kontrak->id }})">
                                                                            <i class="bi bi-trash"></i> &nbsp; Hapus
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach

                                        @empty

                                            <tr>

                                                <td colspan="4" class="text-center py-5">

                                                    <i class="bi bi-inbox" style="font-size: 30px;">
                                                    </i>

                                                    <br>

                                                    Data kontrak belum tersedia.

                                                </td>

                                            </tr>
                                        @endforelse

                                    </tbody>

                                </table>

                            </div>
                        </div>
                        <div class="tab-pane fade show" id="profile" role="tabpanel" aria-labelledby="profile-tab">

                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Modal -->
    <div class="modal fade" id="modalSk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formSk">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Form Sasaran Kinerja</h5>
                    </div>

                    <div class="modal-body">
                        <div id="respon_error" class="text-danger mb-4"></div>
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="jenis" value="pk">

                        <div class="form-group">
                            <label>Sasaran Kinerja</label>
                            <textarea name="nama" id="nama" class="form-control" cols="30" rows="5"
                                placeholder="Sasaran Kinerja"></textarea>
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

    <div class="modal fade" id="modalTambahBagian" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formTambahBagian">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2">Form Sub Bagian</h5>
                    </div>

                    <div class="modal-body">
                        <div id="respon_error" class="text-danger mb-4"></div>
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="id_parent" id="id_parent">
                        <input type="hidden" name="jenis" value="pk">

                        <div class="form-group">
                            <label>Indikator Induk</label>
                            <textarea id="indikator_induk" class="form-control" cols="30" rows="5" placeholder="Sasaran Kinerja"
                                readonly></textarea>
                        </div>

                        <div class="form-group">
                            <label>Sub Indikator</label>
                            <textarea name="sub_indikator" id="sub_indikator" class="form-control" cols="30" rows="5"
                                placeholder="Sub Indikator"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Kode / Urutan</label>
                            <input type="text" class="form-control" name="kode" id="kode"
                                placeholder="Kode / Urutan">
                        </div>

                        <div class="form-group">
                            <label>Target</label>
                            <input type="text" class="form-control" name="target" id="target"
                                placeholder="Target">
                        </div>

                        <div class="form-group">
                            <label>Satuan</label>
                            <select name="jenis_target" id="jenis_target" class="form-control">
                                <option>orang</option>
                                <option>dokumen</option>
                                <option>file</option>
                                <option>nilai</option>
                                <option>rupiah</option>
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

    <script>
        // document.addEventListener('DOMContentLoaded', function() {

        //     const searchInput =
        //         document.getElementById('searchInput');

        //     const btnCari =
        //         document.getElementById('btnCari');

        //     const table =
        //         document.getElementById('myTable');

        //     const rows =
        //         table.querySelectorAll('tbody tr');


        //     function searchTable() {

        //         const keyword =
        //             searchInput.value.toLowerCase().trim();


        //         rows.forEach(function(row) {

        //             const text =
        //                 row.innerText.toLowerCase();


        //             if (text.includes(keyword)) {

        //                 row.style.display = '';

        //             } else {

        //                 row.style.display = 'none';

        //             }

        //         });

        //     }


        //     searchInput.addEventListener(
        //         'keyup',
        //         searchTable
        //     );


        //     btnCari.addEventListener(
        //         'click',
        //         searchTable
        //     );

        // });
    </script>
@endsection
@push('script')
    <script src="{{ asset('js/backend/kontrak/index.js') }}"></script>
@endpush
