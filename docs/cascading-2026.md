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

## Cara mengisi form baru: satu form, satu kali Simpan

Modal tetap berukuran biasa dan seluruhnya dapat digulir hingga tombol Simpan. Form memuat induk, semua anak/cucu, target sumber, satuan, serta bobot sekaligus.

1. Klik Tambah Sub pada sasaran untuk membuat IKK, atau Edit pada IKK yang sudah ada.
2. Pilih **Pola pengisian**. Tersedia persentase dua nilai, jumlah rincian, gabungan berbobot, serta pola siap pakai IKK 4/5/14/16/24. Nama dan kode diisi otomatis jika masih kosong; tetap dapat diedit. Pada data yang sudah ada, pola mencocokkan kode rincian dan melengkapi baris yang belum ada, sambil mempertahankan ID, target, satuan, serta rincian tersimpan. Rincian tambahan di luar pola tetap ditampilkan; pada pola penjumlahan/berbobot, rincian tersebut tidak dimasukkan ke hasil pola.
3. Isi angka sumber langsung pada rincian yang muncul. Rumus/pengali dan hubungan sumber dalam pola sudah terpasang; tidak perlu menyimpan induk lalu membuka anak satu per satu.
4. Periksa pratinjau hasil induk, lalu klik **Simpan** sekali. Semua tingkat disimpan sebagai satu transaksi.
5. Untuk mengubah data lama, edit induk dan ubah seluruh rincian di form yang sama. ID baris serta realisasi lama tetap dipertahankan. Form ini tidak menghapus rincian tersimpan; sumber rumus hanya boleh berasal dari anak langsungnya.

| Pola | Angka yang perlu diisi | Hasil contoh |
|---|---|---|
| IKK 4 — Akreditasi | Akreditasi 1; prodi terakreditasi 2; seluruh prodi 3 | 86,67% |
| IKK 14 — Kualitas dosen | 55; 100; 80,95 | 78,19% (bobot 40/40/20 sudah disiapkan) |
| IKK 5 — Peningkatan SDM | 91,45; 95,57 | 93,92% |
| IKK 16 — Kerja sama | 17; 19; 13; 14 | 90,83% |
| IKK 24 — Nilai dasar + layanan | Layanan 5; target layanan 6 | 91,67% (nilai dasar 50 sesuai formula Excel) |
| Persentase dua nilai | Pembilang 18; pembagi 21 | 85,71% |

### Struktur lain

- Pilih **Isi sendiri / struktur yang ada**. Pada **Cara menghitung induk**, pilih manual, jumlah, rasio, gabungan berbobot, nilai × pengali, atau nilai dasar + persentase.
- Tombol **+ Tambah rincian** menambah anak langsung di dalam form. Tombol **+ Rincian di bawah ini** menambah cucu pada bagian yang bersangkutan. Baris baru boleh dihapus sebelum disimpan.
- Pada gabungan berbobot, bobot diisi di sebelah target/satuan anak. Bobot 40% diisi 40, bukan 0,4.
- Untuk kelompok seperti Bobot 60% di IKK 4, pola sudah mengatur pengali 60 atas nilai dokumen 1; Bobot 40% memakai rasio 2/3 × 40. Pada IKK 5, pengali terhadap nilai persen adalah 0,4 dan 0,6. Jangan menyamakan dua operasi ini.
- Nama, kode, satuan kelompok, dan rumus rincian dapat diubah melalui **Ubah rincian / rumus** pada bagian itu. Isian ini tertutup supaya pengisian angka tetap ringkas. Pengali dan pemilihan sumber hanya perlu dibuka untuk susunan yang berbeda dari pola.
- Target persen diisi 91,45 atau 91.45, bukan 0,9145. Angka besar diisi tanpa pemisah ribuan, misalnya 15450000000. Satuan setiap baris tetap mengikuti isian sendiri.
- Rumus boleh disimpan sebagai susunan yang belum lengkap. Nilai sumber kosong, pembagi nol, atau bobot yang belum diisi menghasilkan — dengan petunjuk, bukan nol palsu. Nol yang benar tetap dihitung.
- Pada realisasi, isi sumber manual yang dipakai rumus. Hasil induk dihitung dari sumber tersebut; capaian adalah hasil realisasi ÷ hasil target × 100.
- Data lama yang belum diedit memakai profil Excel 2026. Ketika disimpan melalui form lengkap, metode dan sumber yang ditampilkan disimpan eksplisit memakai ID. Mengganti kode/nama kemudian tidak mengganti rumus.

### IKK 24

Pola baru langsung membuat dua rincian layanan yang dipakai rumus, berkode 3 dan 4. Pada data lama, rincian unit 1 dan 2 (0/0) tetap dipertahankan, tetapi tidak ikut dihitung karena formula Excel memang hanya memakai layanan. Nilai dasar 50 masih perlu konfirmasi kebijakan sebagaimana dijelaskan berikut.

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

Form lengkap juga diuji melalui HTTP untuk seluruh 34 indikator yang sudah ada, penyimpanan atomik, penolakan sumber di luar cabang, dan pemeliharaan ID/realisasi. Browser memeriksa pembuatan IKK 4/5/14/16/24 dalam satu kali Simpan, penerapan pola pada struktur lama yang belum lengkap, serta scroll dan penyimpanan di ponsel.
