<?php

namespace App\Console\Commands;

use App\Models\Course;
use Database\Seeders\FreeBusinessPitfallsCourseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetCoursesCatalogCommand extends Command
{
    protected $signature = 'courses:reset-to-free-catalog 
                            {--force : Apply the reset without prompt}
                            {--fresh : Wipe all existing courses completely before seeding}';

    protected $description = 'Remove all legacy courses from mobile app and website, and seed the official 11 free courses';

    public function handle(): int
    {
        $this->info('Starting course catalog reset to official 11 free courses...');

        $officialTitles = [
            'Avoiding Common Business Pitfalls: 3 Critical Mistakes and Solutions',
            '5 Accessible Business Ideas with Low Starting Capital',
            'Starting a Profitable Coffee Business in Ethiopia: A Step-by-Step Guide',
            'The Secrets to Becoming Rich Quickly: Simple Ways to Success',
            'Starting a Profitable Pool House Business: A Step-by-Step Guide',
            'Coffee Export Business: From Farm to Global Market',
            'Starting a Low-Capital Tutoring Business: Scaling from 500 Birr to 100,000+ Birr Monthly',
            'Navigating the Ethiopian Coffee Export Industry: Procedures and Requirements',
            'Essential Documentation and Procedures for International Coffee Export',
            'Proven Strategies for Finding International Buyers',
            'Understanding Coffee Varieties: Arabica vs. Robusta',
        ];

        $currentCount = Course::count();
        $isFresh = $this->option('fresh');
        $legacyCourses = Course::whereNotIn('title', $officialTitles)->get();

        $this->info("Currently found {$currentCount} total course(s).");
        if ($isFresh) {
            $this->warn("Mode: FRESH WIPE — All {$currentCount} courses will be removed and re-seeded.");
        } else {
            $this->info("Legacy course(s) to remove: {$legacyCourses->count()}");
            foreach ($legacyCourses as $course) {
                $this->line(" - To remove: [ID {$course->id}] {$course->title} ({$course->pricing_model})");
            }
        }

        if (! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm('Do you wish to proceed?')) {
                $this->warn('Operation cancelled.');
                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($isFresh, $legacyCourses) {
            if ($isFresh) {
                Course::all()->each(fn ($c) => $c->delete());
            } else {
                foreach ($legacyCourses as $course) {
                    $course->delete();
                }
            }

            $seeder = new FreeBusinessPitfallsCourseSeeder();
            $seeder->setCommand($this);
            $seeder->run();
        });

        $finalCourses = Course::where('status', 'published')->get();
        $this->info("Course catalog successfully reset! Active courses count: {$finalCourses->count()}");
        $this->table(
            ['ID', 'Title', 'Pricing', 'Category', 'Status'],
            $finalCourses->map(fn ($c) => [$c->id, $c->title, $c->pricing_model, $c->category, $c->status])->toArray()
        );

        return self::SUCCESS;
    }
}
