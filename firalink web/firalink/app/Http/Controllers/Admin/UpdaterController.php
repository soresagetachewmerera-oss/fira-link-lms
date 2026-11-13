<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\PlatformSetting; // Assuming this model exists

class UpdaterController extends Controller
{
    public function index()
    {
        $setting = DB::table('platform_settings')->where('key', 'system_version')->first();
        $currentVersion = $setting ? json_decode($setting->payload) : '1.0.0';
        
        $newVersion = \Database\Seeders\VersionUpdateSeeder::NEW_VERSION;
        
        return view('admin.updater.index', compact('currentVersion', 'newVersion'));
    }

    public function update(Request $request)
    {
        try {
            // Run Migrations
            Artisan::call('migrate', ['--force' => true]);

            // Run Version Seeder
            Artisan::call('db:seed', ['--class' => 'VersionUpdateSeeder', '--force' => true]);
            $output_version = Artisan::output();

            // Run Storage Link
            ensure_storage_link();

            return redirect()->back()
                ->with('success', 'System updated successfully.')
                ->with('output', 'System version updated to: ' . \Database\Seeders\VersionUpdateSeeder::NEW_VERSION);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
}
