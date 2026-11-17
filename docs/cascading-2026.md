# Analisis cascading dan panduan pengisian SIKEKER

Sumber: workbook `Cascading Kinerja Poltekbang Palembang 2026 (1) (3)-1.xlsx`, sheet **Cascading PK dan KPI 2026**, kolom E7:E126. Formula diperiksa bersama nilai hasil tersimpan Excel; bukan sekadar tampilan sel. Instruksi/teks di workbook diperlakukan sebagai data. Tidak mengimpor atau mengubah database pengguna secara otomatis.

Ada **20 IKK PK** dan **14 KPI**. Sheet1 mengulang seluruh target PK dengan hasil yang sama; Sheet2 mengulang target KPI dengan nilai yang sama. Workbook tidak memuat IKK 17–21 atau IKK 23. Huruf P di kolom unit kerja menyatakan pembagian peran, bukan pengali rumus.

## Temuan pada aplikasi sebelumnya

- Semua IKK dianggap persen dan targetnya sempat dikunci 100. Padahal empat IKK adalah nilai manual.
- Node yang memiliki anak selalu dijumlahkan, sehingga target manual/bobot hilang saat anak ditambahkan.
- Rumus A/B tidak mewakili penjumlahan IKK 11, bobot IKK 14, dan konstanta IKK 24.
- Satuan node pernah diwarisi dari anak atau dipaksa persen; sekarang memakai isian node itu sendiri.
- Kolom target awal `integer` tidak dapat mewakili pecahan dan target rupiah di luar jangkauan integer MySQL bertanda (Rp15,45 miliar). Migrasi memakai decimal(22,6).
- Halaman realisasi sebelumnya menjumlahkan target dan realisasi dari berbagai tingkat/satuan lalu membaginya. Sekarang menggunakan sumber manual yang benar dan metode yang sama dengan target; capaian = hasil realisasi ÷ hasil target × 100.

## Pemetaan PK

| IKK | Sumber Excel | Rumus target | Hasil |
|---|---|---|---:|
| IKK 1 | E7 | A ÷ B × 100 | 91,45 % |
| IKK 2 | E10 | Jumlah anak A ÷ jumlah anak B × 100 | 95,57 % |
| IKK 3 | E21 | Jumlah anak A ÷ jumlah anak B × 100 | 93,52 % |
| IKK 4 | E30 | A + B; A = akreditasi × 60; B = prodi terakreditasi ÷ seluruh prodi × 40 | 86,67 % |
| IKK 5 | E36 | A + B; A = anak × 0,4; B = anak × 0,6 | 93,92 % |
| IKK 6 | E41 | Jumlah anak A ÷ jumlah anak B × 100 | 100 % |
| IKK 7 | E50 | A ÷ B × 100 | 100 % |
| IKK 8 | E53 | Jumlah anak A ÷ jumlah anak B × 100 | 87,5 % |
| IKK 9 | E62 | Jumlah anak A ÷ jumlah anak B × 100 | 95,27 % |
| IKK 10 | E69 | Anak 1 ÷ anak 2 × 100 | 85,71 % |
| IKK 11 | E72 | Anak 1 + anak 2 + anak 3 | 80,34 % |
| IKK 12 | E76 | Anak 1 ÷ anak 2 × 100 | 9,52 % |
| IKK 13 | E79 | Anak 1 ÷ anak 2 × 100 | 86,67 % |
| IKK 14 | E82 | Anak 1 × 40% + anak 2 × 40% + anak 3 × 20% | 78,19 % |
| IKK 15 | E86 | Manual, meskipun ada rincian | 3,62 nilai |
| IKK 16 | E87 | A + B; A = anak 1 ÷ anak 2 × 60; B = anak 1 ÷ anak 2 × 40 | 90,83 % |
| IKK 22 | E94 | Manual | 82 nilai |
| IKK 24 | E95 | 50 + anak 3 ÷ anak 4 × 50; anak 1 dan 2 tidak dipakai Excel | 91,67 % |
| IKK 25 | E100 | Manual 90,5; delapan rincian IKPA tidak memiliki nilai/rumus | 90,5 nilai |
| IKK 26 | E109 | Manual | 84,15 nilai |

Semua hasil dihitung dengan presisi penuh dan dibulatkan hanya saat ditampilkan. Contoh IKK 5 dihitung 36,58 + 57,342 = 93,922 → 93,92; tidak menjumlahkan angka tampilan yang telah dipotong.

