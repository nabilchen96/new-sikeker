document.addEventListener(
    'DOMContentLoaded',
    function () {

        getData();

    });



let table;


// SIMPAN


$("#form").submit(function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    axios.post(
        '/store-cascading',
        formData
    )
        .then(res => {
            if (res.data.responCode == 1) {
                Swal.fire(
                    'Sukses',
                    res.data.respon,
                    'success'
                );
                $("#modal").modal('hide');
                location.reload();
            }
        })
});






// HAPUS


function hapusData(id) {
    Swal.fire({
        title: 'Yakin hapus data?',
        icon: 'warning',
        showCancelButton: true
    })

    .then((result) => {
        if (result.isConfirmed) {
            axios.post('/delete-cascading',
                {
                    id: id
                }

            )
            .then(res => {
                location.reload();
            });
        }
    });
}