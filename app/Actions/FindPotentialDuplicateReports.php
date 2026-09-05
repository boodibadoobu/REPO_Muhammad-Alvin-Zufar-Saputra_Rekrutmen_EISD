<?php

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Support\Collection;

class FindPotentialDuplicateReports
{
    private const RADIUS_METERS = 150;

    private const LOOKBACK_DAYS = 30;

    private const EARTH_RADIUS_METERS = 6_371_000;

    /**
     * @param  list<int>  $categoryIds
     * @return Collection<int, Report>
     */
    public function handle(float $latitude, float $longitude, array $categoryIds): Collection
    {
        // Latitude is a conservative prefilter; exact distance decides membership.
        $latitudeDelta = rad2deg(self::RADIUS_METERS / self::EARTH_RADIUS_METERS) + 0.0000001;

        return Report::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('status', [
                ReportStatus::Diajukan->value,
                ReportStatus::Diverifikasi->value,
                ReportStatus::Diproses->value,
            ])
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->latest()
            ->cursor()
            ->filter(fn (Report $report): bool => $this->distanceInMeters(
                $latitude,
                $longitude,
                (float) $report->latitude,
                (float) $report->longitude,
            ) <= self::RADIUS_METERS)
            ->take(3)
            ->collect()
            ->values();
    }

    private function distanceInMeters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB))
            * sin($longitudeDelta / 2) ** 2;

        $haversine = min(1.0, max(0.0, $haversine));

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
