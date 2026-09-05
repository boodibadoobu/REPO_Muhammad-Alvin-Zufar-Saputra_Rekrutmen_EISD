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
            'latitude' => fake()->latitude(-8.1, -7.8),
            'longitude' => fake()->longitude(112.5, 112.8),
            'photo_path' => 'reports/contoh.jpg',
            'resolution_photo_path' => null,
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
