<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin LaporKita',
            'email' => 'admin@laporkita.test',
            'password' => 'Password123!',
        ]);
        $officer = User::factory()->petugas()->create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@laporkita.test',
            'password' => 'Password123!',
        ]);
        $resident = User::factory()->warga()->create([
            'name' => 'Warga Demo',
            'email' => 'warga@laporkita.test',
            'password' => 'Password123!',
        ]);

        $categories = collect([
            ['name' => 'Jalan Rusak', 'slug' => 'jalan-rusak', 'description' => 'Jalan berlubang, retak, atau membahayakan pengguna.'],
            ['name' => 'Drainase', 'slug' => 'drainase', 'description' => 'Saluran air tersumbat, rusak, atau memicu genangan.'],
            ['name' => 'Sanitasi', 'slug' => 'sanitasi', 'description' => 'Masalah air bersih, limbah, dan fasilitas sanitasi.'],
            ['name' => 'Sampah', 'slug' => 'sampah', 'description' => 'Penumpukan sampah dan sarana pengelolaan yang tidak memadai.'],
            ['name' => 'Penerangan', 'slug' => 'penerangan', 'description' => 'Lampu jalan mati atau area publik minim penerangan.'],
            ['name' => 'Bangunan Tidak Layak', 'slug' => 'bangunan-tidak-layak', 'description' => 'Hunian atau fasilitas umum yang tidak aman dan tidak layak.'],
        ])->map(fn (array $category): Category => Category::query()->create($category));

        Storage::disk('public')->put(
            'reports/contoh-lingkungan.svg',
            file_get_contents(public_path('images/report-placeholder.svg')),
        );

        collect([
            [
                'title' => 'Jalan lingkungan berlubang dan tergenang',
                'description' => 'Kerusakan jalan semakin lebar ketika hujan dan genangan membuat pengendara sulit melihat lubang.',
                'address' => 'Jalan Melati RT 03 RW 02, Kelurahan Sukamaju',
                'status' => ReportStatus::Diajukan,
                'category_indexes' => [0, 1],
            ],
            [
                'title' => 'Saluran drainase tertutup sampah',
                'description' => 'Sampah menumpuk di saluran utama sehingga air meluap ke halaman rumah warga saat hujan deras.',
                'address' => 'Gang Anggrek RT 05 RW 01, Kelurahan Sukamaju',
                'status' => ReportStatus::Diverifikasi,
                'category_indexes' => [1, 3],
            ],
            [
                'title' => 'Lampu jalan padam di akses permukiman',
                'description' => 'Tiga titik lampu jalan tidak menyala dan membuat akses menuju permukiman gelap pada malam hari.',
                'address' => 'Jalan Kenanga RT 02 RW 06, Kelurahan Harapan',
                'status' => ReportStatus::Diproses,
                'category_indexes' => [4],
            ],
            [
                'title' => 'Tempat sampah komunal sudah diperbaiki',
                'description' => 'Tempat penampungan sementara sebelumnya rusak dan sampah tercecer ke badan jalan.',
                'address' => 'Pasar Warga RW 04, Kelurahan Harapan',
                'status' => ReportStatus::Selesai,
                'category_indexes' => [3],
            ],
        ])->map(function (array $data) use ($resident, $officer, $categories): Report {
            $categoryIndexes = $data['category_indexes'];
            unset($data['category_indexes']);

            $report = new Report;
            $report->user_id = $resident->id;
            $report->officer_id = $data['status'] === ReportStatus::Diajukan ? null : $officer->id;
            $report->title = $data['title'];
            $report->description = $data['description'];
            $report->address = $data['address'];
            $report->photo_path = 'reports/contoh-lingkungan.svg';
            $report->status = $data['status'];
            $report->officer_note = $data['status'] === ReportStatus::Diajukan ? null : 'Laporan telah diperiksa oleh petugas lapangan.';
            $report->verified_at = in_array($data['status'], [ReportStatus::Diverifikasi, ReportStatus::Diproses, ReportStatus::Selesai], true) ? now()->subDays(3) : null;
            $report->processed_at = in_array($data['status'], [ReportStatus::Diproses, ReportStatus::Selesai], true) ? now()->subDays(2) : null;
            $report->resolved_at = $data['status'] === ReportStatus::Selesai ? now()->subDay() : null;
            $report->save();
            $report->categories()->attach($categories->only($categoryIndexes)->pluck('id'));

            return $report;
        });

    }
}
