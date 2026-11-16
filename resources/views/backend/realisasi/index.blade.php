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
                            <a href="{{ url('realisasi') }}?jenis=PK"
                                class="nav-link {{ Request('jenis') == 'PK' || Request('jenis') == null ? 'active' : '' }}">
                                Perjanjian Kinerja
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ url('realisasi') }}?jenis=KPI"
                                class="nav-link {{ Request('jenis') == 'KPI' ? 'active' : '' }}" type="button"
                                role="tab" aria-controls="profile" aria-selected="false">
                                KPI
                            </a>
                        </li>
                    </ul>

                    {{-- =================================================
                        SEARCH
                    ================================================== --}}


                    <div class="tab-content" id="myTabContent">
                        @if (Request('jenis') == 'PK' || request('jenis') == null)
                            <div
                                class="tab-pane fade show {{ Request('jenis') == 'PK' || Request('jenis') == null ? 'active' : '' }}">

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        {{ session('success') }}
                                        <button type="button" class="close" data-dismiss="alert">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <strong>Data gagal disimpan:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif


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
                                                <option value="{{ $nomor }}"
                                                    {{ (int) $bulan === $nomor ? 'selected' : '' }}>
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
                                <form method="POST" action="{{ url('/store-realisasi') }}">
                                    @csrf

                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">

                                    {{-- Letakkan seluruh tabel IKK dan indikator di dalam form ini. --}}
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
                                                                <small class="d-block">Target: {{ $item['total_target'] !== null ? number_format($item['total_target'], 2, ',', '.') : '—' }} {{ $item['unit'] }};
                                                                    Realisasi: {{ $item['total_realisasi_kumulatif'] !== null ? number_format($item['total_realisasi_kumulatif'], 2, ',', '.') : '—' }} {{ $item['unit'] }}</small>
                                                                @if ($item['calculation_error'])
                                                                    <small class="d-block">{{ $item['calculation_error'] }}</small>
                                                                @endif
                                                            </strong>
                                                        </th>

                                                        <th class="text-center align-middle">
                                                            {{ $item['persentase'] !== null ? number_format($item['persentase'], 2, ',', '.') . ' %' : '—' }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                            </table>

                                            {{-- Tabel biru: indikator yang memiliki target --}}
                                            <table class="table table-bordered table-hover" style="width: 100%;">

                                                <thead class="bg-info text-white">
                                                    <tr>
                                                        <th width="5%" style="vertical-align: middle;"
                                                            class="text-center">
                                                            No
                                                        </th>
                                                        <th width="35%" style="vertical-align: middle;"
                                                            class="text-center">
                                                            Indikator Kinerja
                                                        </th>
                                                        <th width="10%" class="text-center">
                                                            Target <br> Realisasi
                                                        </th class="text-center">
                                                        <th width="10%" class="text-center">
                                                            Realisasi <br> s.d Lalu
                                                        </th>
                                                        <th width="10%" class="text-center">
                                                            Realisasi <br> Bulan Ini
                                                        </th>
                                                        <th width="10%" class="text-center">
                                                            Dokumen <br> Dukung
                                                        </th>
                                                        <th width="1%" style="vertical-align: middle;"
                                                            class="text-center">
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
                                                                {{ $indikator->jenis_target }}
                                                            </td>

                                                            <td class="text-center">
                                                                {{ $indikator->realisasi_sebelumnya !== null
                                                                    ? number_format($indikator->realisasi_sebelumnya, 2, ',', '.')
                                                                    : '-' }}
                                                            </td>


                                                            <td class="text-center">
                                                                <input type="number"
                                                                @if(Auth::user()->role == 'Admin' || Auth::user()->role_kontrak == 'PK' || Auth::user()->role_kontrak == 'KPI')
                                    
                                                                    name="realisasi[{{ $indikator->id }}]"

                                                                    @endif
                                                                    class="form-control form-control-sm" min="0"
                                                                    step="0.01" style="text-align: center !important;"
                                                                    value="{{ $indikator->nilai_realisasi !== null ? $indikator->nilai_realisasi : '' }}"
                                                                    placeholder="Masukkan nilai">
                                                            </td>



                                                            <td class="text-center">


                                                                <a href="#" data-toggle="modal"
                                                                    data-target="#dokumenModal{{ $indikator->id }}">
                                                                    Lihat Data Dukung
                                                                </a>

                                                                <div class="modal fade"
                                                                    id="dokumenModal{{ $indikator->id }}" tabindex="-1"
                                                                    role="dialog" aria-hidden="true">

                                                                    <div class="modal-dialog modal-md" role="document">
                                                                        <div class="modal-content">

                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">
                                                                                    Dokumen Dukung
                                                                                </h5>

                                                                                <button type="button" class="close"
                                                                                    data-dismiss="modal">
                                                                                    <span>&times;</span>
                                                                                </button>
                                                                            </div>

                                                                            <div class="modal-body">
                                                                                @if ($indikator->aksiProkers->isNotEmpty())
                                                                                    <table class="table table-striped"
                                                                                        width="100%">
                                                                                        <thead class="bg-info text-white">
                                                                                            <tr>
                                                                                                <th
                                                                                                    style="vertical-align: middle;">
                                                                                                    Rencana Proker
                                                                                                </th>
                                                                                                <th width="10%">
                                                                                                    Tgl Pengerjaan
                                                                                                </th>
                                                                                                <th width="5%"></th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @foreach ($indikator->aksiProkers as $aksi)
                                                                                            
                                                                                                <tr>
                                                                                                    <td class="text-left p-3">
                                                                                                        {{ $aksi->kegiatan_proker }}
                                                                                                    </td>
                                                                                                    <td>
                                                                                                        {{ $aksi->tgl_pengerjaan ? \Carbon\Carbon::parse($aksi->tgl_pengerjaan)->format('d-m-Y') : '-' }}

                                                                                                    </td>
                                                                                                    <td width="15%">
                                                                                                        <a href="{{ asset('storage/' . $aksi->bukti_kegiatan) }}"
                                                                                                            target="_blank"
                                                                                                            rel="noopener noreferrer">
                                                                                                            <i class="bi bi-paperclip"></i>
                                                                                                            File
                                                                                                        </a>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                @else
                                                                                    <div class="alert alert-warning mb-0">
                                                                                        Belum ada aksi proker untuk
                                                                                        indikator
                                                                                        ini.
                                                                                        Tambahkan aksi proker terlebih
                                                                                        dahulu
                                                                                        pada menu Proker.
                                                                                    </div>
                                                                                @endif
                                                                            </div>

                                                                            <div class="modal-footer">
                                                                                <button type="button"
                                                                                    class="btn btn-info"
                                                                                    data-dismiss="modal">
                                                                                    Selesai
                                                                                </button>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>


                                                            <td class="text-center">
                                                                <a
                                                                    href="{{ route('realisasi.detail', [
                                                                        'id' => $indikator->id,
                                                                        'tahun' => $tahun ?? now()->year,
                                                                    ]) }}">
                                                                    <i style="font-size:1rem"
                                                                        class="text-info bi bi-ui-checks-grid"></i>
                                                                </a>
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



                                    {{-- Tombol simpan diletakkan setelah seluruh tabel. --}}
                                    <div class="text-right">
                                        @if(Auth::user()->role == 'Admin' || Auth::user()->role_kontrak == 'PK' || Auth::user()->role_kontrak == 'KPI')
                                    
                                        <button type="submit" class="btn btn-primary btn-md d-none d-md-inline-block">
                                            Simpan
                                        </button>

                                        @endif
                                    </div>
                                </form>

                            </div>
                        @elseif(Request('jenis') == 'KPI')
                            <div class="tab-pane fade show {{ Request('jenis') == 'KPI' ? 'active' : '' }}">

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        {{ session('success') }}
                                        <button type="button" class="close" data-dismiss="alert">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <strong>Data gagal disimpan:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif


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
                                                <option value="{{ $nomor }}"
                                                    {{ (int) $bulan === $nomor ? 'selected' : '' }}>
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
                                <form method="POST" action="{{ url('/store-realisasi') }}">
                                    @csrf

                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">

                                    {{-- Letakkan seluruh tabel IKK dan indikator di dalam form ini. --}}
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
                                                                <small class="d-block">Target: {{ $item['total_target'] !== null ? number_format($item['total_target'], 2, ',', '.') : '—' }} {{ $item['unit'] }};
                                                                    Realisasi: {{ $item['total_realisasi_kumulatif'] !== null ? number_format($item['total_realisasi_kumulatif'], 2, ',', '.') : '—' }} {{ $item['unit'] }}</small>
                                                                @if ($item['calculation_error'])
                                                                    <small class="d-block">{{ $item['calculation_error'] }}</small>
                                                                @endif
                                                            </strong>
                                                        </th>

                                                        <th class="text-center align-middle">
                                                            {{ $item['persentase'] !== null ? number_format($item['persentase'], 2, ',', '.') . ' %' : '—' }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                            </table>

                                            {{-- Tabel biru: indikator yang memiliki target --}}
                                            <table class="table table-bordered table-hover" style="width: 100%;">

                                                <thead class="bg-info text-white">
                                                    <tr>
                                                        <th width="5%" style="vertical-align: middle;"
                                                            class="text-center">
                                                            No
                                                        </th>
                                                        <th width="35%" style="vertical-align: middle;"
                                                            class="text-center">
                                                            Indikator Kinerja
                                                        </th>
                                                        <th width="10%" class="text-center">
                                                            Target <br> Realisasi
                                                        </th class="text-center">
                                                        <th width="10%" class="text-center">
                                                            Realisasi <br> s.d Lalu
                                                        </th>
                                                        <th width="10%" class="text-center">
                                                            Realisasi <br> Bulan Ini
                                                        </th>
                                                        {{-- <th width="20%" class="text-center">
                                                            Dokumen <br> Dukung
                                                        </th> --}}
                                                        <th width="1%" style="vertical-align: middle;"
                                                            class="text-center">
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
                                                                {{ $indikator->jenis_target }}
                                                            </td>

                                                            <td class="text-center">
                                                                {{ $indikator->realisasi_sebelumnya !== null
                                                                    ? number_format($indikator->realisasi_sebelumnya, 2, ',', '.')
                                                                    : '-' }}
                                                            </td>


                                                            <td class="text-center">
                                                                <input type="number"
                                                                    @if(Auth::user()->role == 'Admin' || Auth::user()->role_kontrak == 'KPI' || Auth::user()->role_kontrak == 'KPI')
                                                                    name="realisasi[{{ $indikator->id }}]"
                                                                    @endif
                                                                    class="form-control form-control-sm" min="0"
                                                                    step="0.01" style="text-align: center !important;"
                                                                    value="{{ $indikator->nilai_realisasi !== null ? $indikator->nilai_realisasi : '' }}"
                                                                    placeholder="Masukkan nilai" 
                                                                    >
                                                            </td>



                                                           

                                                            <td class="text-center">
                                                                <a
                                                                    href="{{ route('realisasi.detail', [
                                                                        'id' => $indikator->id,
                                                                        'tahun' => $tahun ?? now()->year,
                                                                    ]) }}">
                                                                    <i style="font-size:1rem"
                                                                        class="text-info bi bi-ui-checks-grid"></i>
                                                                </a>
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



                                    {{-- Tombol simpan diletakkan setelah seluruh tabel. --}}
                                    @if(Auth::user()->role == 'Admin' || Auth::user()->role_kontrak == 'KPI' || Auth::user()->role_kontrak == 'KPI')
                                    
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary btn-md d-none d-md-inline-block">
                                            Simpan
                                        </button>
                                    </div>

                                    @endif
                                </form>

                            </div>
                        @endif
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
