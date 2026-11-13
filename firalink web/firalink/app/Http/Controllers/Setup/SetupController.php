<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use App\Services\Installer\SetupState;


abstract class SetupController extends Controller
{
    protected const STEPS = [
        ['key' => 'welcome', 'label' => 'Welcome', 'route' => 'setup.welcome'],
        ['key' => 'requirements', 'label' => 'Requirements', 'route' => 'setup.requirements'],
        ['key' => 'permissions', 'label' => 'Permissions', 'route' => 'setup.permissions'],
        ['key' => 'environment', 'label' => 'Environment', 'route' => 'setup.environment'],
        ['key' => 'database', 'label' => 'Database', 'route' => 'setup.database'],
        ['key' => 'final', 'label' => 'Finish', 'route' => 'setup.final'],
    ];

    public function callAction($method, $parameters)
    {
        if (app(SetupState::class)->isInstalled()) {
            return redirect('/');
        }

        return call_user_func_array([$this, $method], $parameters);
    }


    protected function stepCollection(): Collection
    {
        return collect(self::STEPS);
    }

    protected function stepContext(string $current): array
    {
        $steps = $this->stepCollection();
        $currentIndex = (int) $steps->search(fn($step) => $step['key'] === $current);

        return $steps
            ->values()
            ->map(function (array $step, int $index) use ($currentIndex) {
                return [
                    ...$step,
                    'status' => $index < $currentIndex
                        ? 'complete'
                        : ($index === $currentIndex ? 'current' : 'upcoming'),
                ];
            })
            ->toArray();
    }

    protected function view(string $view, string $currentStep, array $data = [])
    {
        return view($view, [
            ...$data,
            'steps' => $this->stepContext($currentStep),
        ]);
    }
}

