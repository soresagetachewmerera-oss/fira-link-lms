<?php

namespace App\Http\Controllers\Setup;

use App\Services\Installer\RequirementService;
use App\Services\Installer\SetupState;

class WelcomeController extends SetupController
{
    public function __construct(
        protected SetupState $state,
        protected RequirementService $requirements
    ) {
    }

    public function __invoke()
    {
        return $this->view('setup.welcome', 'welcome', [
            'alreadyInstalled' => $this->state->isInstalled(),
            'requirementsMet' => $this->requirements->isSatisfied(),
        ]);
    }
}

