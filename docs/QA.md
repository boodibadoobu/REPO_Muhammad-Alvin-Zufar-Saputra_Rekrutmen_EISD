# QA LaporKita

## Verifikasi terbaru — 6 September 2026 (WIB)

Scope: penajaman narasi keselamatan/aksesibilitas, panduan deskripsi, kejelasan
statistik dan peta, serta tombol beranda sesuai policy pembuatan laporan.
Tidak ada perubahan skema, kategori, atau workflow bisnis.

### Lingkungan dan isolasi

- Automated test: SQLite `:memory:` sesuai `phpunit.xml`; 41 tests dan
  318 assertions lulus. Test baru memeriksa tautan beranda untuk tamu, warga,
  petugas, dan admin, beserta penolakan endpoint pembuatan bagi petugas/admin.
- Browser QA: Chrome DevTools pada `http://127.0.0.1:8016`, memakai runtime
  sementara di `storage/app/qa-focus-20260906` yang diabaikan Git. Runtime tidak
  membaca `.env` utama; koneksi yang tersedia hanya SQLite QA. Database,
  upload, cache/sesi database, dan compiled view terpisah dari aplikasi utama.
- Database QA baru diberi sembilan laporan demo dengan seeder yang ada, kemudian
  satu laporan `[QA 6 Sep]` dibuat melalui browser dan diselesaikan (#10).
  Tidak ada reset, seeding, atau penulisan data QA ke Supabase.
- Semua gambar dan kejadian QA bersifat sintetis. Pemilihan titik melalui klik
  peta menguji penyimpanan koordinat, bukan ketepatan lokasi kejadian nyata.

### Hasil pemeriksaan

| Pemeriksaan | Hasil |
|---|---|
| Beranda dan hak akses | Tamu mendapat tautan registrasi; petugas/admin mendapat Tinjau laporan; endpoint create petugas/admin 403; admin users 200 untuk admin dan 403 untuk petugas |
| Warga lain | Detail internal laporan #10 mengembalikan 403 untuk warga kedua |
| Pengajuan dan CAPTCHA | Jawaban salah menampilkan error serta mempertahankan teks/kategori/koordinat; jawaban benar membuat laporan dengan dua kategori dan foto |
| Privasi sebelum verifikasi | Detail publik laporan diajukan mengembalikan 404; setelah diverifikasi mengembalikan 200 |
| Penggantian foto | File JPEG pengganti diterima; PNG 8,4 megapiksel ditolak dengan error pemrosesan |
| Penyelesaian | Transisi diajukan → diverifikasi → diproses → selesai berhasil; browser menolak bukti kosong; PNG 8,4 megapiksel ditolak server dan status tetap diproses tanpa bukti |
| Sanitasi tiga jalur | JPEG sintetis berorientasi EXIF 6 dan tag Make diproses pada create, replacement, dan resolution; hasil 120×240 dari input 240×120 tanpa Exif/tag PRIVATE-GPS |
| Detail publik selesai | Foto laporan dan bukti penyelesaian termuat; nama/email akun warga tidak muncul pada halaman |
| Filter | Pencarian + status selesai + kategori Jalan Rusak menghasilkan satu kartu dan satu marker; total status tetap 3/0/4, sesuai cakupan global yang dijelaskan |
| Reset dan hasil kosong | Reset kembali ke tujuh laporan publik; pencarian tanpa hasil menampilkan pesan kosong tanpa panel peta |
| Peta | Popup marker menampilkan judul, status, dan tautan detail; tile OpenStreetMap termuat |
| Responsivitas | Tidak ada overflow horizontal pada beranda 1440/375 px, daftar publik 375/768 px, detail publik 768 px, dan form 375 px yang diperiksa |
| Console/network | Detail publik yang diperiksa tidak memiliki console warning/error; dokumen, aset, dua foto, serta tile menerima HTTP 200 |

### Gate README

- `php artisan test`: 41 tests, 318 assertions, lulus.
- `npm run build`: berhasil; ada informasi timing plugin Vite, bukan kegagalan.
- `vendor/bin/pint --test`: lulus.
- `composer validate`: valid; `composer audit`: tidak ada advisory.
- `php artisan route:list`: 29 route; `php artisan view:cache`: berhasil.
- `git diff --check`: lulus.

### Batas dan tindak lanjut

- Tool upload file menolak path lokal karena batas workspace tool. Gambar uji
  kemudian dibuat di browser dan dimasukkan sebagai `File` sintetis ke input;
  pengiriman memakai form aplikasi. Pemilih file OS belum terverifikasi ulang.
- Interaksi select native pada emulasi mobile mengalami timeout tool. Filter
  gabungan diverifikasi dengan mengisi select melalui DOM dan mengirim form;
  pencarian, tombol Terapkan/Reset, serta popup juga dioperasikan melalui UI.
- EXIF GPS, metadata PNG/WebP, dan pelestarian foto/status saat penolakan tetap
  dicakup automated test; browser QA bukan audit aksesibilitas menyeluruh atau
  pengujian GPS perangkat nyata. Viewport mobile/tablet adalah emulasi.
- Riwayat QA di bawah dipertahankan sebagai hasil sesi terdahulu. Catatan lama
  tentang koneksi Supabase bukan status terkini; verifikasi koneksi/seeder
  terdahulu tercatat di [SUPABASE_SEED.md](SUPABASE_SEED.md), tidak diulang di sesi ini.
- Tidak ada remote Git terkonfigurasi saat diperiksa; push belum dilakukan.
  Hosting, URL publik, dan storage deployment belum diputuskan/diverifikasi.

## Riwayat QA — 5 September 2026

Target: aplikasi lokal pada http://127.0.0.1:8000, diperiksa dengan Chrome DevTools.
Viewport: desktop 1440 px, tablet 768 px, dan mobile/touch 375 px.

### Hasil alur utama

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

### Temuan yang diperbaiki

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

### Verifikasi akhir

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

### Audit otomatis aksesibilitas

Lighthouse snapshot pada detail publik mobile memberi skor 100 untuk
Accessibility, Best Practices, SEO, dan Agentic Browsing. Terdapat satu temuan
eksperimental label-content-name-mismatch pada logo: teks dekoratif “L”
terbaca oleh audit sebagai bagian label, sementara markup memang menandainya
aria-hidden dan nama tautan adalah “LaporKita beranda”. Skor otomatis ini tidak
menggantikan pengujian pembaca layar atau audit aksesibilitas menyeluruh.

### Batas verifikasi

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
