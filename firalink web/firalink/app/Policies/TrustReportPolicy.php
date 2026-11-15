<?php

namespace App\Policies;

use App\Models\TrustReport;
use App\Models\User;

/** Any signed-in person may report; only reviewers see and decide reports. The reported business never sees them. */
class TrustReportPolicy
{
    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, TrustReport $report): bool
    {
        return $user->can('review-verifications');
    }

    public function decide(User $user, TrustReport $report): bool
    {
        return $user->can('review-verifications');
    }
}
