<?php

namespace App\Http\Controllers;

use App\Enums\LeadGoal;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\ChatMessage;
use App\Models\Lead;
use App\Support\IpHasher;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Leads/Index', [
            'leads' => $this->filtered($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Lead $lead): array => $this->summary($lead)),
            'filters' => $this->filters($request),
            'options' => [
                'statuses' => $this->options(LeadStatus::cases()),
                'sources' => $this->options(LeadSource::cases()),
                'goals' => $this->options(LeadGoal::cases()),
            ],
        ]);
    }

    public function show(Lead $lead): Response
    {
        return Inertia::render('Leads/Show', [
            'lead' => $this->detailPayload($lead),
            'statuses' => $this->options(LeadStatus::cases()),
        ]);
    }

    public function store(StoreLeadRequest $request): JsonResponse|RedirectResponse
    {
        $spam = $request->isSpamTrap();
        $lead = Lead::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $this->clip($request->input('phone'), 40),
            'goal' => $request->string('goal')->toString(),
            'message' => $this->clip($request->input('message'), 2000),
            'source' => LeadSource::Form,
            'status' => $spam ? LeadStatus::Spam : LeadStatus::New,
            'utm_source' => $this->clip($request->input('utm_source'), 190),
            'utm_medium' => $this->clip($request->input('utm_medium'), 190),
            'utm_campaign' => $this->clip($request->input('utm_campaign'), 190),
            'utm_term' => $this->clip($request->input('utm_term'), 190),
            'utm_content' => $this->clip($request->input('utm_content'), 190),
            'referrer' => $this->clip($request->input('referrer'), 500),
            'landing_path' => $this->clip($request->input('landing_path'), 500),
            'visitor_id' => $this->uuidOrNull($request->input('visitor_id')),
            'ip_hash' => IpHasher::hash((string) $request->ip()),
            'user_agent' => $this->clip($request->userAgent(), 500),
        ]);

        if (! $spam) {
            event(new LeadCreated($lead));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'name' => $lead->name,
            ], $spam ? 200 : 201);
        }

        return redirect('/#contact')->with('lead_success', $lead->name);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $note = $request->input('admin_note');

        $lead->update([
            'status' => $request->string('status')->toString(),
            'admin_note' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ]);

        return redirect()->route('leads.show', $lead);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index');
    }

    public function export(Request $request): StreamedResponse
    {
        $leads = $this->filtered($request)->get();
        $columns = [
            'id', 'name', 'email', 'phone', 'goal', 'message', 'source', 'status', 'admin_note',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'referrer', 'landing_path', 'visitor_id', 'chat_session_id', 'user_agent',
            'created_at', 'updated_at',
        ];

        return response()->streamDownload(function () use ($leads, $columns): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);

            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->id,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->goal?->value,
                    $lead->message,
                    $lead->source?->value,
                    $lead->status?->value,
                    $lead->admin_note,
                    $lead->utm_source,
                    $lead->utm_medium,
                    $lead->utm_campaign,
                    $lead->utm_term,
                    $lead->utm_content,
                    $lead->referrer,
                    $lead->landing_path,
                    $lead->visitor_id,
                    $lead->chat_session_id,
                    $lead->user_agent,
                    $this->formatDate($lead->created_at),
                    $this->formatDate($lead->updated_at),
                ]);
            }

            fclose($out);
        }, 'leads.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Builder<Lead>
     */
    private function filtered(Request $request): Builder
    {
        $query = Lead::query()->orderByDesc('created_at');
        $status = (string) $request->query('status', '');
        $source = (string) $request->query('source', '');
        $goal = (string) $request->query('goal', '');

        if (LeadStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        if (LeadSource::tryFrom($source)) {
            $query->where('source', $source);
        }

        if (LeadGoal::tryFrom($goal)) {
            $query->where('goal', $goal);
        }

        $tz = (string) config('app.display_timezone', 'America/Chicago');
        $period = (string) $request->query('period', 'all');

        if ($period === 'today') {
            $query->whereBetween('created_at', [
                now($tz)->startOfDay()->utc(),
                now($tz)->endOfDay()->utc(),
            ]);
        } elseif ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($period === 'custom') {
            $from = (string) $request->query('from', '');
            $to = (string) $request->query('to', '');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($from, $tz)->startOfDay()->utc());
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($to, $tz)->endOfDay()->utc());
            }
        }

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        return [
            'status' => (string) $request->query('status', ''),
            'source' => (string) $request->query('source', ''),
            'goal' => (string) $request->query('goal', ''),
            'period' => (string) $request->query('period', 'all'),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
            'q' => (string) $request->query('q', ''),
        ];
    }

    /**
     * @param  array<int, LeadStatus|LeadSource|LeadGoal>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(fn (LeadStatus|LeadSource|LeadGoal $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $cases);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'created_at' => $this->formatDate($lead->created_at),
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'goal' => $lead->goal?->value,
            'goal_label' => $lead->goal?->label(),
            'source' => $lead->source?->value,
            'source_label' => $lead->source?->label(),
            'status' => $lead->status?->value,
            'status_label' => $lead->status?->label(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Lead $lead): array
    {
        return [
            ...$this->summary($lead),
            'message' => $lead->message,
            'admin_note' => $lead->admin_note,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_term' => $lead->utm_term,
            'utm_content' => $lead->utm_content,
            'referrer' => $lead->referrer,
            'landing_path' => $lead->landing_path,
            'visitor_id' => $lead->visitor_id,
            'chat_session_id' => $lead->chat_session_id,
            'user_agent' => $lead->user_agent,
            'updated_at' => $this->formatDate($lead->updated_at),
            'chat' => $this->chatPayload($lead),
        ];
    }

    /**
     * @return array{id: int, messages: list<array<string, mixed>>}|null
     */
    private function chatPayload(Lead $lead): ?array
    {
        if ($lead->chat_session_id === null) {
            return null;
        }

        $session = $lead->chatSession()->with('messages')->first();

        if ($session === null) {
            return null;
        }

        return [
            'id' => $session->id,
            'messages' => $session->messages->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'error' => $message->error,
                'created_at' => $this->formatDate($message->created_at),
            ])->all(),
        ];
    }

    private function formatDate(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->copy()
            ->timezone((string) config('app.display_timezone', 'America/Chicago'))
            ->format('M j, Y g:i A');
    }

    private function clip(mixed $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }

    private function uuidOrNull(mixed $value): ?string
    {
        if (! is_string($value) || ! Str::isUuid($value)) {
            return null;
        }

        return $value;
    }
}
