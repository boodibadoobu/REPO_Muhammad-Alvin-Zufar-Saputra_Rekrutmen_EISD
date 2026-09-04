<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return $user->hasAnyRole(UserRole::Admin, UserRole::Petugas)
            || $report->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Warga);
    }

    public function update(User $user, Report $report): bool
    {
        return $report->user_id === $user->id
            && $report->status === ReportStatus::Diajukan;
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->update($user, $report);
    }

    public function updateStatus(User $user, Report $report): bool
    {
        return $user->hasAnyRole(UserRole::Admin, UserRole::Petugas);
    }
}
