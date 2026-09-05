# QA LaporKita — 5 September 2026

Target: aplikasi lokal pada http://127.0.0.1:8000, diperiksa dengan Chrome DevTools.
Viewport: desktop 1440 px, tablet 768 px, dan mobile/touch 375 px.

## Hasil alur utama

| Pemeriksaan | Hasil |
|---|---|
| Halaman publik dan detail | Terbuka tanpa login; nama/email pelapor tidak muncul |
| Status nonpublik | Detail diajukan dan ditolak mengembalikan 404 |
| Pencarian dan filter | Kombinasi judul, status, kategori menampilkan hasil sesuai; kondisi kosong terbaca |
| Responsivitas | Tidak ada overflow horizontal pada halaman yang diperiksa; filter tablet dan tombol Reset tetap terlihat |
| Peta | Tile OpenStreetMap termuat, marker/popup dan tautan detail bekerja |
| Lokasi formulir | Klik peta dan drag marker memperbarui koordinat; koordinat tersimpan setelah submit |
| GPS | Jalur gagal/izin ditolak menampilkan petunjuk dan mengaktifkan kembali tombol; callback sukses diperiksa dengan koordinat simulasi |
| CAPTCHA | Jawaban salah ditolak server, input teks/koordinat dipertahankan; jawaban benar diterima |
| Duplikat | Laporan aktif serupa memunculkan peringatan; konfirmasi eksplisit diuji otomatis |
| Penyelesaian | Alur diajukan → diverifikasi → diproses → selesai berhasil; tanpa foto ditolak browser dan server; bukti tampil publik |
| Console/network | Tidak ada error/warning pada pemeriksaan ulang halaman publik; dokumen, aset, foto, dan tile mengembalikan 200 |

Dua laporan lokal berjudul **QA Browser** dibuat untuk pengujian: #5 selesai dan
#6 ditolak. Gambar berlabel QA merupakan fixture sintetis, bukan bukti kejadian
nyata. Data lama tidak dihapus, database tidak direset, dan seeder tidak dijalankan
ulang pada database lokal. Pengujian seeder memakai SQLite in-memory dan storage
palsu yang terisolasi.

## Temuan yang diperbaiki

- Filter melebar keluar layar tablet ketika tombol Reset hadir.
- Menu untuk pengunjung publik belum tersedia di header mobile.
- Input pencarian dan marker peta belum mempunyai nama aksesibel yang jelas;
  pembaruan koordinat kini memakai aria-live.
- Koordinat contoh pada seeder belum disalin ke model laporan.
- Pencarian sekarang menggunakan whereLike agar tidak membedakan kapitalisasi
  baik pada SQLite maupun PostgreSQL.
- Pembatasan 20 kandidat sebelum perhitungan jarak dapat melewatkan duplikat.
  Kandidat kini dipindai sampai maksimal tiga kecocokan jarak ditemukan; batas
  latitude memakai radius bumi yang sama dengan perhitungan jarak.

## Verifikasi akhir

- 35 automated tests, 163 assertions: lulus.
- Vite production build: berhasil.
- Pint: lulus.
- Composer validate: valid; Composer audit: tidak ada advisory keamanan.
- Route list: 29 route; Blade view cache: berhasil.
- Migration lokasi/bukti: sudah diterapkan pada database lokal.
- git diff --check: lulus.

Regresi tambahan mencakup koordinat/bukti seeder, akses laporan ditolak,
filter publik, kandidat duplikat padat, batas 150 meter, laporan lama/selesai/
ditolak/kategori berbeda, dan batas lima percobaan pengiriman per menit.

## Audit otomatis aksesibilitas

Lighthouse snapshot pada detail publik mobile memberi skor 100 untuk
Accessibility, Best Practices, SEO, dan Agentic Browsing. Terdapat satu temuan
eksperimental label-content-name-mismatch pada logo: teks dekoratif “L”
terbaca oleh audit sebagai bagian label, sementara markup memang menandainya
aria-hidden dan nama tautan adalah “LaporKita beranda”. Skor otomatis ini tidak
menggantikan pengujian pembaca layar atau audit aksesibilitas menyeluruh.

## Batas verifikasi

GPS perangkat nyata belum terverifikasi karena browser QA menolak izin lokasi;
koordinat sukses memakai simulasi callback. Pemeriksaan visual menggunakan
inspeksi saat ini; perbandingan regresi visual belum konklusif karena tidak ada
baseline screenshot. Audit aksesibilitas menyeluruh, pembaca layar, dan Core Web
Vitals belum disertifikasi oleh pemeriksaan ini.

Koneksi production Supabase dan push GitHub belum diuji karena kredensial serta
remote belum tersedia. Tidak ada deployment production pada sesi QA ini.

## Checklist ulang sebelum demo

1. Buka halaman publik pada desktop dan ponsel; coba pencarian, filter, Reset,
   hasil kosong, popup peta, dan detail bukti penyelesaian.
2. Masuk sebagai warga; isi laporan dengan foto, dua kategori, titik peta,
   dan CAPTCHA. Coba jawaban salah serta titik dekat laporan aktif.
3. Masuk sebagai petugas; jalankan seluruh transisi, coba menyelesaikan tanpa
   foto lalu dengan foto, dan cek hasil publik tanpa login.
4. Periksa console/network dan jalankan perintah verifikasi pada README.
