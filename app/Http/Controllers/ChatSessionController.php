<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = $this->filtered($request)->paginate(25)->withQueryString();
        $ids = $sessions->getCollection()->pluck('id');
        $previews = ChatMessage::query()
            ->whereIn('chat_session_id', $ids)
            ->where('role', 'user')
            ->orderBy('id')
            ->get(['id', 'chat_session_id', 'content'])
            ->groupBy('chat_session_id')
            ->map(fn ($group): string => mb_substr((string) $group->first()->content, 0, 80));

        return Inertia::render('Chats/Index', [
            'sessions' => $sessions->through(fn (ChatSession $session): array => [
                'id' => $session->id,
                'last_message_at' => $this->formatDate($session->last_message_at),
                'messages_count' => $session->messages_count,
                'preview' => $previews->get($session->id, ''),
                'lead_id' => $session->lead_id,
                'lead_name' => $session->lead?->name,
            ]),
            'filters' => [
                'period' => (string) $request->query('period', 'all'),
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
                'lead' => $request->boolean('lead') ? '1' : '',
            ],
        ]);
    }

    public function show(ChatSession $session): Response
    {
        $session->load(['messages', 'lead:id,name']);

        return Inertia::render('Chats/Show', [
            'session' => $this->payload($session),
        ]);
    }

    public function destroy(ChatSession $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('chats.index');
    }

    /**
     * @return Builder<ChatSession>
     */
    private function filtered(Request $request): Builder
    {
        $query = ChatSession::query()->with('lead:id,name')->orderByDesc('last_message_at')->orderByDesc('id');

        if ($request->boolean('lead')) {
            $query->whereNotNull('lead_id');
        }

        $tz = (string) config('app.display_timezone', 'America/Chicago');
        $period = (string) $request->query('period', 'all');

        if ($period === 'today') {
            $query->whereBetween('last_message_at', [
                now($tz)->startOfDay()->utc(),
                now($tz)->endOfDay()->utc(),
            ]);
        } elseif ($period === '7d') {
            $query->where('last_message_at', '>=', now()->subDays(7));
        } elseif ($period === '30d') {
            $query->where('last_message_at', '>=', now()->subDays(30));
        } elseif ($period === 'custom') {
            $from = (string) $request->query('from', '');
            $to = (string) $request->query('to', '');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $query->where('last_message_at', '>=', \Carbon\Carbon::parse($from, $tz)->startOfDay()->utc());
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $query->where('last_message_at', '<=', \Carbon\Carbon::parse($to, $tz)->endOfDay()->utc());
            }
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'visitor_id' => $session->visitor_id,
            'utm_source' => $session->utm_source,
            'utm_medium' => $session->utm_medium,
            'utm_campaign' => $session->utm_campaign,
            'referrer' => $session->referrer,
            'landing_path' => $session->landing_path,
            'user_agent' => $session->user_agent,
            'lead_id' => $session->lead_id,
            'lead_name' => $session->lead?->name,
            'tokens_in' => (int) $session->messages->sum('tokens_in'),
            'tokens_out' => (int) $session->messages->sum('tokens_out'),
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
}
