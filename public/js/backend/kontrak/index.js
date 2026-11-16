document.addEventListener('DOMContentLoaded', function () {
    // getData();
});

// $("#btnCari").click(function () {
//     table.ajax.reload();
// });

var table = null;

// =========================
// SHOW MODAL
// =========================
$('#modalSk').on('show.bs.modal', function (event) {

    console.log('cek');

    var button = $(event.relatedTarget); // Button that triggered the modal
    var id = button.data('bs-id'); // Extract info from data-* attributes
    var nama = button.data('bs-nama'); // Extract info from data-* attributes

    document.getElementById("formSk").reset();
    document.getElementById('id').value = '';


    if (id) {

        var modal = $(this);

        modal.find('#id').val(id);
        modal.find('#nama').val(nama);
    }
});

$('#modalTambahBagian').on('show.bs.modal', function (event) {

    console.log('cek');

    var button = $(event.relatedTarget);
    var id = button.data('bs-id');
    var id_parent = button.data('bs-id_parent');
    var indikator_induk = button.data('bs-indikator_induk');
    var sub_indikator = button.data('bs-sub_indikator');
    var kode = button.data('bs-kode');
    var target = button.data('bs-target');
    var jenis_target = button.data('bs-jenis_target');
    var level = button.data('bs-level');

    console.log(indikator_induk);


    document.getElementById("formTambahBagian").reset();
    document.getElementById('id').value = '';


    if (id_parent) {

        var modal = $(this);

        modal.find('#id').val(id);
        modal.find('#id_parent').val(id_parent);
        modal.find('#indikator_induk').val(indikator_induk);
        modal.find('#sub_indikator').val(sub_indikator);
        modal.find('#kode').val(kode);

        console.log(level);


        modal.find('#target').val(id ? target : '');
        modal.find('#jenis_target').val(id ? jenis_target : 'orang');
        if (id && jenis_target && !modal.find('#jenis_target').val()) {
            modal.find('#jenis_target').append(new Option(jenis_target, jenis_target, true, true));
        }



    }
});


