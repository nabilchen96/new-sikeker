@extends('backend.app')
@section('content')
<div class="row" style="margin-top:-200px">
    <div class="col-12 text-white"><h3 class="font-weight-bold">Pengisian seperti Excel</h3></div>
</div>
<div class="row"><div class="col-12 mt-4"><div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between mb-3">
        <a class="btn btn-outline-secondary mb-2" href="{{ url('kontrak') }}?jenis={{ $jenis }}">Kembali ke Kontrak</a>
        <button type="button" class="btn btn-primary mb-2" id="sheet_save">Simpan tabel</button>
    </div>
    <div class="sheet-toolbar">
        <label>Indikator
            <select id="sheet_indicator" class="form-control">
                <option value="baru" {{ !$selected ? 'selected' : '' }}>+ Indikator baru</option>
                @foreach($indicators as $indicator)
                    <option value="{{ $indicator->id }}" {{ $selected?->id === $indicator->id ? 'selected' : '' }}>{{ $indicator->kode }} — {{ $indicator->nama }}</option>
                @endforeach
            </select>
        </label>
        <label>Sasaran kinerja
            <select id="sheet_parent" class="form-control">
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ $selected?->id_parent == $parent->id ? 'selected' : '' }}>{{ $parent->nama }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <p class="text-muted mb-2">Ubah angka atau rumus pada kolom <strong>E</strong>. Nomor sel mengikuti tabel ini. Contoh: <code>=E2/E3*100</code>, <code>=SUM(E2:E4)</code>, <code>=E2*40%+E3*40%+E4*20%</code>.</p>
    <div class="sheet-formula-bar mb-2"><span id="sheet_address">E1</span><span class="text-muted">fx</span><input id="sheet_formula" class="form-control" aria-label="Isi sel aktif" autocomplete="off"></div>
    <div id="sheet_errors" class="text-danger mb-2" role="alert"></div>
    <div class="sheet-scroll">
        <table class="sheet-grid" id="sheet_grid">
            <thead><tr><th>#</th><th>A<br>Kode</th><th>B<br>Indikator / rincian</th><th>C<br>Induk (baris)</th><th>D<br>Satuan</th><th>E<br>Target / rumus</th><th>F<br>Hasil</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap align-items-center mt-3">
        <button type="button" class="btn btn-outline-primary mr-3 mb-2" id="sheet_add">+ Tambah baris</button>
        <button type="button" class="btn btn-outline-secondary mr-3 mb-2" id="sheet_remove" disabled>Hapus baris baru terakhir</button>
        <span id="sheet_status" class="text-muted mb-2" aria-live="polite">Siap diisi.</span>
    </div>
    <small class="text-muted">Tab berpindah sel; Enter atau panah atas/bawah berpindah baris. Bisa menempel beberapa baris dari Excel. Angka biasa boleh memakai koma desimal; rumus memakai titik desimal atau persen. Referensi mengikuti nomor baris tabel ini. Sel kosong belum dihitung. Data tersimpan, termasuk induk dan sasaran kinerja, dapat dikoreksi lalu disimpan kembali.</small>
</div></div></div></div>
@endsection
@push('style')
<link rel="stylesheet" href="{{ asset('css/backend/kontrak/sheet.css') }}?v={{ filemtime(public_path('css/backend/kontrak/sheet.css')) }}">
@endpush
@push('script')
<script>window.kontrakSheet = @json(['rows'=>$sheetRows,'jenis'=>$jenis,'id'=>$selected?->id]);</script>
<script src="{{ asset('js/backend/kontrak/sheet.js') }}?v={{ filemtime(public_path('js/backend/kontrak/sheet.js')) }}"></script>
@endpush
