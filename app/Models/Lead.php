<?php

namespace App\Models;

use App\Enums\LeadGoal;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'goal',
        'message',
        'source',
        'status',
        'admin_note',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer',
        'landing_path',
        'visitor_id',
        'chat_session_id',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'goal' => LeadGoal::class,
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
        ];
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }
}
