<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    protected $fillable = [
        'uuid',
        'visitor_id',
        'ip_hash',
        'user_agent',
        'messages_count',
        'lead_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'landing_path',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'messages_count' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatSession $session): void {
            if (! is_string($session->uuid) || $session->uuid === '') {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
