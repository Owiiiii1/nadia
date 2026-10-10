<?php

namespace App\Services\Chat;

use App\Ai\ChatClientFactory;
use App\Ai\ChatReply;
use App\Enums\LeadGoal;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Lead;
use App\Support\AssistantDefaults;
use App\Support\IpHasher;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ChatMessenger
{
    public const FALLBACK = "Sorry, I'm having trouble answering right now. You can leave your details in the form below and Nadia will get back to you.";

    public const SESSION_LIMIT = 'This chat has reached its limit — please use the contact form and Nadia will reply personally.';

    public const DAILY_LIMIT = 'Too many messages today. Please use the contact form and Nadia will reply personally.';

    public function __construct(
        private ChatClientFactory $clients,
        private Settings $settings,
    ) {}

    public function handle(Request $request, ?string $sessionUuid, string $message): ChatTurn
    {
        $session = $sessionUuid
            ? ChatSession::query()->where('uuid', $sessionUuid)->first()
            : null;

        if ($session !== null) {
            $userMessages = ChatMessage::query()
                ->where('chat_session_id', $session->id)
                ->where('role', 'user')
                ->count();

            if ($userMessages >= (int) config('nadia.chat.session_user_messages', 30)) {
                return new ChatTurn(null, null, false, 429, self::SESSION_LIMIT, 'session_limit');
            }
        }

        if ($this->overDailyLimit($request)) {
            return new ChatTurn(null, null, false, 429, self::DAILY_LIMIT);
        }

        if ($session === null) {
            $session = $this->createSession($request, $sessionUuid);
        }

        $this->storeUserMessage($session, $message);

        $history = ChatMessage::query()
            ->where('chat_session_id', $session->id)
            ->orderByDesc('id')
            ->limit((int) config('nadia.chat.context_messages', 20))
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $row): array => [
                'role' => $row->role,
                'content' => $row->content,
            ])
            ->values()
            ->all();

        $prompt = AssistantDefaults::forModel((string) $this->settings->get(
            'assistant_system_prompt',
            AssistantDefaults::systemPrompt(),
        ));

        try {
            $reply = $this->clients->make()->reply(
                $prompt,
                $history,
                (int) config('nadia.chat.max_output_tokens', 500),
            );
        } catch (Throwable $exception) {
            $this->storeAssistant($session, self::FALLBACK, null, $this->safeError($exception));

            return new ChatTurn($session->uuid, self::FALLBACK, false);
        }

        [$clean, $blocks] = $this->extractLeadMarkup($reply->text);
        $captured = $this->applyLeadBlocks($session, $blocks);
        $this->storeAssistant($session, $clean, $reply, null);

        return new ChatTurn($session->uuid, $clean, $captured);
    }

    private function overDailyLimit(Request $request): bool
    {
        $start = now('UTC')->startOfDay();
        $hash = IpHasher::hash((string) $request->ip());
        $byIp = ChatMessage::query()
            ->where('role', 'user')
            ->where('created_at', '>=', $start)
            ->whereHas('session', fn ($query) => $query->where('ip_hash', $hash))
            ->count();

        if ($byIp >= (int) config('nadia.chat.daily_ip_limit', 100)) {
            return true;
        }

        $global = ChatMessage::query()
            ->where('role', 'user')
            ->where('created_at', '>=', $start)
            ->count();

        return $global >= (int) config('nadia.chat.daily_global_limit', 1500);
    }

    private function createSession(Request $request, ?string $sessionUuid): ChatSession
    {
        if ($sessionUuid !== null) {
            $existing = ChatSession::query()->where('uuid', $sessionUuid)->first();
            if ($existing) {
                return $existing;
            }
        }

        $touch = json_decode((string) $request->cookie('nadia_ft'), true);
        $touch = is_array($touch) ? $touch : [];
        $visitor = $request->cookie('nadia_vid');

        return ChatSession::query()->create([
            'uuid' => $sessionUuid ?: (string) Str::uuid(),
            'visitor_id' => is_string($visitor) && Str::isUuid($visitor) ? $visitor : null,
            'ip_hash' => IpHasher::hash((string) $request->ip()),
            'user_agent' => $this->clip($request->userAgent(), 500),
            'utm_source' => $this->clip($touch['utm_source'] ?? null, 190),
            'utm_medium' => $this->clip($touch['utm_medium'] ?? null, 190),
            'utm_campaign' => $this->clip($touch['utm_campaign'] ?? null, 190),
            'referrer' => $this->clip($touch['referrer'] ?? null, 500),
            'landing_path' => $this->clip($touch['landing_path'] ?? null, 500),
            'last_message_at' => now(),
        ]);
    }

    private function storeUserMessage(ChatSession $session, string $message): void
    {
        $session->messages()->create([
            'role' => 'user',
            'content' => $message,
            'created_at' => now(),
        ]);
        $this->touch($session);
    }

    private function storeAssistant(ChatSession $session, string $content, ?ChatReply $reply, ?string $error): void
    {
        $setting = app(\App\Support\ChatAvailability::class)->setting();

        $session->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'provider' => $setting?->provider,
            'model' => $setting?->active_model,
            'tokens_in' => $reply?->tokensIn,
            'tokens_out' => $reply?->tokensOut,
            'latency_ms' => $reply?->latencyMs,
            'error' => $error,
            'created_at' => now(),
        ]);
        $this->touch($session);
    }

    private function touch(ChatSession $session): void
    {
        $session->messages_count = (int) $session->messages()->count();
        $session->last_message_at = now();
        $session->save();
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function extractLeadMarkup(string $text): array
    {
        $blocks = [];
        $clean = preg_replace_callback('/<lead>(.*?)<\/lead>/si', function (array $match) use (&$blocks): string {
            $blocks[] = $match[1];

            return '';
        }, $text);
        $clean = is_string($clean) ? $clean : $text;
        $clean = preg_replace('/<lead\b[\s\S]*$/i', '', $clean);

        return [trim(is_string($clean) ? $clean : $text), $blocks];
    }

    /**
     * @param  list<string>  $blocks
     */
    private function applyLeadBlocks(ChatSession $session, array $blocks): bool
    {
        $captured = false;

        foreach ($blocks as $raw) {
            $parsed = $this->parseLeadBlock($session, $raw);
            if ($parsed === null) {
                continue;
            }
            $this->applyLead($session, $parsed);
            $captured = true;
            $session->refresh();
        }

        return $captured;
    }

    /**
     * @return array{name: string, email: ?string, phone: ?string, goal: string, summary: ?string}|null
     */
    private function parseLeadBlock(ChatSession $session, string $raw): ?array
    {
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            $this->warn($session, 'invalid_json');

            return null;
        }

        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        $email = $this->nullableString($data['email'] ?? null, 255);
        $phone = $this->nullableString($data['phone'] ?? null, 40);

        if ($name === '' || ($email === null && $phone === null)) {
            $this->warn($session, 'missing_contact');

            return null;
        }

        if ($email !== null && Validator::make(['email' => $email], ['email' => ['email:rfc']])->fails()) {
            $this->warn($session, 'invalid_email');

            return null;
        }

        $goal = is_string($data['goal'] ?? null) ? strtolower(trim($data['goal'])) : '';
        if (LeadGoal::tryFrom($goal) === null) {
            $goal = LeadGoal::Consult->value;
        }

        return [
            'name' => mb_substr($name, 0, 255),
            'email' => $email,
            'phone' => $phone,
            'goal' => $goal,
            'summary' => $this->clip(is_string($data['summary'] ?? null) ? $data['summary'] : null, 2000),
        ];
    }

    /**
     * @param  array{name: string, email: ?string, phone: ?string, goal: string, summary: ?string}  $parsed
     */
    private function applyLead(ChatSession $session, array $parsed): void
    {
        $lead = $session->lead_id ? Lead::query()->find($session->lead_id) : null;

        if ($lead) {
            $changes = [];
            if ($this->blank($lead->name)) {
                $changes['name'] = $parsed['name'];
            }
            if ($this->blank($lead->email) && $parsed['email'] !== null) {
                $changes['email'] = $parsed['email'];
            }
            if ($this->blank($lead->phone) && $parsed['phone'] !== null) {
                $changes['phone'] = $parsed['phone'];
            }
            if ($lead->goal === null) {
                $changes['goal'] = $parsed['goal'];
            }
            if ($this->blank($lead->message) && $parsed['summary'] !== null) {
                $changes['message'] = $parsed['summary'];
            }
            if ($changes !== []) {
                $lead->update($changes);
            }

            return;
        }

        $lead = Lead::query()->create([
            'name' => $parsed['name'],
            'email' => $parsed['email'],
            'phone' => $parsed['phone'],
            'goal' => $parsed['goal'],
            'message' => $parsed['summary'],
            'source' => LeadSource::Chat,
            'status' => LeadStatus::New,
            'utm_source' => $session->utm_source,
            'utm_medium' => $session->utm_medium,
            'utm_campaign' => $session->utm_campaign,
            'referrer' => $session->referrer,
            'landing_path' => $session->landing_path,
            'visitor_id' => $session->visitor_id,
            'chat_session_id' => $session->id,
            'ip_hash' => $session->ip_hash,
            'user_agent' => $session->user_agent,
        ]);

        $session->lead_id = $lead->id;
        $session->save();
        event(new LeadCreated($lead));
    }

    private function warn(ChatSession $session, string $reason): void
    {
        Log::warning('Chat lead block ignored', [
            'session_id' => $session->id,
            'reason' => $reason,
        ]);
    }

    private function safeError(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $message = preg_replace('/([?&]key=)[^&\s]+/i', '$1redacted', $message) ?? $message;
        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer redacted', $message) ?? $message;
        $message = preg_replace('/sk-[A-Za-z0-9_\-]+/', 'redacted', $message) ?? $message;
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_substr($message !== '' ? $message : 'Provider request failed', 0, 500);
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return $this->clip($value, $limit);
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

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
