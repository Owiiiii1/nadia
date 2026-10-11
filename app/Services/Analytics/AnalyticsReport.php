<?php

namespace App\Services\Analytics;

use App\Models\ChatSession;
use App\Models\Lead;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsReport
{
    /**
     * @return array<string, mixed>
     */
    public function forRequest(string $period, ?string $from, ?string $to): array
    {
        [$start, $end] = $this->bounds($period, $from, $to);
        $length = (int) round($start->copy()->timezone($this->timezone())->startOfDay()
            ->diffInDays($end->copy()->timezone($this->timezone())->startOfDay(), true));
        $previousEnd = $start->copy();
        $previousStart = $start->copy()->subDays($length);
        $key = 'analytics:'.md5($start->toIso8601String().'|'.$end->toIso8601String());

        return Cache::remember($key, 300, function () use ($start, $end, $previousStart, $previousEnd, $period, $from, $to): array {
            $current = $this->totals($start, $end);
            $previous = $this->totals($previousStart, $previousEnd);

            return [
                'filters' => [
                    'period' => $period,
                    'from' => $from ?? '',
                    'to' => $to ?? '',
                ],
                'empty' => $current['pageviews'] === 0 && $current['leads'] === 0 && $current['chat_opens'] === 0 && $current['chat_leads'] === 0,
                'cards' => [
                    $this->card('visitors', $current['visitors'], $previous['visitors']),
                    $this->card('sessions', $current['sessions'], $previous['sessions']),
                    $this->card('pageviews', $current['pageviews'], $previous['pageviews']),
                    $this->card('leads', $current['leads'], $previous['leads']),
                    $this->card('conversion', $current['conversion'], $previous['conversion']),
                    $this->card('chat_opens', $current['chat_opens'], $previous['chat_opens']),
                    $this->card('chat_leads', $current['chat_leads'], $previous['chat_leads']),
                ],
                'series' => $this->series($start, $end),
                'referrers' => $this->referrers($start, $end),
                'utm' => [
                    'source' => $this->utm($start, $end, 'utm_source'),
                    'medium' => $this->utm($start, $end, 'utm_medium'),
                    'campaign' => $this->utm($start, $end, 'utm_campaign'),
                ],
                'devices' => $this->breakdown($start, $end, 'device'),
                'browsers' => $this->breakdown($start, $end, 'browser'),
                'oses' => $this->breakdown($start, $end, 'os'),
            ];
        });
    }

    /**
     * @return array{leads: int, visitors: int, conversion: float, chats: int, series: list<array{date: string, visitors: int, pageviews: int}>, recent: list<array<string, mixed>>}
     */
    public function dashboard(): array
    {
        [$start, $end] = $this->bounds('30d', null, null);
        $totals = $this->totals($start, $end);
        $tz = $this->timezone();

        $recent = Lead::query()
            ->where('status', '!=', 'spam')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->name,
                'created_at' => $lead->created_at?->copy()->timezone($tz)->format('M j, Y g:i A'),
            ])
            ->all();

        return [
            'leads' => $totals['leads'],
            'visitors' => $totals['visitors'],
            'conversion' => $totals['conversion'],
            'chats' => ChatSession::query()->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            'series' => $this->series($start, $end),
            'recent' => $recent,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bounds(string $period, ?string $from, ?string $to): array
    {
        $tz = $this->timezone();
        $today = now($tz)->startOfDay();
        $end = $today->copy()->addDay();

        if ($period === 'custom' && $this->date($from) && $this->date($to)) {
            $start = Carbon::parse($from, $tz)->startOfDay();
            $finish = Carbon::parse($to, $tz)->startOfDay();

            if ($finish->lt($start)) {
                [$start, $finish] = [$finish, $start];
            }

            return [$start->utc(), $finish->copy()->addDay()->utc()];
        }

        $days = match ($period) {
            'today' => 0,
            '7d' => 6,
            '90d' => 89,
            default => 29,
        };

        return [$today->copy()->subDays($days)->utc(), $end->utc()];
    }

    /**
     * @return array{visitors: int, sessions: int, pageviews: int, leads: int, conversion: float, chat_opens: int, chat_leads: int}
     */
    private function totals(CarbonInterface $start, CarbonInterface $end): array
    {
        $views = DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('COUNT(*) as pageviews')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('COUNT(DISTINCT session_id) as sessions')
            ->first();

        $leads = (int) DB::table('leads')
            ->where('status', '!=', 'spam')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();

        $events = DB::table('site_events')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereIn('type', ['chat_open', 'chat_lead'])
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $visitors = (int) ($views->visitors ?? 0);

        return [
            'visitors' => $visitors,
            'sessions' => (int) ($views->sessions ?? 0),
            'pageviews' => (int) ($views->pageviews ?? 0),
            'leads' => $leads,
            'conversion' => $visitors > 0 ? round($leads / $visitors * 100, 1) : 0.0,
            'chat_opens' => (int) ($events['chat_open'] ?? 0),
            'chat_leads' => (int) ($events['chat_lead'] ?? 0),
        ];
    }

    /**
     * @return list<array{date: string, visitors: int, pageviews: int}>
     */
    private function series(CarbonInterface $start, CarbonInterface $end): array
    {
        $days = $this->days($start, $end);
        $case = $this->dayCase($days);
        $bindings = $this->dayBindings($days);
        $rows = DB::select(
            'select day, count(*) as pageviews, count(distinct visitor_id) as visitors
             from (
                select visitor_id, '.$case.' as day
                from page_views
                where created_at >= ? and created_at < ?
             ) bucketed
             where day is not null
             group by day',
            [...$bindings, $start->toDateTimeString(), $end->toDateTimeString()],
        );
        $byDay = [];

        foreach ($rows as $row) {
            $byDay[(string) $row->day] = [
                'visitors' => (int) $row->visitors,
                'pageviews' => (int) $row->pageviews,
            ];
        }

        return array_map(fn (array $day): array => [
            'date' => $day['date'],
            'visitors' => $byDay[$day['date']]['visitors'] ?? 0,
            'pageviews' => $byDay[$day['date']]['pageviews'] ?? 0,
        ], $days);
    }

    /**
     * @return list<array{label: string, visits: int, leads: int}>
     */
    private function referrers(CarbonInterface $start, CarbonInterface $end): array
    {
        $visits = DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw("COALESCE(referrer_host, '__direct__') as label")
            ->selectRaw('COUNT(*) as visits')
            ->groupByRaw("COALESCE(referrer_host, '__direct__')")
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $leadHosts = DB::table('leads')
            ->where('status', '!=', 'spam')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw($this->leadHostSql().' as label')
            ->selectRaw('COUNT(*) as leads')
            ->groupByRaw($this->leadHostSql())
            ->pluck('leads', 'label');

        return $visits->map(fn ($row): array => [
            'label' => $row->label === '__direct__' ? 'Direct / none' : (string) $row->label,
            'visits' => (int) $row->visits,
            'leads' => (int) ($leadHosts[$row->label === '__direct__' ? '' : $row->label] ?? 0),
        ])->all();
    }

    /**
     * @return list<array{label: string, visits: int, leads: int}>
     */
    private function utm(CarbonInterface $start, CarbonInterface $end, string $column): array
    {
        $visits = DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column.' as label')
            ->selectRaw('COUNT(*) as visits')
            ->groupBy($column)
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $leads = DB::table('leads')
            ->where('status', '!=', 'spam')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column.' as label')
            ->selectRaw('COUNT(*) as leads')
            ->groupBy($column)
            ->pluck('leads', 'label');

        return $visits->map(fn ($row): array => [
            'label' => (string) $row->label,
            'visits' => (int) $row->visits,
            'leads' => (int) ($leads[$row->label] ?? 0),
        ])->all();
    }

    /**
     * @return list<array{label: string, visits: int}>
     */
    private function breakdown(CarbonInterface $start, CarbonInterface $end, string $column): array
    {
        return DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull($column)
            ->select($column.' as label')
            ->selectRaw('COUNT(*) as visits')
            ->groupBy($column)
            ->orderByDesc('visits')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /**
     * @return array{key: string, value: float|int, delta: float|null}
     */
    private function card(string $key, float|int $current, float|int $previous): array
    {
        return [
            'key' => $key,
            'value' => $current,
            'delta' => $this->delta($current, $previous),
        ];
    }

    private function delta(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return (float) $current == 0.0 ? 0.0 : null;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }

    /**
     * @return list<array{date: string, start: string, end: string}>
     */
    private function days(CarbonInterface $start, CarbonInterface $end): array
    {
        $tz = $this->timezone();
        $cursor = $start->copy()->timezone($tz)->startOfDay();
        $last = $end->copy()->timezone($tz)->subSecond()->startOfDay();
        $days = [];

        while ($cursor->lte($last)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'start' => $cursor->copy()->utc()->toDateTimeString(),
                'end' => $cursor->copy()->addDay()->utc()->toDateTimeString(),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  list<array{date: string, start: string, end: string}>  $days
     */
    private function dayCase(array $days): string
    {
        if ($days === []) {
            return 'null';
        }

        $parts = array_fill(0, count($days), 'when created_at >= ? and created_at < ? then ?');

        return 'case '.implode(' ', $parts).' end';
    }

    /**
     * @param  list<array{date: string, start: string, end: string}>  $days
     * @return list<string>
     */
    private function dayBindings(array $days): array
    {
        $bindings = [];

        foreach ($days as $day) {
            $bindings[] = $day['start'];
            $bindings[] = $day['end'];
            $bindings[] = $day['date'];
        }

        return $bindings;
    }

    private function leadHostSql(): string
    {
        return "COALESCE(LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1), ':', 1)), '')";
    }

    private function timezone(): string
    {
        return (string) config('app.display_timezone', 'America/Chicago');
    }

    private function date(?string $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