## Pemetaan KPI

Workbook hanya memberi target manual. Rincian naratif bukan rumus yang bisa dieksekusi; tidak boleh dijumlahkan otomatis.

| Kode | Indikator | Target | Satuan |
|---|---|---:|---|
| 1 | Margin EBITDA (%) | 1,79 | persen |
| 2 | Realisasi pendapatan BLU | 15.450.000.000 | rupiah |
| 3 | Realisasi pendapatan dari optimalisasi aset | 1.850.000.000 | rupiah |
| 4 | Persentase penyelesaian modernisasi pengelolaan BLU | 100 | persen |
| 5 | Tingkat Perencanaan dan Pengelolaan Rekening Badan Layanan Umum | 4 | indeks |
| 6 | Indeks Peningkatan Efisiensi Layanan BLU | 2 | indeks |
| 7 | Kuantitas dan Kualitas Penelitian, HaKI, dan Produk Inovasi | 54,3 | nilai |
| 8 | Kegiatan pengabdian pada masyarakat | 15 | jumlah |
| 9 | Kuantitas dan Kualitas Dosen | 88,75 | persen |
| 10 | Pendidikan Vokasi Berbasis Kerja Sama dengan Industri dan Dunia Kerja | 51,25 | persen |
| 11 | Beasiswa | 20,65 | persen |
| 12 | Serapan Lulusan dan Tingkat Kepuasan Pengguna Lulusan | 100 | persen |
| 13 | Kerjasama | 49,5 | nilai |
| 14 | Peningkatan Kualitas Kelembagaan | 2,33 | nilai |

## Dua cara pengisian

### Form per baris

Form per baris dikembalikan ke versi sederhana dengan modal ukuran biasa yang dapat digulir. Isi nama, kode, target, dan satuan. Bagian **Atur perhitungan (opsional)** menyediakan manual, jumlah anak, rasio, bobot, serta nilai dasar + rasio. Gabungan berbobot tetap boleh disimpan sebelum anak tersedia. Pilihan ini berguna untuk menambah atau mengubah satu indikator/rincian.

Jika baris memakai rumus tabel, nama dan satuannya masih bisa diedit melalui modal. Rumus yang sudah ada dipertahankan. Untuk mengubah rumus, gunakan **Edit seperti Excel**.

Pada tab KPI, menu **Tambah IKU** di kolom Sasaran Kinerja menambahkan IKU langsung di bawah sasaran tersebut. Menu **Tambah rincian** pada baris IKU menambahkan anak ke IKU itu sendiri. Keduanya memakai induk yang berbeda sesuai lokasi tombol.

Jika IKU telanjur menjadi rincian IKU lain, buka menu barisnya lalu pilih **Pindahkan langsung ke sasaran**. Baris, ID, anak, realisasi, dan kaitan program kerja dipertahankan. Pemindahan dibatasi ke sasaran asal pada tahun aktif dan ditolak jika masih ada referensi rumus yang melintasi bagian yang dipindahkan.

### Tabel seperti Excel

1. Di Data Kontrak, klik **Edit seperti Excel**. Pilih IKK/KPI yang ingin diisi, atau pilih **+ Indikator baru** dan sasaran kinerjanya.
2. Tabel menampilkan A Kode, B Indikator/rincian, C Induk (nomor baris), D Satuan, E Target/rumus, dan F Hasil. Angka serta rumus yang sudah ada ditampilkan otomatis.
3. Isi atau ubah angka pada kolom E. Untuk hasil perhitungan, tulis rumus yang diawali `=`. Hasil muncul pada kolom F; tidak perlu memilih metode rumus atau pembilang/pembagi melalui beberapa form.
4. Gunakan Tab untuk pindah sel, Enter atau panah atas/bawah untuk pindah baris. Bar formula di atas tabel memperlihatkan dan dapat mengedit sel aktif.
5. Dukung tempel TSV dari Excel. Misalnya salin tiga angka 55, 100, 80,95 lalu tempel mulai E2; ketiganya mengisi E2:E4. Tempel beberapa kolom mengikuti urutan kolom tabel. Hasil F tidak bisa ditimpa.
6. **+ Tambah baris** menambah baris baru di bawah tabel; kolom C menentukan induknya. Induk baris lama dapat dikoreksi melalui kolom C, dan Sasaran kinerja dapat diganti di pilihan atas. ID baris, realisasi, serta kaitan program kerja tetap dipertahankan. Perpindahan dibatasi pada susunan indikator yang sedang dibuka dan sasaran dalam jenis/tahun yang sama. Baris baru terakhir yang belum disimpan boleh dibatalkan dengan tombol yang tersedia.
7. Klik **Simpan tabel** untuk menyimpan seluruh indikator dan rinciannya sebagai satu transaksi. Jika suatu isian/rumus gagal, data tidak ditulis sebagian. Catatan realisasi dan ID lama tetap dipertahankan.

