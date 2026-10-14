@extends('backend.app')


@section('content')
    <style>
        td {
            padding: 15px !important;
            font-size: 13px !important;
        }
    </style>
    <div class="row" style="margin-top: -200px;">

        <div class="col-md-12 text-white">

            <div class="row">

                <div class="col-12 col-xl-8 mb-xl-0">

                    <h3 class="font-weight-bold">
                        Data Cascading
                    </h3>

                </div>

            </div>

        </div>

    </div>




    <div class="card">


        <div class="card-body">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home" type="button"
                        role="tab" aria-controls="home" aria-selected="true">
                        Perjanjian Kinerja
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile" type="button"
                        role="tab" aria-controls="profile" aria-selected="false">
                        KPI
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modal">
                        Tambah
                    </button>

                    <table id="myTable" class="table table-bordered table-hover" style="width: 100%;">


                        <thead class="bg-info text-white text-center">


                            <tr>


                                <th>
                                    Indikator Kinerja
                                </th>


                                <th width="5px">
                                </th>


                                <th>
                                    Unit Organisasi
                                </th>


                                <th width="5%">
                                    Aksi
                                </th>


                            </tr>


                        </thead>



                        <tbody>



                            @foreach ($data as $item)
                                @forelse($item->cascadings as $index=>$cascading)
                                    <tr>


                                        @if ($index == 0)
                                            <td class="align-middle" rowspan="{{ $item->cascadings->count() }}">


                                                <b>
                                                    {{ $item->kode }}
                                                </b>


                                                <br>


                                                {{ $item->nama }}


                                            </td>
                                        @endif



                                        <td class="text-center">
                                            ✔
                                        </td>



                                        <td>

                                            {{ $cascading->user->unit->unit ?? $cascading->user->name }}

                                        </td>



                                        <td class="text-center">

                                            <i onclick="hapusData({{ $cascading->id }})" style="font-size:1.2rem"
                                                class="bi bi-trash text-danger" href="javascript:void(0)"
                                                class="bi bi-trash">
                                            </i>


                                        </td>



                                    </tr>



                                @empty



                                    <tr>


                                        <td>


                                            <b>
                                                {{ $item->kode }}
                                            </b>


                                            <br>


                                            {{ $item->nama }}



                                        </td>


                                        <td class="text-center">
                                            -
                                        </td>


                                        <td>

                                            Belum ada unit organisasi

                                        </td>


                                        <td class="text-center">


                                            {{-- <i onclick="tambahUnit({{ $item->id }})" style="font-size:1.2rem"
                                                class="text-success bi bi-plus-circle">

                                            </i> --}}

                                        </td>


                                    </tr>
                                @endforelse
                            @endforeach



                        </tbody>


                    </table>

                    <!-- MODAL -->


                    <div class="modal fade" id="modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form id="form">
                                    <div class="modal-header">
                                        <h5>
                                            Form Cascading
                                        </h5>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" id="id" name="id">
                                        <div id="respon_error" class="text-danger"> </div>
                                        <div class="form-group">
                                            <label>
                                                Indikator Kinerja
                                            </label>
                                            <select name="id_kontrak" id="id_kontrak" class="form-control" required>
                                                <option value="">
                                                    Pilih Indikator
                                                </option>
                                                @foreach ($kontraks as $k)
                                                    <option value="{{ $k->id }}"> {{ $k->kode }} -
                                                        {{ $k->nama }}

                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                User / Unit Organisasi
                                            </label>
                                            <select name="id_user" id="id_user" class="form-control" required>
                                                <option value="">
                                                    Pilih Unit
                                                </option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}">
                                                        {{ $u->unit ?? $u->name }} 
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-dismiss="modal">
                                            Close
                                        </button>
                                        <button id="tombol_kirim" class="btn btn-primary">
                                            Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ asset('js/backend/cascading/index.js') }}"></script>
@endpush
