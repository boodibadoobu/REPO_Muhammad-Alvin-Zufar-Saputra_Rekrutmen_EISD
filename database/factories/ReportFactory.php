<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Report> */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->warga(),
            'officer_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'photo_path' => 'reports/contoh.jpg',
            'status' => ReportStatus::Diajukan,
            'officer_note' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportStatus::Diverifikasi,
            'officer_id' => User::factory()->petugas(),
            'verified_at' => now(),
        ]);
    }
}
