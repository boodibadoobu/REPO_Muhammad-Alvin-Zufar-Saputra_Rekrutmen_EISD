<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $disk = Storage::disk('public');
        foreach (['demo/laporan.svg', 'demo/penyelesaian.svg'] as $path) {
            if (! $disk->exists($path) && ! $disk->put($path, file_get_contents(public_path('images/report-placeholder.svg')))) {
                throw new RuntimeException('Gagal menyimpan ilustrasi laporan demo.');
            }
        }

        DB::transaction(function (): void {
            $accounts = [
                ['admin@laporkita.test', 'Admin LaporKita', UserRole::Admin],
                ['petugas@laporkita.test', 'Petugas Lapangan', UserRole::Petugas],
                ['warga@laporkita.test', 'Warga Demo Satu', UserRole::Warga],
                ['warga2@laporkita.test', 'Warga Demo Dua', UserRole::Warga],
                ['warga3@laporkita.test', 'Warga Demo Tiga', UserRole::Warga],
            ];
            $users = collect($accounts)->mapWithKeys(function (array $account): array {
                [$email, $name, $role] = $account;
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name, 'password' => 'Password123!', 'role' => $role,
                ]);
                if ($user->role !== $role) {
                    throw new RuntimeException('Role akun demo bertabrakan: '.$email);
                }

                return [$email => $user];
            });

            $categories = collect([
                ['jalan-rusak', 'Jalan Rusak', 'Jalan berlubang, retak, atau membahayakan pengguna.'],
                ['drainase', 'Drainase', 'Saluran air tersumbat, rusak, atau memicu genangan.'],
                ['sanitasi', 'Sanitasi', 'Masalah air bersih, limbah, dan fasilitas sanitasi.'],
                ['sampah', 'Sampah', 'Penumpukan sampah dan sarana pengelolaan yang tidak memadai.'],
                ['penerangan', 'Penerangan', 'Lampu jalan mati atau area publik minim penerangan.'],
                ['bangunan-tidak-layak', 'Bangunan Tidak Layak', 'Hunian atau fasilitas umum yang tidak aman dan tidak layak.'],
            ])->mapWithKeys(fn (array $data): array => [$data[0] => Category::firstOrCreate(
                ['slug' => $data[0]], ['name' => $data[1], 'description' => $data[2]],
            )]);

            $examples = [
                ['Saluran drainase tertutup sampah', ['drainase', 'sampah'], 'Gang Anggrek RT 05 RW 01'],
                ['Jalan lingkungan berlubang', ['jalan-rusak', 'drainase'], 'Jalan Melati RT 03 RW 02'],
                ['Lampu jalan padam', ['penerangan'], 'Jalan Kenanga RT 02 RW 06'],
            ];
            $residents = ['warga@laporkita.test', 'warga2@laporkita.test', 'warga3@laporkita.test'];
            foreach ([ReportStatus::Diverifikasi, ReportStatus::Ditolak, ReportStatus::Selesai] as $statusIndex => $status) {
                foreach ($examples as $index => [$title, $slugs, $address]) {
                    $created = now()->subDays(7 + $index);
                    $completed = $status === ReportStatus::Selesai;
                    $rejected = $status === ReportStatus::Ditolak;
                    $report = Report::firstOrCreate(['demo_key' => 'laporkita-v1-'.$status->value.'-'.($index + 1)], [
                        'user_id' => $users[$residents[$index]]->id,
                        'officer_id' => $users['petugas@laporkita.test']->id,
                        'title' => '[Demo] '.$title.' — '.$status->label(),
                        'description' => 'Data demonstrasi LaporKita, bukan kejadian nyata. '.$title.'. Kondisi lingkungan ini digunakan untuk memperagakan pelaporan dan tindak lanjut petugas. Foto yang ditampilkan adalah ilustrasi demo.',
                        'address' => $address.', Kelurahan Demo, Malang',
                        'latitude' => -7.96682 - ($statusIndex * 3 + $index) * 0.0012,
                        'longitude' => 112.63291 + $index * 0.0013,
                        'photo_path' => 'demo/laporan.svg',
                        'resolution_photo_path' => $completed ? 'demo/penyelesaian.svg' : null,
                        'status' => $status,
                        'officer_note' => $rejected
                            ? 'Demo penolakan: titik yang dilaporkan berada di area privat dan bukan kewenangan pengelola infrastruktur publik.'
                            : ($completed ? 'Demo penyelesaian: perbaikan telah dituntaskan dan diperiksa. Bukti terlampir merupakan ilustrasi.' : 'Demo verifikasi: kategori, lokasi, dan kondisi telah diperiksa petugas.'),
                        'created_at' => $created,
                        'updated_at' => $created->copy()->addDays($completed ? 4 : 1),
                        'verified_at' => $rejected ? null : $created->copy()->addDay(),
                        'processed_at' => $completed ? $created->copy()->addDays(2) : null,
                        'resolved_at' => $completed ? $created->copy()->addDays(4) : null,
                        'rejected_at' => $rejected ? $created->copy()->addDay() : null,
                    ]);
                    if ($report->wasRecentlyCreated) {
                        $report->categories()->attach(collect($slugs)->map(fn (string $slug): int => $categories[$slug]->id));
                    }
                }
            }
        });
    }
}
