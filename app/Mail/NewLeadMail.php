<?php

namespace App\Mail;

use App\Enums\LeadSource;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        $goal = $this->lead->goal instanceof \BackedEnum ? $this->lead->goal->value : (string) $this->lead->goal;
        $replyTo = [];

        if (filled($this->lead->email)) {
            $replyTo[] = new Address((string) $this->lead->email, (string) $this->lead->name);
        }

        return new Envelope(
            replyTo: $replyTo,
            subject: 'New lead: '.$this->lead->name.' ('.$goal.')',
        );
    }

    public function content(): Content
    {
        $goal = $this->lead->goal instanceof \BackedEnum ? $this->lead->goal->value : (string) $this->lead->goal;

        return new Content(
            markdown: 'mail.new-lead',
            with: [
                'source' => $this->lead->source === LeadSource::Chat ? 'Chat' : 'Form',
                'goal' => $goal,
                'url' => rtrim((string) config('app.url'), '/').'/leads/'.$this->lead->id,
                'utm' => trim((string) $this->lead->utm_source).'/'.trim((string) $this->lead->utm_campaign),
            ],
        );
    }
}
