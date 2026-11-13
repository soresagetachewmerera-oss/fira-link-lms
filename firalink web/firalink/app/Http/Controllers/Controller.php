<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Controller
 *
 * This is the base controller class for all controllers.
 * It provides common functionality for all controllers.
 *
 * @package App\Http\Controllers
 * @author MhquickDev
 */
abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