// Calculation controls are scoped to this modal so duplicate legacy IDs do not leak.
const calculationForm = document.getElementById('formTambahBagian');
let calculationChildren = [];
let effectiveCalculationType = 'manual';
const calculationLabels = {manual: 'Nilai manual', sum: 'Jumlah anak', ratio: 'Pembilang ÷ pembagi × pengali', weighted_sum: 'Jumlah berbobot', scale: 'Nilai anak × pengali', offset_ratio: 'Nilai dasar + rasio'};
const decimal = value => Number(String(value).replace(',', '.'));
function updateCalculationPreview() {
    const selected = calculationForm.querySelector('#calculation_type').value;
    const type = selected || effectiveCalculationType;
    calculationForm.querySelectorAll('.calculation-parameter').forEach(group => {
        const visible = group.dataset.calculationTypes.split(' ').includes(type);
        group.hidden = !visible;
        group.querySelectorAll('input, select').forEach(input => input.disabled = !visible || !selected);
    });
    calculationForm.querySelector('#manual_target_group').hidden = type !== 'manual';
    const preview = calculationForm.querySelector('#calculation_preview');
    const read = selector => {
        const child = calculationChildren.find(item => String(item.id) === calculationForm.querySelector(selector).value);
        if (!child || child.target === null) throw new Error('Pilih sumber yang memiliki target lengkap.');
        return Number(child.target);
    };
    try {
        let result;
        if (type === 'manual') result = calculationForm.querySelector('#target').value === '' ? null : decimal(calculationForm.querySelector('#target').value);
        else if (type === 'sum') {
            if (!calculationChildren.length) throw new Error('Tambahkan anak sebagai sumber.');
            // A legacy profile can select a subset of direct children.
            const profile = calculationForm.dataset.profileIds ? JSON.parse(calculationForm.dataset.profileIds) : null;
            const sources = !selected && profile ? calculationChildren.filter(child => profile.includes(String(child.id))) : calculationChildren;
            if (sources.some(child => child.target === null)) throw new Error('Lengkapi target sumber.');
            if (!sources.length || (!selected && profile && sources.length !== profile.length)) throw new Error('Sumber profil belum lengkap.');
            result = sources.reduce((sum, child) => sum + Number(child.target), 0);
        } else if (type === 'weighted_sum') {
            if (!calculationChildren.length) throw new Error('Simpan induk dahulu, lalu tambahkan anak dan isi bobotnya.');
            const inputs = calculationForm.querySelectorAll('#calculation_weights input');
            if (![...inputs].some(input => decimal(input.value || 0) > 0)) throw new Error('Isi bobot anak untuk menghitung hasil.');
            result = 0;
            calculationForm.querySelectorAll('#calculation_weights input').forEach(input => {
                const weight = decimal(input.value || 0);
                const child = calculationChildren.find(item => String(item.id) === input.dataset.childId);
                if (weight && child.target === null) throw new Error('Target anak berbobot belum lengkap.');
                result += Number(child.target) * weight / 100;
            });
        } else {
            const factor = decimal(calculationForm.querySelector('#calculation_factor').value);
            if (type === 'scale') result = read('#calculation_source') * factor;
            else {
                const denominator = read('#calculation_denominator');
                if (!denominator) throw new Error('Pembagi nol; hasil belum dapat dihitung.');
                result = read('#calculation_numerator') / denominator * factor;
                if (type === 'offset_ratio') result += decimal(calculationForm.querySelector('#calculation_baseline').value);
            }
        }
        if (result !== null && !Number.isFinite(result)) throw new Error('Isi angka yang valid.');
        const unit = calculationForm.querySelector('#jenis_target').value;
        preview.textContent = (selected ? '' : 'Profil Excel 2026 — ') + calculationLabels[type] + ': ' + (result === null ? 'belum diisi' : result.toLocaleString('id-ID', {maximumFractionDigits: 2}) + (unit === 'persen' ? '%' : ' ' + unit));
    } catch (error) {
        preview.textContent = calculationLabels[type] + ': ' + error.message;
    }
}
$('#modalTambahBagian').on('show.bs.modal', function (event) {
    const id = $(event.relatedTarget).data('bs-id');
    const settings = id ? window.kontrakCalculationForms[String(id)] : null;
    calculationForm.querySelector('#calculation_settings').open = false;
    calculationChildren = settings ? settings.children : [];
    effectiveCalculationType = settings ? settings.effective_type : 'manual';
    if (settings && !calculationForm.querySelector('#jenis_target').value) {
        calculationForm.querySelector('#jenis_target').value = settings.unit || '';
    }
    const config = settings ? (settings.config || settings.effective_config || {}) : {};
    calculationForm.querySelector('#calculation_type').value = settings ? (settings.type || '') : 'manual';
    calculationForm.dataset.profileIds = config.child_ids ? JSON.stringify(config.child_ids.map(String)) : '';
    calculationForm.querySelectorAll('.calculation-source').forEach(select => {
        select.replaceChildren(new Option('Pilih anak langsung', ''));
        calculationChildren.forEach(child => select.add(new Option(child.label, child.id)));
    });
    calculationForm.querySelector('#calculation_numerator').value = config.numerator_id || '';
    calculationForm.querySelector('#calculation_denominator').value = config.denominator_id || '';
    calculationForm.querySelector('#calculation_source').value = config.source_id || '';
    calculationForm.querySelector('#calculation_factor').value = config.factor ?? 100;
    calculationForm.querySelector('#calculation_baseline').value = config.baseline ?? 0;
    const weights = calculationForm.querySelector('#calculation_weights');
    weights.replaceChildren();
    if (!calculationChildren.length) {
        const hint = document.createElement('p');
        hint.className = 'text-muted';
        hint.textContent = 'Belum ada anak. Metode ini tetap bisa disimpan; bobot diisi setelah anak ditambahkan.';
        weights.append(hint);
    }
    calculationChildren.forEach(child => {
        const label = document.createElement('label');
        label.className = 'd-block';
        label.append(document.createTextNode(child.label + ' (%)'));
        const input = document.createElement('input');
        input.className = 'form-control';
        input.name = 'calculation_config[weights][' + child.id + ']';
        input.inputMode = 'decimal';
        input.dataset.childId = String(child.id);
        input.value = config.weights?.[child.id] ?? 0;
        label.append(input);
        weights.append(label);
    });
    calculationForm.querySelectorAll('.calculation-source').forEach(select => select.disabled = false);
    calculationForm.querySelector('#respon_error').textContent = '';
    updateCalculationPreview();
});
$('#modalTambahBagian').on('shown.bs.modal', function () {
    this.scrollTop = 0;
});
calculationForm.addEventListener('input', updateCalculationPreview);
calculationForm.addEventListener('change', updateCalculationPreview);

