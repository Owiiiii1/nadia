<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nadia Glavin | REALTOR® · Keller Williams ONEChicago</title>
  <meta name="description" content="Chicago real estate, handled strategically. Nadia Glavin helps buyers, sellers, and investors navigate Chicago real estate with confidence." />
  <link rel="canonical" href="{{ rtrim(config('app.url'), '/') }}/" />
  <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}/" />
  <meta property="og:title" content="Nadia Glavin | Chicago Real Estate" />
  <meta property="og:description" content="Chicago real estate, handled strategically. Nadia Glavin helps buyers, sellers, and investors navigate Chicago real estate with confidence." />
  <meta property="og:type" content="website" />
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

  <main id="top">
    <section class="hero">
      <div class="wrap hero-grid">
        <div class="hero-copy">
          <div class="eyebrow"><span class="dot"></span>Nadia Glavin · REALTOR® · Keller Williams ONEChicago</div>
          <h1>Chicago real estate, <span class="italic">handled strategically.</span></h1>
          <p>For sellers, buyers, and investors who want sharp financial advice, disciplined negotiation, and a real estate experience that feels deeply personal—not transactional.</p>
          <div class="hero-ctas">
            <a class="btn btn-primary" href="#contact">Sell with Nadia</a>
            <a class="btn btn-ghost" href="#buyers">Explore buying</a>
          </div>
          <ul class="trust">
            <li>Master's in Finance</li>
            <li>Top Producer Mentality</li>
            <li>Pet &amp; Lifestyle Friendly</li>
          </ul>
        </div>
        <div class="hero-visual">
          <div class="portrait">
            <img src="{{ asset('images/nadia-hero.jpg') }}" alt="Nadia Glavin with a client, holding a model home and a house key" />
          </div>
          <div class="strategy-pill">
            <div class="pill-kicker"><span class="dot live"></span>Strategy First</div>
            <p>Pricing, positioning, negotiation, and execution delivered with precision.</p>
            <div class="pill-metrics"><span>Pricing Model</span><span>Staging</span><span>Negotiation</span></div>
          </div>
        </div>
      </div>
    </section>

    <section class="stats" aria-label="Background">
      <div class="wrap stats-grid">
        <div><strong>Chicago</strong><span>Residential real estate expertise</span></div>
        <div><strong>2019</strong><span>Licensed since</span></div>
        <div><strong>Finance</strong><span>Master's degree background</span></div>
        <div><strong>YPN</strong><span>Industry leadership &amp; community</span></div>
      </div>
    </section>

    <section class="band ice" id="sellers">
      <div class="wrap">
        <div class="section-intro">
          <div class="eyebrow">For Sellers</div>
          <h2>Your listing should feel like a launch.</h2>
          <p>Strategic pricing, intentional design preparation, high-impact editorial exposure, and disciplined negotiation. We don't just put a sign in the lawn—we orchestrate buyer demand.</p>
        </div>
        <div class="cards">
          <article class="card">
            <div class="phase">Phase 01</div>
            <h3>POSITION — Price with intention.</h3>
            <p>Deep comparable micro-market analysis, competitive inventory pacing, and buyer psychological anchoring designed to maximize opening weekend momentum.</p>
            <div class="card-foot">Micro-market valuation analysis</div>
          </article>
          <article class="card">
            <div class="phase">Phase 02</div>
            <h3>PRESENT — Make buyers feel it.</h3>
            <p>Editorial staging consultations, architectural photography, and narrative marketing aimed at qualified buyers who can actually close.</p>
            <div class="card-foot">Magazine-caliber visuals &amp; storytelling</div>
          </article>
          <article class="card">
            <div class="phase">Phase 03</div>
            <h3>NEGOTIATE — Protect the outcome.</h3>
            <p>Representation from the first counter through appraisal, inspection, and attorney review, so the result you agreed to is the result you close.</p>
            <div class="card-foot">Advocacy through settlement</div>
          </article>
        </div>
      </div>
    </section>

    <section class="band" id="buyers">
      <div class="wrap">
        <div class="section-intro narrow">
          <div class="eyebrow">For Buyers &amp; Investors</div>
          <h2>Buy the right property—<span class="italic">not just a property.</span></h2>
        </div>
        <div class="split">
          <article class="panel light">
            <div class="tag">Primary Residence</div>
            <h3>Homes that fit the life you're actually building.</h3>
            <p>Finding a place in Chicago is about more than square footage. Neighborhood rhythm, daylight, transit, parks, schools, and what the home will still be worth later.</p>
            <ul>
              <li>MLS search plus private and off-market options</li>
              <li>HOA health and reserve review for condo buyers</li>
              <li>Representation through inspection and attorney review</li>
            </ul>
            <div class="chips">
              <span>Single Family</span><span>Luxury Condos</span><span>Historic Greystones</span>
            </div>
          </article>
          <article class="panel dark" id="invest">
            <div class="tag">Investment Strategy</div>
            <h3>Think like an owner.</h3>
            <p>A finance background applied to cash flow, rent upside, and neighborhood appreciation for small multifamily and buy-and-hold purchases.</p>
            <ul>
              <li>2–4 unit acquisitions and value-add plans</li>
              <li>Rent projections and expense reality checks</li>
              <li>Exit thinking before the offer is written</li>
            </ul>
            <div class="chips">
              <span>Cap Rate Modeling</span><span>Rent Roll Analysis</span><span>Exit Strategy</span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="band white" id="about">
      <div class="wrap about">
        <div class="eyebrow">About Nadia</div>
        <h2>Analytical when it matters.<br/><span class="italic">Human the whole way through.</span></h2>
        <p>Real estate deals are financial decisions and personal ones at the same time. Nadia pairs a master's degree in finance and Chicago transaction experience with hands-on client care, backed by Keller Williams ONEChicago.</p>
        <h3 class="areas-label">Core Neighborhood Specialties</h3>
        <div class="areas">
          <span>Lakeview</span><span>Lincoln Park</span><span>Old Town</span><span>West Loop</span>
          <span>Roscoe Village</span><span>Ukrainian Village</span><span>Wicker Park</span><span>Logan Square</span>
          <span>Lincoln Square</span><span>Andersonville</span><span>Gold Coast</span><span>South Loop</span>
        </div>
      </div>
    </section>

    <section class="band ice" id="bruno">
      <div class="wrap bruno">
        <div class="bruno-frame">
          <img src="{{ asset('images/bruno.jpg') }}" alt="Bruno, a red poodle in a vest, beside an architectural model" />
          <span class="mascot">Official Mascot</span>
        </div>
        <div class="bruno-copy">
          <div class="eyebrow">Meet the Team</div>
          <h2>Bruno, Chief Barketing Officer.</h2>
          <p>Bruno oversees client happiness, sidewalk walkability, dog-park adjacency, and quality control over the treat table at open houses.</p>
          <blockquote>
            <p>“His opinions on pricing are not legally binding, but his vibes are 100% accurate.”</p>
            <cite>Keller Williams ONEChicago</cite>
          </blockquote>
          <p class="coming">Coming soon: Bruno Approved neighborhood picks, dog-friendly buildings, patios, and parks.</p>
        </div>
      </div>
    </section>

    <section class="contact-wrap" id="contact">
      <div class="wrap">
        <div class="contact">
          <div class="contact-copy">
            <div class="tag on-dark">Let's Talk Real Estate</div>
            <h2>What are you planning next?</h2>
            <p>Whether you are months out and gathering market intelligence, or ready to prepare a listing this week, a conversation with Nadia is direct and grounded in the numbers.</p>
            <div class="coffee">
              <span aria-hidden="true">☕</span>
              <div>
                <strong>Coffee or a call</strong>
                <p>A neighborhood cafe, or Zoom if that is easier.</p>
              </div>
            </div>
          </div>
          <div class="contact-form">
            @if (session('lead_success'))
              <div class="lead-thanks" id="lead-thanks">
                <div class="lead-thanks-mark">✓</div>
                <div>
                  <h4>Thank you, {{ session('lead_success') }}. I'll be in touch shortly.</h4>
                  <p>Nadia will review your details and send over initial thoughts.</p>
                </div>
              </div>
            @else
              <form class="lead-card" id="lead-form" method="post" action="{{ route('leads.store') }}">
                @csrf
                <input type="hidden" name="started_at" value="{{ $startedAt }}">
                <input type="hidden" name="visitor_id" id="visitor_id" value="{{ old('visitor_id') }}">
                <input type="hidden" name="utm_source" id="utm_source" value="{{ old('utm_source') }}">
                <input type="hidden" name="utm_medium" id="utm_medium" value="{{ old('utm_medium') }}">
                <input type="hidden" name="utm_campaign" id="utm_campaign" value="{{ old('utm_campaign') }}">
                <input type="hidden" name="utm_term" id="utm_term" value="{{ old('utm_term') }}">
                <input type="hidden" name="utm_content" id="utm_content" value="{{ old('utm_content') }}">
                <input type="hidden" name="referrer" id="referrer" value="{{ old('referrer') }}">
                <input type="hidden" name="landing_path" id="landing_path" value="{{ old('landing_path') }}">
                <div class="hp" aria-hidden="true">
                  <label for="website">Website</label>
                  <input id="website" name="website" type="text" tabindex="-1" autocomplete="off" value="">
                </div>
                <div class="lead-head">
                  <h3>Request a Conversation</h3>
                  <p>Direct, confidential response within 24 hours.</p>
                </div>
                @if (session('lead_error'))
                  <p class="lead-banner">{{ session('lead_error') }}</p>
                @endif
                <div class="form-row">
                  <div>
                    <label for="name">Name *</label>
                    <input id="name" name="name" type="text" placeholder="Your name" value="{{ old('name') }}" required maxlength="120">
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                  </div>
                  <div>
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required maxlength="190">
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                  </div>
                </div>
                <div class="form-row">
                  <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="tel" placeholder="(312) 000-0000" value="{{ old('phone') }}" maxlength="40">
                    @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                  </div>
                  <div>
                    <label for="goal">I'm looking to… *</label>
                    <select id="goal" name="goal" required>
                      <option value="sell" @selected(old('goal', 'sell') === 'sell')>Sell a property</option>
                      <option value="buy" @selected(old('goal') === 'buy')>Buy a home</option>
                      <option value="invest" @selected(old('goal') === 'invest')>Invest in Chicago</option>
                      <option value="consult" @selected(old('goal') === 'consult')>Talk through my options</option>
                    </select>
                    @error('goal')<span class="field-error">{{ $message }}</span>@enderror
                  </div>
                </div>
                <label for="message">Anything else?</label>
                <textarea id="message" name="message" rows="3" maxlength="2000" placeholder="Property, timeline, or neighborhoods">{{ old('message') }}</textarea>
                @error('message')<span class="field-error">{{ $message }}</span>@enderror
                <button class="btn btn-primary" type="submit">Start the conversation</button>
                <p class="fine-print">By submitting, you agree to the <a href="{{ route('privacy') }}">Privacy Policy</a>. No spam, ever.</p>
              </form>
            @endif
          </div>
        </div>
      </div>
    </section>
  </main>

  @include('partials.site-footer')
  @include('partials.tracking', ['placement' => 'body_end'])
  @if ($chatAvailable)
    <script type="application/json" id="nadia-chat-boot">@json(['greeting' => $chatGreeting])</script>
    @vite(['resources/css/chat-widget.css', 'resources/js/chat-widget.js'])
  @endif
</body>
</html>