| Contoh | Isian rumus |
|---|---|
| Rasio dua sumber pada E2 dan E3 | `=E2/E3*100` |
| Penjumlahan sumber E2:E4 | `=SUM(E2:E4)` atau `=E2+E3+E4` |
| IKK 14, anak di E2:E4 | `=E2*40%+E3*40%+E4*20%` |
| Bobot akreditasi, sumber pada E3 | `=E3*60` |
| Rasio berbobot, sumber pada E5 dan E6 | `=E5/E6*40` |
| Nilai dasar + layanan pada E2/E3 | `=50+E2/E3*50` |

Untuk IKK 4 dengan susunan induk baris 1, A baris 2, akreditasi baris 3, B baris 4, prodi terakreditasi baris 5, dan seluruh prodi baris 6:

- E1 `=E2+E4`
- E2 `=E3*60`
- E3 `1`
- E4 `=E5/E6*40`
- E5 `2`
- E6 `3`

Referensi mengikuti **nomor baris tabel yang sedang dibuka**, bukan nomor baris asli pada berkas Excel. Rumus lama otomatis diterjemahkan ke nomor ini. Setelah disimpan, referensi memakai ID database sehingga perubahan urutan/nama tidak menggeser sumber. Nomor sel ditampilkan ulang sesuai urutan tabel terbaru.

Angka biasa boleh memakai koma desimal (`80,95`). Di dalam rumus gunakan titik (`0.4`) atau persen (`40%`). SUM mendukung rentang, titik koma, dan koma pemisah referensi. Pengisian ini mendukung aritmetika `+ - * /`, tanda persen, kurung, dan SUM; bukan seluruh fungsi Excel atau referensi lintas indikator/file.

Pada target/tabel Excel, sel sumber kosong menghasilkan — sampai sumbernya lengkap. Pembagi target nol, referensi di luar tabel, fungsi yang tidak didukung, dan rumus siklik diberi penjelasan; siklus dan formula tidak valid ditolak saat menyimpan. Nol yang benar tetap dihitung sebagai nol. Satuan setiap baris mengikuti isian sendiri, tanpa dipaksa persen.

Sumber realisasi manual ditelusuri dari referensi rumus, tanpa menjumlahkan induk dan anak dua kali. Untuk capaian, isian yang belum pernah dilaporkan dihitung 0, dan setiap bagian pembagi memakai nilai target acuannya. Nilai terakhir bulan sebelumnya tetap dibawa jika bulan berjalan belum diisi. Aturan ini hanya untuk progres/capaian; target dan rumus Excel yang disimpan tidak diubah.

## Bagian Excel yang perlu keputusan pemilik indikator

- **IKK 24:** konstanta 50 tidak diturunkan dari data unit 0/0. Aplikasi meniru formula tertulis E95, tetapi dasar kebijakan pemberian 50 harus dikonfirmasi. Jangan menganggap 0/0 = 100%.
- **IKK 1:** pembilang menyebut lulusan 2025 yang bekerja 2026; pembagi menyebut lulusan 2024 yang bekerja 2025. Definisi pembanding antarangkatan perlu diperiksa; aplikasi tidak mengganti penyebut 117.
- **IKK 3:** judul pembilang menyebut pola pembibitan 2025, sedangkan rincian mencakup tiga jenis diklat. Rumus 1010/1080 jelas, tetapi label populasi/periode perlu dikonfirmasi.
- **IKK 5:** angka 91,45 dan 95,57 adalah angka manual yang disalin, bukan formula referensi ke IKK 1/2. Aplikasi mempertahankan ini; tidak membuat hubungan lintas indikator tanpa keputusan pengguna.
- **IKK 14 vs KPI 9:** PK 78,19 dan KPI 88,75 bukan kesalahan hitung yang boleh disamakan; sumber dan bobotnya berbeda.
- **IKK 25:** rincian komponen IKPA tidak memiliki angka. Target 90,5 tetap manual; jangan mengarang bobot atau menganggap nilai kosong sebagai nol.
- Ada kode rincian dan satuan yang kosong, sel gabungan, nama salah ketik (misalnya Bobor/SOM), serta pengulangan sheet. Tidak mengubah nama/populasi bisnis secara otomatis.

