<?php

namespace App\Http\Controllers\Setup;

use App\Services\Installer\PermissionService;

class PermissionsController extends SetupController
{
    public function __construct(
        protected PermissionService $permissions
    ) {
    }

    public function __invoke()
    {
        $summary = $this->permissions->getSummary();

        return $this->view('setup.permissions', 'permissions', [
            'summary' => $summary,
            'ready' => $this->permissions->isSatisfied(),
        ]);
    }
}

