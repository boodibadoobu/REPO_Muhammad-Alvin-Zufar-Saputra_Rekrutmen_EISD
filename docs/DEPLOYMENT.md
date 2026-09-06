# Deployment PleaseFix pada hosting PHP

Panduan ini untuk satu server PHP/hosting Laravel dengan filesystem permanen.
Ini bukan konfigurasi Vercel dan belum diuji pada server hosting pengguna.

## Persyaratan

- PHP 8.4.1 atau lebih baru yang cocok dengan composer.lock (Symfony membutuhkan >=8.4.1).
- Composer 2, ekstensi Laravel, pdo_pgsql, GD (JPEG/PNG/WebP), dan EXIF.
- HTTPS, akses SSH/terminal, akses keluar ke Supabase Session pooler.
- Penyimpanan storage/app/public harus bertahan saat restart dan deploy berikutnya.
- Document root harus menunjuk folder public di dalam proyek, bukan public/build
  dan bukan root proyek. Server harus meneruskan route aplikasi ke public/index.php.

## Langkah deployment

1. Upload/clone kode proyek ke direktori aplikasi di hosting. Jangan upload .env
   lokal, node_modules, public/hot, atau cache konfigurasi dari komputer lokal.
2. Pilih PHP 8.4.1+ untuk web server DAN CLI, aktifkan ekstensi di atas.
3. Di terminal hosting, masuk ke direktori aplikasi:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

4. Buat .env dari .env.example hanya jika .env belum ada. Edit nilainya lewat
   editor hosting. Gunakan konfigurasi ini ditambah kredensial Supabase milikmu:

```dotenv
APP_NAME=PleaseFix
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN-HOSTING-KAMU
APP_KEY=
DB_CONNECTION=pgsql
DB_HOST=HOST-SESSION-POOLER-SUPABASE
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.PROJECT-REF
DB_PASSWORD=PASSWORD-DATABASE
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```

   Untuk instalasi baru dengan APP_KEY kosong jalankan php artisan key:generate.
   Jika instalasi sudah punya APP_KEY, pertahankan nilainya. Jangan kirim .env
   atau kredensial ke GitHub/chat.

5. Build aset (Node 22.12+). Bisa dilakukan di komputer lokal jika hosting tidak
   menyediakan Node, lalu upload seluruh folder public/build ke lokasi yang sama:

```sh
npm ci
npm run build
```

6. Beri user web server akses tulis ke storage dan bootstrap/cache menggunakan
   pengaturan ownership hosting. Jangan gunakan permission 777.
7. Backup database sebelum menjalankan migrasi produksi. Periksa target Supabase,
   kemudian jalankan:

```sh
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

   Jangan jalankan migrate:fresh, db:wipe, atau seeding ulang untuk deployment.
   Jika database sudah berisi path foto, salin file foto existing ke
   storage/app/public dengan path relatif yang sama. Supabase PostgreSQL tidak
   menyimpan file foto tersebut. Simpan direktori ini antar deployment.

8. Hubungkan domain hosting dengan document root public dan aktifkan HTTPS.
   Domain *.vercel.app bukan domain yang bisa dipindahkan ke hosting ini;
   gunakan URL bawaan hosting atau domain milik sendiri.

## Verifikasi di hosting

- Buka /up, /, /login, dan /laporan-publik.
- Login dengan akun yang sudah ada; periksa session setelah berpindah halaman.
- Buat laporan uji berlabel demo, unggah foto, dan buka kembali fotonya.
- Uji verifikasi, pemrosesan, dan unggah bukti penyelesaian dengan role petugas.
- Pastikan foto tetap ada setelah restart/redeploy.
- Jika HTTP 500, periksa storage/logs/laravel.log secara privat; APP_DEBUG tetap false.
- Jika halaman utama tampil tetapi /login 404, perbaiki rewrite/front controller
  web server. Jangan mengubah route Laravel untuk menutupi konfigurasi server.

## Jika tetap memakai Vercel

Mengubah Output Directory ke public/build hanya memublikasikan aset Vite.
Deployment penuh membutuhkan runtime PHP + Composer, entrypoint function,
routing, cache sementara yang writable, dan penyimpanan foto object storage.
Konfigurasi Vercel tersedia: ikuti docs/VERCEL.md. Pemeriksaan paket PHP 8.5
@libphp/almalinux-9-v85@0.0.3 menemukan GD dan EXIF diaktifkan; build proyek
memeriksa ekstensi dan codec yang benar-benar tersedia.
Jangan mengabaikan ext-gd dengan --ignore-platform-reqs: pemrosesan foto aplikasi
memerlukan GD saat runtime. Jalur ini memerlukan runtime kompatibel GD yang
diverifikasi serta perubahan integrasi storage sebelum aplikasi siap digunakan.

Referensi:
- https://laravel.com/docs/13.x/deployment
- https://github.com/vercel-community/php
