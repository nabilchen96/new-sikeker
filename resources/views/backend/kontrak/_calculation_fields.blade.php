<details id="quick_root_formula" class="mb-3">
    <summary id="quick_root_formula_label" class="text-primary">Cara menghitung induk</summary>
    <div class="pt-3">
        <label for="calculation_type">Cara menghitung</label>
        <select id="calculation_type" class="form-control mb-3">
            <option value="manual">Isi target langsung</option>
            <option value="sum">Jumlahkan nilai anak</option>
            <option value="ratio">Persentase dari dua anak</option>
            <option value="weighted_sum">Gabungan berbobot</option>
            <option value="scale">Bobot dari satu anak</option>
            <option value="offset_ratio">Nilai dasar + persentase</option>
        </select>
        <div id="quick_root_parameters"></div>
    </div>
</details>
<div id="quick_children"></div>
<button type="button" id="quick_add_child" class="btn btn-outline-primary btn-sm mb-3">+ Tambah rincian</button>
<div id="calculation_preview" class="alert alert-info" aria-live="polite"></div>
<small class="text-muted">Induk, rincian, bobot, dan rumus disimpan sekaligus. Satuan mengikuti isian masing-masing baris.</small>
