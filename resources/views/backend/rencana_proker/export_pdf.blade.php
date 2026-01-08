<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }
    </style>
</head>
<body>

<div id="laporan-pdf">
    <h3>
        LAPORAN PROGRAM KERJA UNIT POLITEKNIK PENERBANGAN PALEMBANG<br>
        TAHUN {{ $tahun->tahun }}
    </h3>

    <hr>
    <br><br>
    @foreach ($data as $unitProkers)
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;" colspan="3" width="100%">
                        Unit: {{ $unitProkers->first()->nama_unit }}
                    </th>
                </tr>
            </thead>
            <thead>
                <tr>
                    <th width="50%">Rencana Proker</th>
                    <th width="15%">Jenis Proker</th>
                    <th width="25%">Waktu Pengerjaan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unitProkers as $item)
                    <tr>
                        <td>{{ $item->rencana_proker }}</td>
                        <td align="center">{{ $item->jenis_proker }}</td>
                        <td align="center">
                            {{ $item->tgl_mulai }} s/d {{ $item->tgl_selesai }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>
    @endforeach
</div>

{{-- <!-- html2pdf -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
window.onload = function () {
    const element = document.getElementById('laporan-pdf');

    html2pdf().set({
        margin: 0.5,
        filename: 'Laporan_Rencana_Proker_{{ $tahun->tahun }}.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    }).from(element).save();
};
</script> --}}

</body>
</html>
