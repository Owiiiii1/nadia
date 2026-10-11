<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune';

    protected $description = 'Delete deploy logs older than 30 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(30)->getTimestamp();
        $deleted = 0;

        foreach (glob(storage_path('logs/deploy-*.log')) ?: [] as $file) {
            $mtime = filemtime($file);

            if ($mtime !== false && $mtime < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        $this->info("Pruned {$deleted} deploy logs.");

        return self::SUCCESS;
    }
}
