# Verifikasi seeding Supabase

Migration Laravel dan DatabaseSeeder berhasil dijalankan pada database postgres
melalui Session pooler. Tidak ada reset database atau penghapusan data.

## Data terverifikasi

| Data | Jumlah |
|---|---:|
| Admin | 1 |
| Petugas | 1 |
| Warga | 3 |
| Laporan diverifikasi | 3 |
| Laporan ditolak | 3 |
| Laporan selesai | 3 |
| Kategori | 6 |
| Relasi kategori/laporan pada pivot | 15 |

Setiap warga memiliki tiga laporan, satu untuk masing-masing status.
Password demo cocok dengan hash Laravel. Pelapor, petugas, kategori, koordinat,
alasan penolakan, dan berkas ilustrasi bukti selesai diperiksa.

Seeder dijalankan dua kali di Supabase. Snapshot seluruh baris users, categories,
reports, dan category_report sebelum/sesudah pengulangan identik. Test lokal
juga membuktikan perubahan nama/password akun, judul/status/foto/kategori laporan,
serta laporan tambahan tetap dipertahankan.

## Konfigurasi dan berkas

Website lokal kini menggunakan PostgreSQL Supabase sebagai koneksi utama melalui
Session pooler, dengan SSL diwajibkan. Konfigurasi disimpan pada .env yang diabaikan
Git. Data SQLite lama tetap tersimpan. PHPUnit memaksa koneksi SQLite in-memory
untuk test, terpisah dari database utama.

Verifikasi browser: halaman publik menampilkan enam laporan (tiga diverifikasi
dan tiga selesai). Login warga2@laporkita.test berhasil dan daftar laporan
menampilkan tepat tiga laporan miliknya, termasuk laporan ditolak.

Ilustrasi tersimpan pada disk public Laravel sebagai demo/laporan.svg dan
demo/penyelesaian.svg. File tidak disimpan pada PostgreSQL. Server aplikasi lain
perlu menjalankan seeder dan storage:link untuk menyediakan berkas yang sama.
Kredensial database dan secret key API tidak disimpan dalam repository.
Pengaturan RLS tidak diubah pada pekerjaan ini.

## Pemeriksaan proyek

- 36 test / 255 assertion lulus menggunakan SQLite in-memory.
- Build Vite, Pint, Composer validate/audit, route list, cache Blade, dan
  git diff --check berhasil.

Daftar email demo dan password tersedia di bagian Akun demo pada README.
