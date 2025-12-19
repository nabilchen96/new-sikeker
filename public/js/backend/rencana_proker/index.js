document.addEventListener('DOMContentLoaded', function () {
    getData();
});

$("#btnCari").click(function () {
    table.ajax.reload();
});

var table = null;

function getData() {

    const params = new URLSearchParams(window.location.search);
    const id_proker = params.get('id_proker');

    table = $("#myTable").DataTable({
        ordering: true,
        processing: true,
        searching: false,
        lengthChange: false,
        ajax: {
            url: '/data-rencana-proker?id_proker=' + id_proker,
            data: function (d) {
                d.keyword = $("#searchInput").val();
            }
        },
        columns: [
            {
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'rencana_proker'
            },
            {
                data: 'jenis_proker'
            },
            // {
            //     render: function (data, type, row, meta) {
            //         return `Bulan ${row.bulan_mulai}, Minggu ${row.minggu_mulai} → Bulan ${row.bulan_akhir}, Minggu ${row.minggu_akhir}`;
            //     }
            // },
            {
                render: function (data, type, row, meta) {
                    return `${row.tgl_mulai} → ${row.tgl_selesai}`;
                }
            },
            // {
            //     render: function (data, type, row, meta) {
            //         return `
            //         Persentase ${row.total_progress ?? 0}%
            //         <div class="progress" style="border: grey 1px solid;">
            //             <div class="progress-bar" role="progressbar" style="width: ${row.total_progress}%;"></div>
            //         </div>`;
            //     }
            // },
            {
                data: 'created_at'
            },
            {
                render: function (data, type, row, meta) {
                    if (row.status_approval == 'Dalam Pengajuan' || row.status_approval == 'Diterima') {
                        return ``
                    } else {
                        return `
                        <div class="dropdown">
                            <a class="text-success" href="#" data-toggle="dropdown">
                                <i class="bi bi-three-dots" style="font-size:1.5rem"></i>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item text-success" data-toggle="modal" data-target="#modal"
                                    href="javascript:void(0)" data-bs-id="${row.id}">
                                    <i class="bi bi-grid"></i> &nbsp; Edit
                                </a>
                                <a class="dropdown-item text-danger" onclick="hapusData(${row.id})">
                                    <i class="bi bi-trash"></i> &nbsp; Hapus
                                </a>
                            </div>
                        </div>
                        `;
                    }
                }
            }
        ],
    });
}


// =========================
// SHOW MODAL
// =========================
$('#modal').on('show.bs.modal', function (event) {

    var button = $(event.relatedTarget); // Button that triggered the modal
    var recipient = button.data('bs-id'); // Extract info from data-* attributes
    var cok = $("#myTable").DataTable().rows().data().toArray();

    let cokData = cok.filter((dt) => {
        return dt.id == recipient;
    });

    document.getElementById("form").reset();
    document.getElementById('id').value = '';

    if (recipient) {

        var modal = $(this);

        modal.find('#id').val(cokData[0].id);
        modal.find('#id_proker').val(cokData[0].id_proker);
        // modal.find('#bulan_mulai').val(cokData[0].bulan_mulai);
        // modal.find('#minggu_mulai').val(cokData[0].minggu_mulai);
        // modal.find('#bulan_akhir').val(cokData[0].bulan_akhir);
        // modal.find('#minggu_akhir').val(cokData[0].minggu_akhir);
        modal.find('#rencana_proker').val(cokData[0].rencana_proker);
        modal.find('#tgl_mulai').val(cokData[0].tgl_mulai);
        modal.find('#tgl_selesai').val(cokData[0].tgl_selesai);
        modal.find('#jenis_proker').val(cokData[0].jenis_proker);
    }
});


// =========================
// FORM SUBMIT (AJAX)
// =========================
let form = document.getElementById('form');

form.onsubmit = function (e) {
    e.preventDefault();

    let formData = new FormData(form);

    $("#respon_error").html("");
    $("#tombol_kirim").prop("disabled", true);

    axios.post(
        formData.get('id') == "" ? "/store-rencana-proker" : "/update-rencana-proker",
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

                $("#modal").modal("hide");
                table.destroy();
                getData();
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

formUbahStatusProker.onsubmit = function (e) {
    e.preventDefault();

    let formData = new FormData(formUbahStatusProker);

    $("#respon_error").html("");
    $("#tombol_kirim").prop("disabled", true);

    axios.post(
        "/ubah-status-proker",
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
hapusData = (id) => {

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

            axios.post("/delete-rencana-proker", {
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

                        table.destroy();
                        getData();

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

ajukanProker = (id) => {

    Swal.fire({
        title: "Ajukan Proker?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya",
        cancelButtonColor: "#3085d6",
        cancelButtonText: "Batal"
    }).then((result) => {

        if (result.value) {

            axios.post("/ajukan-proker", {
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
