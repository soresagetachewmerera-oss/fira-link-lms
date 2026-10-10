<?php

namespace App\Http\Controllers\Setup;

use App\Services\Installer\DatabaseInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseController extends SetupController
{
    public function __construct(
        protected DatabaseInstaller $installer
    ) {
    }

    public function show(Request $request)
    {
        return $this->view('setup.database', 'database', [
            'statusMessage' => session('status'),
            'output' => session('setup.output'),
            'error' => $request->session()->get('setup.error'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $result = $this->installer->run();

        if ($result['status'] !== 'success') {
            return redirect()
                ->route('setup.database')
                ->with('setup.output', $result['output'] ?? null)
                ->with('setup.error', $result['message'] ?? 'An unexpected error occurred.');
        }

        session()->flash('setup.output', $result['output'] ?? null);

        return redirect()
            ->route('setup.final')
            ->with('status', 'Database migrations and seeds executed successfully.');
    }
}

