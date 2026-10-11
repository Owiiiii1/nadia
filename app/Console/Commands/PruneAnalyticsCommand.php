<?php

namespace App\Console\Commands;

use App\Models\PageView;
use App\Models\SiteEvent;
use Illuminate\Console\Command;

class PruneAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune';

    protected $description = 'Delete page views and site events older than 12 months';

    public function handle(): int
    {
        $cutoff = now()->subMonths(12);
        $views = $this->prune(PageView::class, $cutoff);
        $events = $this->prune(SiteEvent::class, $cutoff);
        $this->info("Pruned {$views} page views and {$events} site events.");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<PageView|SiteEvent>  $model
     */
    private function prune(string $model, \DateTimeInterface $cutoff): int
    {
        $deleted = 0;

        do {
            $ids = $model::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(500)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += $model::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 500);

        return $deleted;
    }
}
