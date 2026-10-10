<?php

namespace App\Http\Controllers\Setup;

use App\Services\Installer\EnvironmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnvironmentController extends SetupController
{
    public function __construct(
        protected EnvironmentService $environment
    ) {
    }

    public function show()
    {
        return $this->view('setup.environment', 'environment', [
            'sections' => $this->environment->sections(),
            'values' => $this->environment->currentValues(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = $this->environment->rules();
        $validated = $request->validate($rules);

        $payload = collect($this->environment->defaults())
            ->merge($validated)
            ->map(function ($value, $key) use ($request) {
                $input = $request->input($key, $value);

                if (is_bool($input)) {
                    return $input;
                }

                return $input === null ? '' : $input;
            })
            ->toArray();

        $this->environment->persist($payload);

        return redirect()
            ->route('setup.database')
            ->with('status', 'Environment configuration saved.');
    }
}

