<?php

namespace App\Http\Controllers\Setup;

use App\Services\Installer\RequirementService;

class RequirementsController extends SetupController
{
    public function __construct(
        protected RequirementService $requirements
    ) {
    }

    public function __invoke()
    {
        $summary = $this->requirements->getSummary();

        return $this->view('setup.requirements', 'requirements', [
            'summary' => $summary,
            'ready' => $this->requirements->isSatisfied(),
        ]);
    }
}

