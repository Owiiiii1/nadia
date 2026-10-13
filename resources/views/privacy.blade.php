<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Privacy Policy | Nadia Glavin</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet" />
  @vite(['resources/css/landing.css', 'resources/js/landing.js'])
  @unless (auth()->check())
    @vite(['resources/js/tracker.js'])
  @endunless
  @include('partials.tracking', ['placement' => 'head'])
</head>
<body>
  @include('partials.tracking', ['placement' => 'body_start'])
  @include('partials.site-header')
  <main class="wrap privacy">
    <!-- Draft — review with brokerage/legal before publishing -->
    <h1>Privacy Policy</h1>
    <p>Updated October 11, 2026.</p>
    <p>This page describes what the Nadia Glavin website collects, why, and how long some of it is kept.</p>
    <h2>What we collect</h2>
    <ul>
      <li>The contact form: name, email, phone, goal, and message.</li>
      <li>The chat assistant: the conversation you have with it.</li>
      <li>First-party analytics: cookies <code>nadia_vid</code>, <code>nadia_sid</code>, and <code>nadia_ft</code>, the pages you open, the site that referred you, and a general device type (desktop, mobile, or tablet).</li>
    </ul>
    <p>We do not store your IP address. Where a request needs to be limited or tied to a day, we store only an irreversible hash.</p>
    <h2>Why</h2>
    <p>We use this information to reply to your inquiry and to understand which parts of the site are useful.</p>
    <h2>Other services</h2>
    <p>If chat is turned on, your messages are sent to the AI provider selected in the site settings so the assistant can answer. Meta and Google tags run only when a pixel or measurement ID is connected in the admin.</p>
    <h2>How long</h2>
    <p>Page views and on-site events are kept for 12 months, then deleted.</p>
    <h2>Requests</h2>
    <p>To ask about your information, write to [contact email].</p>
  </main>
  @include('partials.site-footer')
  @include('partials.tracking', ['placement' => 'body_end'])
  @if ($chatAvailable)
    <script type="application/json" id="nadia-chat-boot">@json(['greeting' => $chatGreeting])</script>
    @vite(['resources/css/chat-widget.css', 'resources/js/chat-widget.js'])
  @endif
</body>
</html>
