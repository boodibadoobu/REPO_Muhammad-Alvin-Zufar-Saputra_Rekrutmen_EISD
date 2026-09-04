<?php

namespace App\Enums;

enum UserRole: string
{
    case Warga = 'warga';
    case Petugas = 'petugas';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Warga => 'Warga',
            self::Petugas => 'Petugas',
            self::Admin => 'Admin',
        };
    }
}
