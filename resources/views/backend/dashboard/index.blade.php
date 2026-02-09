@extends('backend.app')

@section('content')

    <div class="row" style="margin-top: -200px;">
        <div class="col-md-12 text-white">
            <h3 class="font-weight-bold">Dashboard Rencana Proker</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mt-4">
            <div class="card">
                <div class="card-body">

                    {{-- FILTER --}}
                    <form method="GET" class="mb-3">
                        <div class="input-group mb-3">
                            @php
                                $unit = DB::table('units')->get();
                            @endphp

                            <select name="id_unit" class="form-control" required>
                                <option value="">Pilih Unit ...</option>
                                @foreach ($unit as $item)
                                    <option value="{{ $item->id }}"
                                        {{ request('id_unit') == $item->id ? 'selected' : '' }}>
                                        {{ $item->unit }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="bulan" class="form-control" required>
                                <option value="">Pilih Bulan</option>
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $i => $b)
                                    <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                                        {{ $b }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="input-group-append">
                                <button class="input-group-text" style="height:38px">
                                    <i class="bi bi-search"></i>&nbsp;Cari
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- TABEL --}}
                    <div class="table-responsive" style="height:500px;">
                        <table class="table table-bordered">
                            <thead class="table-secondary sticky-top">
                                <tr>
                                    <th class="p-3" style="min-width:300px">Rencana Proker</th>
                                    <th class="p-3">Jenis Proker</th>
                                    @for ($tgl = 1; $tgl <= $jumlahHari; $tgl++)
                                        <th class="text-center p-3">{{ $tgl }}</th>
                                    @endfor
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($prokers as $proker)
                                @php
                                    $cekAksi = DB::table('aksi_prokers')->where('id_rencana_proker', $proker->id )->first();
                                @endphp
                                    <tr>
                                        <td class="p-3">
                                            <a href="#" style="font-size: 14px;" data-toggle="modal"
                                                data-target="#modal" data-rencana-proker="{{ $proker->rencana_proker }}"
                                                data-waktu-pengerjaan="{{ date('d-m-Y', strtotime($proker->tgl_mulai)) }} → {{ date('d-m-Y', strtotime($proker->tgl_selesai)) }}"
                                                data-jenis-proker="{{ $proker->jenis_proker }}"
                                                data-id-rencana-proker="{{ $proker->id }}"
                                                data-status-rencana="{{ $proker->status_rencana ?? 'Belum' }}">
                                                {{ $proker->rencana_proker }}
                                            </a>
                                        </td>
                                        <td class="p-3">{{ $proker->jenis_proker }}</td>
                                        @for ($tgl = 1; $tgl <= $jumlahHari; $tgl++)
                                            @php
                                                /*
                                                 * Karena tidak pakai tahun,
                                                 * maka ambil tahun dari tgl_mulai
                                                 */
                                                $year = \Carbon\Carbon::parse($proker->tgl_mulai)->year;

                                                $current = \Carbon\Carbon::create($year, $bulan, $tgl);

                                                $active = $current->between(
                                                    \Carbon\Carbon::parse($proker->tgl_mulai),
                                                    \Carbon\Carbon::parse($proker->tgl_selesai),
                                                );

                                            @endphp

                                            <td class="text-center p-1 {{ $current->isWeekend() ? 'bg-danger' : '' }}">
                                                @if ($active)
                                                {{-- jika aksi proker sudah diupload dan status selesai --}}
                                                    @if ($cekAksi && $proker->status_rencana == 'Selesai')
                                                        <div class="bg-success"
                                                            style="
                                                                width:35px;
                                                                height:35px;
                                                                border-radius:8px;
                                                                line-height:35px;
                                                                text-align:center;
                                                                font-size:20px;
                                                            ">
                                                        🏅
                                                        </div>

                                                    @elseif ($cekAksi)
                                                    {{-- jika status belum selesai namun data aksi sudah diupload --}}
                                                    <div class="bg-info"
                                                            style="
                                                                width:35px;
                                                                height:35px;
                                                                border-radius:8px;
                                                                line-height:35px;
                                                                text-align:center;
                                                                font-size:20px;
                                                            ">
                                                        🏃‍➡️
                                                        </div>
                                                    @else
                                                    {{-- jika belum ada upload dan status belum selesai --}}
                                                        <div class="{{ $proker->status_rencana == 'Selesai' ? 'bg-success' : 'bg-warning' }}"
                                                            style="
                                                                width:35px;
                                                                height:35px;
                                                                border-radius:8px;
                                                                line-height:35px;
                                                                text-align:center;
                                                                font-size:20px;
                                                            ">
                                                            @if($proker->status_rencana == 'Selesai')
                                                                🏅
                                                            @endif
                                                        </div>
                                                    @endif

                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $jumlahHari + 1 }}" class="text-center">
                                            <i>Tidak ada data</i>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>

                </div>
                <div class="ml-4 mb-3">
                    <h5>Keterangan : </h5>
                    <table>
                        <tr>
                            <td>
                                <div class="bg-warning"
                                    style="
                                        width:35px;
                                        height:35px;
                                        border-radius:8px;
                                        line-height:35px;
                                        text-align:center;
                                        font-size:20px;
                                    ">

                                </div>
                            </td>
                            <td>:</td>
                            <td>Aksi Proker (data dukung) belum diupload dan kegiatan belum Selesai</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="bg-info"
                                    style="
                                        width:35px;
                                        height:35px;
                                        border-radius:8px;
                                        line-height:35px;
                                        text-align:center;
                                        font-size:20px;
                                    ">
                                🏃‍➡️
                                </div>
                            </td>
                            <td>:</td>
                            <td>Aksi Proker (data dukung) sudah diupload namun kegiatan belum Selesai</td>
                        </tr>

                        <tr>
                            <td>
                                <div class="bg-success"
                                                            style="
                                                                width:35px;
                                                                height:35px;
                                                                border-radius:8px;
                                                                line-height:35px;
                                                                text-align:center;
                                                                font-size:20px;
                                                            ">
                                                        🏅
                                                        </div>
                            </td>
                            <td>:</td>
                            <td>Aksi Proker (data dukung) sudah diupload dan kegiatan sudah Selesai</td>
                        </tr>
                    </table>



                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form">
                    <div class="modal-header p-3">
                        <h5 class="modal-title m-2" id="exampleModalLabel">Detail Rencana Proker</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">

                        <div class="form-group">
                            <label>Rencana Proker</label>
                            <textarea cols="10" id="rencana_proker" class="form-control" rows="5" readonly></textarea>
                        </div>
                        <div class="form-group">
                            <label>Waktu Pengerjaan</label>
                            <input type="text" id="waktu_pengerjaan" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Jenis Proker</label>
                            <input type="text" id="jenis_proker" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Status Rencana Proker</label>
                            <input type="text" id="status_rencana" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Detail Pekerjaan</label>
                            <br>
                            <a href="#" id="link-detail"><i class="bi bi-arrow-right"></i> Lihat Detail Pekerjaan
                                Proker ini</a>
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
    <script>
        $('#modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            var a = button.data('rencana-proker');
            var b = button.data('waktu-pengerjaan');
            var c = button.data('status-rencana');
            var d = button.data('jenis-proker');
            var e = button.data('id-rencana-proker');

            var modal = $(this);

            // Set textarea
            modal.find('#rencana_proker').val(a);

            // Set input waktu pengerjaan
            modal.find('input[readonly].form-control').val(b);

            // Set progress bar
            modal.find('#status_rencana').val(c);

            // Set input waktu pengerjaan
            modal.find('#jenis_proker').val(d);

            // Set input waktu pengerjaan
            modal.find('#link-detail')
                .attr('href', "{{ url('aksi-proker') }}?id_rencana_proker=" + e);

            // Set label persentase
            modal.find('.progress-bar')
                .parent().next('label')
                .html('Persentase Kegiatan ' + c + '%');
        });
    </script>
@endpush