// =========================
// FORM SUBMIT (AJAX)
// =========================
let formSk = document.getElementById('formSk');
let formTambahBagian = document.getElementById('formTambahBagian');

formSk.onsubmit = function (e) {
    e.preventDefault();

    let formData = new FormData(formSk);

    $("#respon_error").html("");
    $("#tombol_kirim").prop("disabled", true);

    axios.post(
        formData.get('id') == "" ? "/store-sasaran-kinerja" : "/update-sasaran-kinerja",
        formData
    )
        .then(res => {
            $("#tombol_kirim").prop("disabled", false);

            if (res.data.responCode == 1) {
                Swal.fire({
                    icon: "success",
                    title: "Sukses",
                    text: res.data.respon,
                    showConfirmButton: false,
                    timer: 2000
                });

                $("#modalSk").modal("hide");
                location.reload();

            } else {
                let err = "";
                Object.entries(res.data.respon).forEach(([field, msg]) => {
                    msg.forEach(m => err += `<li>${m}</li>`);
                });
                $("#respon_error").html(err);
            }
        })
        .catch(err => {
            $("#tombol_kirim").prop("disabled", false);
            console.error(err);
        });
}

formTambahBagian.onsubmit = function (e) {
    e.preventDefault();

    let formData = new FormData(formTambahBagian);

    $(formTambahBagian).find("#respon_error").html("");
    $(formTambahBagian).find("#tombol_kirim").prop("disabled", true);

    axios.post(
        formData.get('id') == "" ? "/store-bagian" : "/update-bagian",
        formData
    )
        .then(res => {
            $(formTambahBagian).find("#tombol_kirim").prop("disabled", false);

            if (res.data.responCode == 1) {
                Swal.fire({
                    icon: "success",
                    title: "Sukses",
                    text: res.data.respon,
                    showConfirmButton: false,
                    timer: 2000
                });

                $("#modalTambahBagian").modal("hide");
                location.reload();

            } else {
                let err = "";
                Object.entries(res.data.respon).forEach(([field, msg]) => {
                    msg.forEach(m => err += `<li>${m}</li>`);
                });
                $(formTambahBagian).find("#respon_error").html(err);
            }
        })
        .catch(err => {
            $(formTambahBagian).find("#tombol_kirim").prop("disabled", false);
            const errors = err.response?.data?.errors;
            const box = formTambahBagian.querySelector('#respon_error');
            box.textContent = errors ? Object.values(errors).flat().join(' ') : 'Data belum tersimpan. Periksa koneksi lalu coba lagi.';
            if (errors && Object.keys(errors).some(key => key.startsWith('calculation_'))) {
                calculationForm.querySelector('#calculation_settings').open = true;
            }
            document.getElementById('modalTambahBagian').scrollTop = 0;
        });
}


// =========================
// HAPUS DATA
// =========================
hapusDataSasaranKinerja = (id) => {

    Swal.fire({
        title: "Yakin hapus data?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya",
        cancelButtonColor: "#3085d6",
        cancelButtonText: "Batal"
    }).then((result) => {

        if (result.value) {

            axios.post("/delete-sasaran-kinerja", {
                id
            })
                .then(res => {

                    if (res.data.responCode == 1) {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            timer: 2000,
                            showConfirmButton: false
                        });

                        location.reload();

                    } else {
                        Swal.fire({
                            icon: "warning",
                            title: "Gagal",
                            text: res.data.respon,
                        });
                    }

                });
        }
    });
}

hapusDataBagian = (id) => {

    Swal.fire({
        title: "Yakin hapus data?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya",
        cancelButtonColor: "#3085d6",
        cancelButtonText: "Batal"
    }).then((result) => {

        if (result.value) {

            axios.post("/delete-bagian", {
                id
            })
                .then(res => {

                    if (res.data.responCode == 1) {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            timer: 2000,
                            showConfirmButton: false
                        });

                        location.reload();

                    } else {
                        Swal.fire({
                            icon: "warning",
                            title: "Gagal",
                            text: res.data.respon,
                        });
                    }

                });
        }
    });
}
