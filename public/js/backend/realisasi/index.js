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


        if (level === 0) {
            document.getElementById('target').closest('.form-group').style.display = 'none';
            document.getElementById('jenis_target').closest('.form-group').style.display = 'none';
        } else {

            document.getElementById('target').closest('.form-group').style.display = '';
            document.getElementById('jenis_target').closest('.form-group').style.display = '';

            modal.find('#target').val(target);
            modal.find('#jenis_target').val(jenis_target);
        }


    }
});


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

    $("#respon_error").html("");
    $("#tombol_kirim").prop("disabled", true);

    axios.post(
        formData.get('id') == "" ? "/store-bagian" : "/update-bagian",
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

                $("#modalTambahBagian").modal("hide");
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
