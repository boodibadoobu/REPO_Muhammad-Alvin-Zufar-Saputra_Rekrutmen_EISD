# LaporKita — Platform Pelaporan Permukiman & Infrastruktur

LaporKita adalah aplikasi Laravel untuk membantu warga melaporkan permukiman
kumuh, jalan rusak, drainase, sanitasi, sampah, penerangan, dan infrastruktur
publik lain yang membutuhkan penanganan. Proyek ini dibuat untuk Rekrutmen Aslab
EISD dan mendukung **SDG 11: Sustainable Cities and Communities**.

Fokus LaporKita adalah dampak kondisi tersebut terhadap keselamatan,
aksesibilitas, dan kelayakan lingkungan. Contohnya, jalan berlubang membahayakan
pengguna, genangan menghambat akses warga, atau penerangan rusak menyulitkan
penggunaan fasilitas pada malam hari. Warga melaporkan kondisi dengan foto dan
lokasi; petugas menindaklanjuti; publik memantau progres dan dokumentasi hasilnya.

Kontribusi ini berkaitan dengan target 11.1 (permukiman dan layanan dasar),
11.7 (ruang publik yang aman dan aksesibel), serta 11.2 untuk laporan terkait
keselamatan jalan dan akses transportasi. Laporan warga juga dapat menjadi bahan
evaluasi pemeliharaan yang relevan dengan partisipasi pada target 11.3. Jumlah
laporan dan status selesai bukan pengukuran indikator resmi SDG atau bukti
independen kualitas perbaikan. Referensi: [target SDG 11 PBB](https://sdgs.un.org/goals/goal11).

Nama repository yang ditetapkan:
`REPO_Muhammad Alvin Zufar Saputra_Rekrutmen_EISD`.

## Fitur utama

- Registrasi dan login berbasis session Laravel.
- Tiga role: `warga`, `petugas`, dan `admin`.
- Warga dapat membuat laporan dengan foto serta memilih banyak kategori.
- Lokasi laporan dipilih melalui peta Leaflet: GPS perangkat, klik peta, atau
  pin yang dapat digeser.
- CAPTCHA berbasis session, rate limit, dan deteksi laporan aktif serupa dalam
  radius 150 meter membantu mengurangi spam serta duplikasi.
- Warga hanya dapat melihat laporannya sendiri dan hanya dapat mengedit atau
  menghapus laporan ketika masih berstatus `diajukan`.
- Petugas dan admin dapat memproses laporan melalui alur:
  `diajukan → diverifikasi → diproses → selesai`, atau
  `diajukan → ditolak` dengan catatan wajib.
- Admin dapat mengelola kategori dan role pengguna.
- Validasi server-side, policy, middleware role, CSRF, rate limit login, flash
  message, dan validasi upload gambar.
- Dashboard publik menampilkan laporan terverifikasi, diproses, dan selesai
  beserta peta, detail progres, serta bukti foto penyelesaian tanpa membuka
  identitas pelapor.
- Tiket hanya dapat diubah menjadi selesai setelah petugas mengunggah foto bukti
  penyelesaian.
- Relasi One-to-Many dan Many-to-Many melalui pivot `category_report`.
- Dashboard responsif untuk setiap role.

Peta publik menampilkan maksimal 200 laporan terbaru yang sesuai filter, tanpa
mengurutkan tingkat bahaya. Ringkasan jumlah per status mencakup seluruh laporan
publik dan tidak mengikuti filter daftar. Heatmap, analitik perencanaan, SLA,
anggaran, serta vendor/work order merupakan arah pengembangan, bukan fitur rilis
rekrutmen ini. Kampus dapat menjadi contoh penerapan tanpa membatasi cakupan
permukiman dan infrastruktur lingkungan.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13
- PostgreSQL Supabase untuk database aplikasi
- Blade, Vite, Tailwind CSS 4, Leaflet 1.9, dan tile OpenStreetMap untuk tampilan
- PHPUnit dengan SQLite in-memory untuk automated test

Tidak ada package admin atau generator CRUD otomatis seperti Filament.

## Instalasi

```bash
git clone <URL_REPOSITORY>
cd "REPO_Muhammad Alvin Zufar Saputra_Rekrutmen_EISD"
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Pada PowerShell, salin environment dengan:

```powershell
Copy-Item .env.example .env
```

## Menghubungkan Supabase

1. Buat project di Supabase.
2. Buka **Project Settings → Database** atau tombol **Connect**.
3. Pilih koneksi **Session pooler** agar dapat digunakan dari jaringan IPv4.
4. Salin parameter koneksi ke `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=your-project.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=your-supabase-database-password
DB_SSLMODE=require
```

Jangan commit `.env` atau password Supabase ke repository. Autentikasi pengguna
tetap dikelola Laravel; Supabase hanya bertindak sebagai PostgreSQL terkelola.

Setelah koneksi siap:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Dashboard transparansi tersedia tanpa login di
`http://127.0.0.1:8000/laporan-publik`.

Fitur lokasi perangkat membutuhkan izin geolocation dari browser. Gunakan
localhost saat pengembangan dan HTTPS saat deployment. Jika GPS ditolak, warga
tetap dapat memilih lokasi dengan klik atau menggeser pin pada peta.

## Akun demo

Semua akun hasil seeder menggunakan kata sandi `Password123!`.

| Role | Email |
|---|---|
| Admin | `admin@laporkita.test` |
| Petugas | `petugas@laporkita.test` |
| Warga 1 | `warga@laporkita.test` |
| Warga 2 | `warga2@laporkita.test` |
| Warga 3 | `warga3@laporkita.test` |

Seeder membuat 5 akun, 6 kategori, dan 9 laporan: masing-masing 3 diverifikasi,
ditolak, dan selesai. Setiap warga memiliki satu laporan untuk setiap status.
Foto laporan dan bukti penyelesaian adalah ilustrasi demo pada disk public
aplikasi, bukan file yang disimpan di PostgreSQL Supabase.

Jalankan ulang dengan `php artisan db:seed --force`. Seeder hanya menambahkan
entri yang belum ada; akun dicocokkan berdasarkan email, kategori berdasarkan
slug, dan laporan berdasarkan `demo_key` unik yang tidak berubah saat laporan
diedit. Password, status, kategori laporan, dan perubahan data yang sudah ada
tidak ditimpa. Jika role akun dengan email demo bertabrakan, transaksi dibatalkan.
Database yang sudah berisi data dapat memiliki jumlah total lebih besar.
Hasil eksekusi dan verifikasi Supabase tersedia di [docs/SUPABASE_SEED.md](docs/SUPABASE_SEED.md).

Ganti atau hapus akun demo sebelum deployment publik.

## Menjalankan test

```bash
php artisan test
```

Test selalu memakai SQLite `:memory:` yang ditetapkan di `phpunit.xml`, sehingga
tidak akan menghapus atau mengubah data Supabase.

Pemeriksaan kualitas yang disarankan:

```bash
npm run build
vendor/bin/pint --test
composer validate
composer audit
php artisan route:list
php artisan view:cache
git diff --check
```

Hasil QA browser, batas verifikasi, dan checklist demo tersedia di
[docs/QA.md](docs/QA.md).

## Skenario demonstrasi

Gunakan kasus ilustratif jalan lingkungan berlubang yang mengganggu akses warga.
Untuk latihan yang membuat atau mengubah laporan, gunakan lingkungan QA lokal
terisolasi; jangan menjalankan reset database atau seeding destruktif di Supabase.

1. Buka beranda dan laporan publik tanpa login; tunjukkan lokasi, filter, dan
   batas cakupan peta.
2. Masuk sebagai warga. Isi kondisi, dampak bagi pengguna fasilitas, foto fixture,
   kategori, titik peta, dan CAPTCHA. Data fixture harus dinyatakan sebagai demo.
3. Masuk sebagai petugas dan verifikasi laporan; periksa bahwa laporan kini
   terlihat publik, sementara identitas akun warga tidak ditampilkan.
4. Ubah status menjadi diproses. Coba menyelesaikan tanpa foto, lalu unggah foto
   bukti penyelesaian yang juga berlabel demo.
5. Buka detail publik untuk menunjukkan progres dan dokumentasi hasil. Foto
   selesai merupakan bukti yang diunggah petugas, bukan konfirmasi warga.
6. Periksa bahwa warga lain tidak dapat membuka detail internal laporan tersebut,
   petugas tidak dapat mengelola pengguna, dan admin dapat mengakses pengelolaan.

Pemeriksaan metadata foto dilakukan melalui automated test/pemeriksaan file;
browser QA memeriksa tampilan, validasi, dan pengalaman ketiga jalur unggahan.

## Struktur bisnis

- `User` memiliki banyak `Report` sebagai pelapor.
- `User` memiliki banyak `Report` sebagai petugas yang menangani.
- `Report` dan `Category` memiliki relasi Many-to-Many melalui
  `category_report`.
- Validasi input berada di `app/Http/Requests`.
- Otorisasi laporan berada di `app/Policies/ReportPolicy.php`.
- Aturan perpindahan status berada di
  `app/Actions/TransitionReportStatus.php`.
- Deteksi laporan serupa berada di
  `app/Actions/FindPotentialDuplicateReports.php`. Kandidat duplikat adalah
  laporan aktif dengan minimal satu kategori sama, berjarak maksimal 150 meter,
  dan dibuat dalam 30 hari terakhir. Warga dapat melanjutkan setelah memberi
  konfirmasi eksplisit agar kondisi yang memang berbeda tidak terblokir.

Dokumentasi Use Case, Class, Activity, Sequence, state diagram, dan kecocokan
database tersedia di [docs/UML.md](docs/UML.md). Keputusan scope permanen proyek
tersimpan di [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

## Catatan deployment

- Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
- Gunakan HTTPS serta set `SESSION_SECURE_COOKIE=true`.
- Pastikan server mengizinkan koneksi keluar ke tile OpenStreetMap dan tampilkan
  atribusi OpenStreetMap yang sudah disediakan pada peta.
- Pastikan `storage` dan `bootstrap/cache` dapat ditulis server.
- Jalankan `php artisan migrate --force`, `php artisan storage:link`, dan
  `php artisan optimize` pada proses deployment.
- File unggahan saat ini menggunakan disk `public`. Untuk deployment serverless
  atau multi-instance, pindahkan disk ke object storage yang kompatibel S3 tanpa
  mengubah kontrak controller.

## Privasi foto unggahan

Semua foto baru, penggantian foto, dan bukti penyelesaian diproses oleh
`StoreSanitizedPhoto` menggunakan Intervention Image 3 dan driver GD sebelum
disimpan. Aktifkan ekstensi PHP `gd` dan `exif` sebelum `composer install`;
GD harus mendukung JPEG, PNG, dan WebP. Restart server PHP setelah mengaktifkannya.

Orientasi JPEG dibetulkan berdasarkan EXIF, kemudian gambar di-encode ulang tanpa
metadata asli (termasuk EXIF GPS, perangkat, komentar, dan XMP). Format dipertahankan;
JPEG/WebP dapat mengalami perubahan kualitas akibat encoding ulang. Animasi tidak
dipertahankan. Upload dibatasi 2 MB dan 8 megapiksel untuk membatasi memori decode.
Foto rusak atau gagal diproses ditolak, tanpa menyimpan original sebagai fallback.
Foto demo dari seeder tidak diproses ulang. Koordinat pilihan pada peta dan isi
visual foto tidak dihapus oleh pembersihan metadata.

### Batas deployment Vercel

PHP di Vercel memakai runtime komunitas `vercel-php`. Daftar ekstensi bawaannya
tidak mencantumkan GD/Imagick; deployment memerlukan runtime dengan GD, EXIF, dan
dukungan JPEG/PNG/WebP. Jangan mengabaikan persyaratan ekstensi Composer.
Kompatibilitas deployment Vercel belum diuji. Penyimpanan foto juga harus memakai
object storage permanen sebelum deployment serverless; disk public lokal saat ini
belum memenuhi kebutuhan tersebut. Referensi: [Vercel runtimes](https://vercel.com/docs/functions/runtimes)
dan [PHP runtime](https://github.com/vercel-community/php).
