# LaporKita — Platform Pelaporan Permukiman & Infrastruktur

LaporKita adalah aplikasi Laravel untuk membantu warga melaporkan permukiman
kumuh, jalan rusak, drainase, sanitasi, sampah, penerangan, dan infrastruktur
publik lain yang membutuhkan penanganan. Proyek ini dibuat untuk Rekrutmen Aslab
EISD dan mendukung **SDG 11: Sustainable Cities and Communities**.

Nama repository yang ditetapkan:
`REPO_Muhammad Alvin Zufar Saputra_Rekrutmen_EISD`.

## Fitur utama

- Registrasi dan login berbasis session Laravel.
- Tiga role: `warga`, `petugas`, dan `admin`.
- Warga dapat membuat laporan dengan foto serta memilih banyak kategori.
- Warga hanya dapat melihat laporannya sendiri dan hanya dapat mengedit atau
  menghapus laporan ketika masih berstatus `diajukan`.
- Petugas dan admin dapat memproses laporan melalui alur:
  `diajukan → diverifikasi → diproses → selesai`, atau
  `diajukan → ditolak` dengan catatan wajib.
- Admin dapat mengelola kategori dan role pengguna.
- Validasi server-side, policy, middleware role, CSRF, rate limit login, flash
  message, dan validasi upload gambar.
- Relasi One-to-Many dan Many-to-Many melalui pivot `category_report`.
- Dashboard responsif untuk setiap role.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13
- PostgreSQL Supabase untuk database aplikasi
- Blade, Vite, dan Tailwind CSS 4 untuk tampilan
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

## Akun demo

Semua akun hasil seeder menggunakan kata sandi `Password123!`.

| Role | Email |
|---|---|
| Admin | `admin@laporkita.test` |
| Petugas | `petugas@laporkita.test` |
| Warga | `warga@laporkita.test` |

Ganti atau hapus akun demo sebelum deployment publik.

## Menjalankan test

```bash
php artisan test
```

Test selalu memakai SQLite `:memory:` yang ditetapkan di `phpunit.xml`, sehingga
tidak akan menghapus atau mengubah data Supabase.

Pemeriksaan kualitas yang disarankan:

```bash
vendor/bin/pint --test
composer validate
composer audit
php artisan route:list
php artisan view:cache
```

## Struktur bisnis

- `User` memiliki banyak `Report` sebagai pelapor.
- `User` memiliki banyak `Report` sebagai petugas yang menangani.
- `Report` dan `Category` memiliki relasi Many-to-Many melalui
  `category_report`.
- Validasi input berada di `app/Http/Requests`.
- Otorisasi laporan berada di `app/Policies/ReportPolicy.php`.
- Aturan perpindahan status berada di
  `app/Actions/TransitionReportStatus.php`.

Dokumentasi Use Case, Class, Activity, Sequence, state diagram, dan kecocokan
database tersedia di [docs/UML.md](docs/UML.md). Keputusan scope permanen proyek
tersimpan di [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

## Catatan deployment

- Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
- Gunakan HTTPS serta set `SESSION_SECURE_COOKIE=true`.
- Pastikan `storage` dan `bootstrap/cache` dapat ditulis server.
- Jalankan `php artisan migrate --force`, `php artisan storage:link`, dan
  `php artisan optimize` pada proses deployment.
- File unggahan saat ini menggunakan disk `public`. Untuk deployment serverless
  atau multi-instance, pindahkan disk ke object storage yang kompatibel S3 tanpa
  mengubah kontrak controller.
