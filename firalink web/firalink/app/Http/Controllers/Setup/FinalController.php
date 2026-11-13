<?php

namespace App\Http\Controllers\Setup;

use App\Models\User;
use App\Services\Installer\EnvironmentService;
use App\Services\Installer\SetupState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;


class FinalController extends SetupController
{
    public function __construct(
        protected SetupState $state,
        protected EnvironmentService $environment
    ) {
    }

    public function __invoke(Request $request)
    {
        if (!$this->state->isInstalled()) {
            $this->state->markInstalled();
            ensure_storage_link();
        }


        $demoUsers = User::query()
            ->whereIn('email', [
                'admin@eduex.com',
                'instructor@eduex.com',
                'student@eduex.com',
            ])
            ->orderByRaw("FIELD(email, 'admin@eduex.com', 'instructor@eduex.com', 'student@eduex.com')")
            ->get(['name', 'email', 'role'])
            ->map(function (User $user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => ucfirst($user->role),
                    'password' => 'password',
                ];
            });

        return $this->view('setup.final', 'final', [
            'statusMessage' => $request->session()->get('status', 'Setup complete!'),
            'output' => session('setup.output'),
            'markerPath' => $this->state->markerPath(),
            'envValues' => $this->environment->currentValues(),
            'demoUsers' => $demoUsers,
        ]);
    }
}

