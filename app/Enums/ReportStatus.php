<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Diverifikasi => 'Diverifikasi',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Diajukan => 'badge-warning',
            self::Diverifikasi => 'badge-info',
            self::Diproses => 'badge-primary',
            self::Selesai => 'badge-success',
            self::Ditolak => 'badge-danger',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Diajukan => [self::Diverifikasi, self::Ditolak],
            self::Diverifikasi => [self::Diproses],
            self::Diproses => [self::Selesai],
            self::Selesai, self::Ditolak => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
