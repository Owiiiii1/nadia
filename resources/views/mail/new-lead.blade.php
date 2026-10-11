<x-mail::message>
# New lead — {{ $source }}

{{ $lead->name }}

@if ($lead->email)
{{ $lead->email }}
@endif

@if ($lead->phone)
{{ $lead->phone }}
@endif

Goal: {{ $goal }}

@if ($lead->message)
{{ \Illuminate\Support\Str::limit($lead->message, 300, '') }}
@endif

@if ($lead->utm_source || $lead->utm_campaign)
UTM: {{ $utm }}
@endif

<x-mail::button :url="$url">
Open in admin
</x-mail::button>
</x-mail::message>
