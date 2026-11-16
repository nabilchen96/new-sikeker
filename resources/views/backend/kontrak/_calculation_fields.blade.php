<div class="form-group">
    <label for="calculation_type">Cara menghitung target dan realisasi</label>
    <select name="calculation_type" id="calculation_type" class="form-control">
        <option value="">Profil Excel 2026 (data lama)</option>
        <option value="manual">Isi manual — tetap meskipun ada anak</option>
        <option value="sum">Jumlahkan nilai anak</option>
        <option value="ratio">Pembilang ÷ pembagi × pengali</option>
        <option value="weighted_sum">Jumlahkan kontribusi berbobot</option>
        <option value="scale">Nilai satu anak × pengali</option>
        <option value="offset_ratio">Nilai dasar + pembilang ÷ pembagi × pengali</option>
    </select>
    <small class="form-text text-muted">Satuan mengikuti isian sendiri. Persen diisi 91,45, bukan 0,9145. Tambahkan anak dahulu sebelum memilih sumber rumus.</small>
</div>
<div id="calculation_details">
    <div class="form-group calculation-parameter" data-calculation-types="ratio offset_ratio">
        <label for="calculation_numerator">Pembilang</label>
        <select id="calculation_numerator" name="calculation_config[numerator_id]" class="form-control calculation-source"></select>
    </div>
    <div class="form-group calculation-parameter" data-calculation-types="ratio offset_ratio">
        <label for="calculation_denominator">Pembagi</label>
        <select id="calculation_denominator" name="calculation_config[denominator_id]" class="form-control calculation-source"></select>
    </div>
    <div class="form-group calculation-parameter" data-calculation-types="scale">
        <label for="calculation_source">Sumber nilai</label>
        <select id="calculation_source" name="calculation_config[source_id]" class="form-control calculation-source"></select>
    </div>
    <div class="form-group calculation-parameter" data-calculation-types="ratio scale offset_ratio">
        <label for="calculation_factor">Pengali</label>
        <input id="calculation_factor" name="calculation_config[factor]" class="form-control" inputmode="decimal">
        <small class="text-muted">Rasio biasa: 100. Rasio berbobot 40%: 40. Nilai persen × bobot 40%: 0,4.</small>
    </div>
    <div class="form-group calculation-parameter" data-calculation-types="offset_ratio">
        <label for="calculation_baseline">Nilai dasar</label>
        <input id="calculation_baseline" name="calculation_config[baseline]" class="form-control" inputmode="decimal">
    </div>
    <div class="form-group calculation-parameter" data-calculation-types="weighted_sum">
        <label>Bobot setiap anak (%)</label>
        <div id="calculation_weights"></div>
        <small class="text-muted">Contoh IKK 14: 40, 40, 20. Bobot 0 mengabaikan anak tersebut.</small>
    </div>
</div>
<div id="calculation_preview" class="alert alert-info" aria-live="polite"></div>
