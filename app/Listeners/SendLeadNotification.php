<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Services\Notifications\LeadNotifier;

class SendLeadNotification
{
    public function handle(LeadCreated $event): void
    {
        $leadId = $event->lead->id;

        dispatch(function () use ($leadId): void {
            $lead = Lead::query()->find($leadId);

            if ($lead instanceof Lead) {
                app(LeadNotifier::class)->send($lead);
            }
        })->afterResponse();
    }
}
