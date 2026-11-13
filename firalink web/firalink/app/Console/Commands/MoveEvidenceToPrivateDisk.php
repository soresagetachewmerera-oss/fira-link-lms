<?php

namespace App\Console\Commands;

use App\Services\PrivateDocumentStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves evidence files that were stored on the public disk (reachable at /storage/...)
 * to the private disk. Paths in the database stay the same; only the disk changes.
 *
 *   php artisan files:move-evidence-private --dry-run
 *   php artisan files:move-evidence-private
 */
class MoveEvidenceToPrivateDisk extends Command
{
    protected $signature = 'files:move-evidence-private {--dry-run : Count files without moving them}';

    protected $description = 'Move bank slips, contracts, lot evidence, report evidence, licenses and lab reports off the public disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(PrivateDocumentStore::DISK);
        $dryRun = (bool) $this->option('dry-run');

        $rows = [];
        $empty = ['referenced' => 0, 'on_public' => 0, 'web_copies' => 0, 'moved' => 0, 'already_private' => 0, 'missing' => 0];
        $totals = $empty;

        foreach (PrivateDocumentStore::ADMIN_KINDS as $kind => [$modelClass, $column]) {
            $counts = $empty;

            // withoutGlobalScopes(): include soft-deleted lots, their files are still exposed.
            $modelClass::withoutGlobalScopes()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->select(['id', $column])
                ->chunkById(200, function ($records) use ($column, $public, $private, $dryRun, &$counts) {
                    foreach ($records as $record) {
                        $path = ltrim((string) $record->{$column}, '/');
                        if (str_contains($path, '..')) {
                            continue;
                        }
                        $counts['referenced']++;

                        // On hosts without a symlink, public/storage is a separate copy that the web server serves.
                        $webCopy = public_path('storage/' . $path);
                        $hasWebCopy = is_file($webCopy) && !is_link(public_path('storage'));
                        if ($hasWebCopy) {
                            $counts['web_copies']++;
                        }

                        if ($public->exists($path)) {
                            $counts['on_public']++;
                            if (!$dryRun) {
                                $private->put($path, $public->get($path));
                                if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                                    $public->delete($path);
                                    $counts['moved']++;
                                }
                            }
                        } elseif ($private->exists($path)) {
                            $counts['already_private']++;
                        } elseif ($hasWebCopy) {
                            // Only the served copy exists: move that one.
                            $counts['on_public']++;
                            if (!$dryRun) {
                                $private->put($path, (string) file_get_contents($webCopy));
                                $counts['moved']++;
                            }
                        } else {
                            $counts['missing']++;
                        }

                        if ($hasWebCopy && !$dryRun && $private->exists($path)) {
                            @unlink($webCopy);
                        }
                    }
                });

            foreach ($counts as $key => $value) {
                $totals[$key] += $value;
            }
            $rows[] = array_merge([$kind], array_values($counts));
        }

        $rows[] = array_merge(['TOTAL'], array_values($totals));

        $this->table(['Kind', 'Referenced', 'On public disk', 'Copies in public/storage', 'Moved', 'Already private', 'Missing'], $rows);
        $this->info($dryRun
            ? "Dry run: {$totals['on_public']} file(s) would be moved."
            : "Moved {$totals['moved']} of {$totals['on_public']} file(s) off the public disk.");

        return self::SUCCESS;
    }
}
