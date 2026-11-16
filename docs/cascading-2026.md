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

## Cara mengisi form baru

Form menggunakan modal ukuran biasa yang dapat digulir sampai tombol Simpan. Isian dasar hanya nama, kode, target, dan satuan. **Atur perhitungan (opsional)** tertutup saat form dibuka; pengaturan yang sudah ada tetap tersimpan meskipun bagian ini tidak dibuka. Untuk indikator yang sudah otomatis, cukup ubah nilai pada anak sumber; hasil induk dihitung kembali.

1. Buat indikator dan tentukan satuannya sendiri. Target manual bisa diisi di tingkat IKK sekalipun. Isi persen 91,45 (atau 91.45), bukan 0,9145. Angka tidak memakai pemisah ribuan: 15450000000.
2. **Gabungan berbobot dapat dipilih sejak membuat induk, sebelum anak ada.** Simpan tanpa target sementara, tambahkan anak, lalu edit induk untuk mengisi bobot. Selama anak/bobot belum lengkap hasil ditampilkan — dengan petunjuk; metode tetap tersimpan. Untuk metode rasio dan pengali, buat anak sumbernya terlebih dahulu. Setelah itu edit induk, buka **Atur perhitungan (opsional)**, dan pilih metode. Memilih Manual membuat nilai induk tetap walaupun ada anak keterangan.
3. **Jumlah anak** menjumlahkan anak langsung, tidak menjumlahkan anak dan cucu dua kali.
4. **Rasio** meminta pembilang, pembagi, dan pengali. Contoh IKK 10 pilih sumber 18 dan 21, pengali 100.
5. **Nilai satu anak × pengali**: untuk bobot 40% atas persentase 91,45 gunakan pengali 0,4. Untuk akreditasi 1 × 60 gunakan pengali 60. Keduanya berbeda.
6. **Jumlah berbobot**: isi bobot persentase tiap anak. IKK 14 memakai 40, 40, 20. Bobot 0 mengabaikan sumber.
7. **Nilai dasar + rasio**: IKK 24 sesuai rumus Excel memakai nilai dasar 50, sumber layanan 5 dan 6, pengali 50.
8. Pratinjau memakai target sumber saat ini. Pengaturan eksplisit tersimpan memakai ID anak, sehingga mengganti nama/kode tidak mengubah rumus. Simpan kemudian periksa hasil di tabel.
9. Data lama tanpa pengaturan memakai **Profil Excel 2026 (data lama)** agar target tidak perlu diisi ulang. Kode profil mengikuti tabel ini, termasuk anak A/B atau 1/2. Pilih metode eksplisit untuk struktur lain atau tahun berikutnya.
10. Satuan tidak menentukan operasi matematika. Isian nilai/persen/dokumen tetap dipertahankan pada masing-masing sub, termasuk yang memiliki bobot. Simbol % hanya untuk satuan persen.
11. Pembagi nol atau sumber yang hilang menghasilkan tanda — dan pesan penjelasan, bukan hasil 0 palsu. Nol yang benar tetap dihitung. Nilai manual kosong tidak dianggap nol.
12. Di Realisasi, isi sumber manual yang dipakai rumus; jangan mengisi hasil induk lagi. Input bulan berjalan mempertahankan mekanisme nilai terakhir dari bulan sebelumnya. Sumber bertarget 0 dapat diisi. Hasil realisasi memakai rumus yang sama; capaian dibedakan dari hasil indikator.

### Struktur IKK 24 pada profil lama

- Anak 1: jumlah unit = 0 (tidak dipakai rumus Excel).
- Anak 2: target unit = 0 (tidak dipakai rumus Excel).
- Anak 3: jumlah layanan = 5 (pembilang).
- Anak 4: target layanan = 6 (pembagi).
- Jika kode anak berbeda, pilih metode eksplisit dan pilih sumber layanan yang benar, bukan mengganti datanya.

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
