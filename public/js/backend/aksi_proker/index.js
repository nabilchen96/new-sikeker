document.addEventListener('DOMContentLoaded', function () {
    getData();
});

var table = null;

function getData() {

    const params = new URLSearchParams(window.location.search);
    const id_rencana_proker = params.get('id_rencana_proker');

    table = $("#myTable").DataTable({
        ordering: true,
        processing: true,
        searching: false,
        lengthChange: false,
        ajax: {
            url: '/data-aksi-proker?id_rencana_proker=' + id_rencana_proker,
        },
        columns: [
            {
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                render: function (data, type, row, meta) {
                    return `<b>Rencana Proker: </b><br>
                    ${row.rencana_proker}<br><br>
                    <b>Kegiatan: </b><br>
                    ${row.kegiatan_proker}`;
                }
            },
            {
                render: function (data, type, row, meta) {
                    function formatTanggal(tgl) {
                        if (!tgl) return '';
                        const [yyyy, mm, dd] = tgl.split('-');
                        return `${dd}-${mm}-${yyyy}`;
                    }

                    return `<b>Waktu Pengerjaan: </b>
                    <br>${formatTanggal(row.tgl_mulai)}
                    → ${formatTanggal(row.tgl_selesai)}<br><br>
                    <b>Unit:</b><br>
                    ${row.unit}, Tahun ${row.tahun}
                    `;
                }
            },
            {
                render: function (data, type, row, meta) {
                    return `<b>File Kegiatan: </b><br>
                    <a href="/storage/${row.bukti_kegiatan}"><i class="bi bi-file-earmark-text"></i> Download File</a> <br><br>
                    <b>Created At:</b><br>
                    ${row.tgl_pengerjaan}`;
                }
            },
            {
                render: function (data, type, row, meta) {
                    return `
                    <div class="dropdown">
                        <a class="text-success" href="#" data-toggle="dropdown">
                            <i class="bi bi-three-dots" style="font-size:1.5rem"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item text-success" data-toggle="modal" data-target="#modalEdit"
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
        modal.find('#id_rencana_proker').val(cokData[0].id_rencana_proker);
        modal.find('#kegiatan_proker').val(cokData[0].kegiatan_proker);
        modal.find('#progress').val(cokData[0].progress);
        modal.find('#tgl_pengerjaan').val(cokData[0].tgl_pengerjaan);
    }
});

$('#modalEdit').on('show.bs.modal', function (event) {

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
        modal.find('#id_rencana_proker3').val(cokData[0].id_rencana_proker);
        id_rencana_proker3.setValue(cokData[0].id_rencana_proker)
        modal.find('#kegiatan_proker').val(cokData[0].kegiatan_proker);
        modal.find('#progress').val(cokData[0].progress);
        modal.find('#tgl_pengerjaan').val(cokData[0].tgl_pengerjaan);
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
        formData.get('id') == "" ? "/store-aksi-proker" : "/update-aksi-proker",
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

            axios.post("/delete-aksi-proker", {
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
