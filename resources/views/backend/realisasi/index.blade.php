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
                             <form method="GET" action="{{ url('realisasi') }}">
                                <div class="input-group mb-3">
                                    {{-- <input type="text" class="form-control" id="searchInput" placeholder="Cari ..."> --}}
                                    <select name="bulan" id="bulan" class="form-control">

                                        @foreach ([
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ] as $nomor => $namaBulan)
                                            <option value="{{ $nomor }}" {{ (int) $bulan === $nomor ? 'selected' : '' }}>
                                                {{ $namaBulan }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button style="height: 38px;" class="input-group-text" type="submit">
                                            <i class="bi bi-search"></i> &nbsp; Cari
                                        </button>
                                    </div>
                                </div>
                             </form>

                            @forelse ($data as $item)
                                <div class="table-responsive mb-5">

                                    {{-- Tabel hijau: identitas IKK dan capaian --}}
                                    <table class="table table-bordered table-hover" style="width: 100%;">

                                        <thead class="bg-success text-white">
                                            <tr>
                                                <th>Indikator Kinerja</th>
                                                <th width="1%" class="text-center align-middle">
                                                    % Capaian
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>
                                                    <strong>
                                                        {{ $item['ikk']->kode }}
                                                        {{ $item['ikk']->nama }}
                                                    </strong>
                                                </th>

                                                <th class="text-center align-middle">
                                                    {{ number_format($item['persentase'], 2, ',', '.') }}
                                                    %
                                                </th>
                                            </tr>
                                        </thead>
                                    </table>

                                    {{-- Tabel biru: indikator yang memiliki target --}}
                                    <table class="table table-bordered table-hover" style="width: 100%;">

                                        <thead class="bg-info text-white">
                                            <tr>
                                                <th width="5%" class="text-center">
                                                    No
                                                </th>
                                                <th width="35%" class="text-center">
                                                    Indikator Kinerja
                                                </th>
                                                <th width="10%" class="text-center">
                                                    Target
                                                </th>
                                                <th width="10%" class="text-center">
                                                    Realisasi
                                                </th>
                                                <th width="20%" class="text-center">
                                                    Data Dukung
                                                </th>
                                                <th width="1%" class="text-center">
                                                    Detail
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($item['indikators'] as $index => $indikator)
                                                @php
                                                    $nilaiRealisasi = $indikator->realisasis->sum('nilai');
                                                @endphp

                                                <tr>
                                                    <td class="text-center">
                                                        {{ $index + 1 }}
                                                    </td>

                                                    <td>
                                                        {{-- {{ $indikator->kode }} --}}
                                                        {{ $indikator->nama }}
                                                    </td>

                                                    <td class="text-center">
                                                        {{ number_format($indikator->target, 0, ',', '.') }}
                                                        {{-- {{ $indikator->jenis_target }} --}}
                                                    </td>

                                                    <td class="text-center">
                                                        {{-- {{ number_format($nilaiRealisasi, 2, ',', '.') }} --}}
                                                        {{ number_format($indikator->nilai_realisasi, 2, ',', '.') }}
                                                    </td>

                                                    <td class="text-center">
                                                        {{-- Tambahkan tombol/link data dukung --}}
                                                        -
                                                    </td>

                                                    <td class="text-center">
                                                        {{-- Tambahkan tombol aksi sesuai route aplikasi --}}
                                                        <i style="font-size:1rem" class="text-info bi bi-hourglass-split"></i>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        Belum ada indikator dengan target
                                                        pada IKK ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>


                                    </table>
                                </div>
                            @empty
                                <div class="alert alert-info">
                                    Belum ada data IKK yang tersedia.
                                </div>
                            @endforelse

                        </div>
                    </div>


                </div>

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
