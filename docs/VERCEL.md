# Deploy PleaseFix ke Vercel

Konfigurasi repository memakai vercel-php 0.9.0 (PHP 8.5), entrypoint
api/index.php, dan aset statis dari public. Runtime memasang dependensi Composer.
Script vercel memeriksa platform serta GD/EXIF; deployment gagal dengan pesan
jelas jika runtime tidak memenuhi kebutuhan. Belum diverifikasi pada deployment
Vercel pengguna. Jangan menggunakan runtime 0.7.x untuk composer.lock ini.

## 1. Storage foto di Supabase

Di project Supabase yang sama, buat bucket public bernama pleasefix.
Gunakan public read untuk URL foto; jangan tambahkan izin anonymous upload/delete.
Di Storage Settings, buat S3 access key khusus server dan catat endpoint, region,
access key ID, dan secret access key. Ini berbeda dari anon key dan password DB.
Jangan masukkan rahasia ke Git atau variabel VITE_*.

Upload isi storage/app/public lokal ke bucket dengan path relatif yang sama,
termasuk demo/laporan.svg dan demo/penyelesaian.svg jika database memakai seeder.
Database PostgreSQL hanya menyimpan path foto, bukan isi filenya.
Tidak perlu menjalankan seeder ulang atau storage:link di Vercel.

## 2. Environment Variables Vercel

Tambahkan variabel berikut di Settings > Environment Variables. Ganti placeholder
dengan nilai project sendiri, dan pilih environment deployment yang sesuai.

```dotenv
APP_NAME=PleaseFix
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN-PRODUKSI.vercel.app
APP_KEY=base64:KUNCI-APLIKASI-YANG-SUDAH-ADA
LOG_CHANNEL=stderr
LOG_LEVEL=warning
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
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
PUBLIC_DISK_DRIVER=s3
AWS_ACCESS_KEY_ID=S3-ACCESS-KEY-ID
AWS_SECRET_ACCESS_KEY=S3-SECRET-ACCESS-KEY
AWS_DEFAULT_REGION=REGION-DARI-STORAGE-SETTINGS
AWS_BUCKET=pleasefix
AWS_ENDPOINT=https://PROJECT-REF.storage.supabase.co/storage/v1/s3
AWS_URL=https://PROJECT-REF.supabase.co/storage/v1/object/public/pleasefix
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Salin endpoint dari dashboard Supabase jika berbeda dari contoh. Biarkan
SESSION_DOMAIN tidak diisi. APP_URL harus sesuai domain yang digunakan login.
Jangan membuat APP_KEY baru pada setiap build. Pertahankan APP_KEY existing.

Session dan cache memakai database; pastikan tabel migrasi aplikasi sudah ada.
Jika migrasi tertinggal, backup lalu jalankan php artisan migrate --force dari
lingkungan tepercaya yang sudah dikonfigurasi ke database target. Build Vercel
tidak menjalankan migrasi atau seeding secara otomatis.

## 3. Pengaturan build dan push manual

Push sendiri perubahan repository, termasuk composer.lock, api, scripts,
vercel.json, .vercelignore, config/filesystems.php, dan bootstrap/app.php.

Pada Settings > Build and Deployment:

- Framework Preset: Other.
- Root Directory: root repository (biarkan kosong jika aplikasi di root).
- Node.js: 22.x, sesuai runtime komunitas.
- Hapus override build/install/output lama agar vercel.json dipakai.
- Nilai efektif: Install npm ci, Build npm run build, Output public.

Output sekarang public, bukan public/build atau dist. Routes dalam vercel.json
mengirim halaman aplikasi ke function PHP dan aset ke jalur statisnya.
Redeploy setelah environment variables disimpan. Log harus memperlihatkan
instalasi Composer dan pesan PleaseFix runtime ... OK, bukan hanya vite build.

## 4. Verifikasi

1. Buka /up, /, /login, /laporan-publik dan satu aset /build/assets/....
2. Login, pindah halaman, dan pastikan session tetap ada.
3. Unggah laporan uji yang jelas berlabel demo; pastikan objek baru ada di bucket.
4. Uji alur petugas hingga selesai beserta foto bukti, serta penggantian foto.
5. Redeploy dan pastikan foto tetap terbaca.
6. Pastikan nama pelapor tidak muncul di halaman publik.

Jika 404 Vercel: pastikan commit yang dideploy berisi vercel.json dan api/index.php,
output public, dan build menghasilkan function PHP. Jika 500: buka Runtime Logs
Vercel; kirim pesan error yang sudah disensor dari rahasia. Jika gagal runtime
check, jangan menghapus check atau mengabaikan ekstensi.

Folder /tmp dapat bertahan pada instance hangat, tetapi tidak dijamin permanen
atau dibagi antar-instance. Hanya cache Blade/discovery sementara berada di sana.
Foto berada di Supabase Storage, session/cache aplikasi berada di PostgreSQL.

Referensi:
- https://github.com/vercel-community/php
- https://supabase.com/docs/guides/storage/s3/authentication
- https://supabase.com/docs/guides/storage/s3/compatibility