## Memperbarui lokal

Cadangkan database lokal sebelum migrasi. Pada branch `fix/ikk-target-ratio`:

```powershell
git pull --ff-only origin fix/ikk-target-ratio
composer install
php artisan migrate
php artisan optimize:clear
php artisan serve
```

Migrasi menambahkan pengaturan nullable, mempertahankan target yang sudah ada, dan memperluas tipe target. Nilai pecahan yang pernah terpotong pada database lama perlu diperbaiki dari sumber Excel; migrasi tidak bisa memulihkan angka yang telah hilang. Rollback hanya menghapus kolom pengaturan, tanpa mempersempit kolom target agar pecahan/nominal besar tidak hilang.

## Verifikasi

`tests/Fixtures/cascading_2026.json` mencatat lokasi sel, formula, nilai hasil tersimpan, dan hierarki sumber. Pengujian membandingkan seluruh target bernilai angka dengan workbook, mencakup target rupiah/desimal, realisasi agregat, satuan terpisah, rumus eksplisit, sumber tidak valid, dan pembagi nol. Pengujian HTTP memakai database SQLite sementara; tidak memakai database pengguna.

Tabel Excel diuji untuk seluruh 34 indikator yang sudah ada, penyimpanan atomik, referensi ID yang stabil, penolakan rumus siklik, dan pemeliharaan ID/realisasi. Browser memeriksa hitung ulang, Tab/Enter, tempel beberapa baris, simpan/muat ulang, tampilan ponsel, serta form per baris yang dipulihkan.

## Tampilan dan penyimpanan realisasi

Ringkasan hijau PK/KPI menampilkan Indikator Kinerja, Target, Satuan, dan % Capaian. Kolom Realisasi agregat dihapus dari ringkasan. Input realisasi per rincian, realisasi s.d. lalu, dan satuan pada tabel rincian tetap tersedia. Label metode seperti Manual/Rumus Excel/Jumlah anak di bawah target halaman Kontrak tidak ditampilkan. Angka target tidak dibulatkan ke bilangan bulat: target Margin EBITDA 1,79 tetap ditampilkan 1,79. Angka bulat ditampilkan tanpa tambahan nol desimal, misalnya 107, dan nominal besar tetap memakai pemisah ribuan.

Form Simpan, filter Cari, dan tautan tab membawa jenis PK/KPI, bulan, serta tahun yang dipilih. Setelah menyimpan KPI, pengguna tetap berada di tab KPI dengan periode yang sama. Kiriman lama tanpa parameter jenis menggunakan jenis indikator sebagai acuan. Kiriman dengan tab/indikator yang tidak sesuai atau mencampur PK dan KPI ditolak sebelum penulisan.

Menyimpan ulang indikator pada bulan yang sama memperbarui catatan periode tersebut; tidak membuat baris duplikat. Bulan lain tetap disimpan sebagai periode yang berbeda.

### Capaian saat data belum lengkap

Capaian memakai progres terhadap target acuan. Bagian pembagi dari rasio memakai target, agar pembagi realisasi yang kecil atau belum diisi tidak membuat hasil melonjak. Untuk IKK 2 pada contoh: jumlah realisasi lulusan 1.070 dibanding target lulusan 820 menghasilkan capaian 130,49%; isian realisasi pada bagian target B tidak menjadi pembagi capaian. Untuk IKK 3 yang baru melaporkan 206 dari target lulusan 1.010, capaian tetap muncul 20,40%.

Rincian yang belum pernah dilaporkan bernilai 0 hanya dalam perhitungan capaian, bukan disimpan sebagai catatan nol baru. Jika belum ada satu pun catatan realisasi, capaian mulai dari 0%, termasuk indikator dengan konstanta dasar. Jika sudah ada laporan bulan sebelumnya, nilai terakhir tetap dipakai. Target yang tidak valid atau pembagi target nol tetap diberi keterangan, bukan persentase palsu. Bobot tetap diterapkan; capaian yang melampaui target boleh lebih dari 100%.
