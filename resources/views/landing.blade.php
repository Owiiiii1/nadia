<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nadia Glavin | Chicago Real Estate</title>
  <meta name="description" content="Chicago real estate, handled strategically. Nadia Glavin helps buyers, sellers, and investors navigate Chicago real estate with confidence." />
  <link rel="canonical" href="https://nadia.owlsolutions.net/" />
  <meta property="og:title" content="Nadia Glavin | Chicago Real Estate" />
  <meta property="og:description" content="Chicago real estate, handled strategically. Nadia Glavin helps buyers, sellers, and investors navigate Chicago real estate with confidence." />
  <meta property="og:type" content="website" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  @vite(['resources/css/landing.css', 'resources/js/landing.js'])
  @stack('head_codes')
</head>
<body>
  @stack('body_start_codes')
  <header class="nav">
    <div class="wrap nav-inner">
      <a class="brand" href="#top">Nadia Glavin<small>Chicago Real Estate</small></a>
      <nav class="nav-links">
        <a href="#sell">Sell</a>
        <a href="#buy">Buy</a>
        <a href="#invest">Invest</a>
        <a href="#about">About</a>
        <a href="#bruno">Bruno</a>
        <a class="btn btn-dark" href="#contact">Book a consultation</a>
      </nav>
    </div>
  </header>

  <main id="top">
    <section class="wrap hero">
      <div class="hero-copy">
        <div class="eyebrow">Nadia Glavin · REALTOR® · Keller Williams ONEChicago</div>
        <h1>Chicago real estate, handled strategically.</h1>
        <p>For sellers, buyers, and investors who want sharp advice, strong negotiation, and a real estate experience that feels personal—not transactional.</p>
        <div class="hero-ctas">
          <a class="btn btn-dark" href="#contact">Sell with Nadia</a>
          <a class="btn" href="#buy">Explore buying</a>
        </div>
      </div>
      <div class="hero-art" aria-label="Chicago skyline placeholder">
        <div class="photo-label">Replace with your hero portrait</div>
        <div class="skyline"></div>
        <div class="hero-note">
          <strong>Strategy first.</strong>
          <span>Pricing, positioning, negotiation, and execution.</span>
        </div>
      </div>
    </section>

    <section class="wrap">
      <div class="stats">
        <div class="stat"><strong>Chicago</strong><span>Residential real estate expertise</span></div>
        <div class="stat"><strong>2019</strong><span>Licensed since</span></div>
        <div class="stat"><strong>Finance</strong><span>Master's degree background</span></div>
        <div class="stat"><strong>YPN</strong><span>Industry leadership & community</span></div>
      </div>
    </section>

    <section class="section alt" id="sell">
      <div class="wrap">
        <div class="section-head">
          <div>
            <div class="eyebrow">For sellers</div>
            <h2>Your listing should feel like a launch.</h2>
          </div>
          <p>Great selling is more than putting a property on the MLS. I focus on pricing, preparation, presentation, exposure, and negotiation so your property enters the market with a clear strategy—and a reason for buyers to act.</p>
        </div>
        <div class="cards">
          <article class="card">
            <div class="num">01 · POSITION</div>
            <h3>Price with intention.</h3>
            <p>We look at the market, competing inventory, buyer psychology, timing, and your goals to create a pricing strategy—not just a number.</p>
          </article>
          <article class="card">
            <div class="num">02 · PRESENT</div>
            <h3>Make buyers feel it.</h3>
            <p>Professional photography, video, social media, agent outreach, open houses, and polished property storytelling designed to create attention.</p>
          </article>
          <article class="card">
            <div class="num">03 · NEGOTIATE</div>
            <h3>Protect the outcome.</h3>
            <p>From offer terms through attorney review, inspection, appraisal, and closing, I stay focused on leverage, communication, and getting the deal across the finish line.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="buy">
      <div class="wrap">
        <div class="section-head">
          <div>
            <div class="eyebrow">For buyers</div>
            <h2>Buy the right property—not just a property.</h2>
          </div>
          <p>Whether it’s your first condo, your next home, or a multifamily purchase, I help you evaluate the numbers, the building, the neighborhood, the resale story, and the terms that matter before you commit.</p>
        </div>
        <div class="portfolio">
          <div class="panel">
            <div class="tag">Primary residence</div>
            <h3>Homes that fit the life you’re actually building.</h3>
            <p>Neighborhood fit, monthly costs, association health, inspection, financing, resale potential, and day-to-day livability.</p>
          </div>
          <div class="panel dark" id="invest">
            <div class="tag">Investment</div>
            <h3>Think like an owner.</h3>
            <p>Rent potential, expenses, cap rate, cash flow, financing, tenant considerations, and long-term value.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section alt" id="about">
      <div class="wrap">
        <div class="section-head">
          <div>
            <div class="eyebrow">About Nadia</div>
            <h2>Analytical when it matters. Human the whole way through.</h2>
          </div>
          <p>I bring a finance background, years of Chicago real estate experience, and a highly hands-on approach to every transaction. My clients come to me for market knowledge—but stay because they know I care deeply about the outcome.</p>
        </div>
        <div class="areas">
          <span class="chip">Lakeview</span>
          <span class="chip">Lincoln Park</span>
          <span class="chip">Old Town</span>
          <span class="chip">West Loop</span>
          <span class="chip">Roscoe Village</span>
          <span class="chip">Ukrainian Village</span>
          <span class="chip">Wicker Park</span>
          <span class="chip">Logan Square</span>
          <span class="chip">Lincoln Square</span>
          <span class="chip">Andersonville</span>
          <span class="chip">Gold Coast</span>
          <span class="chip">South Loop</span>
        </div>
      </div>
    </section>

    <section class="section" id="bruno">
      <div class="wrap bruno">
        <div class="paw" aria-label="Bruno mascot placeholder">
          <svg viewBox="0 0 512 512" role="img" aria-label="Paw icon">
            <ellipse cx="147" cy="138" rx="58" ry="78"/>
            <ellipse cx="365" cy="138" rx="58" ry="78"/>
            <ellipse cx="81" cy="267" rx="52" ry="68" transform="rotate(-18 81 267)"/>
            <ellipse cx="431" cy="267" rx="52" ry="68" transform="rotate(18 431 267)"/>
            <path d="M256 219c-86 0-151 78-151 153 0 65 51 95 98 77 29-11 40-22 53-22s24 11 53 22c47 18 98-12 98-77 0-75-65-153-151-153z"/>
          </svg>
        </div>
        <div class="bruno-copy">
          <div class="eyebrow">Meet the team</div>
          <h2>Bruno, Chief Barketing Officer.</h2>
          <p>Every serious real estate brand needs someone responsible for morale. Bruno handles client happiness, neighborhood walkability inspections, and the occasional quality-control review of Nadia’s marketing materials.</p>
          <div class="quote">“His opinions on pricing are not binding.”</div>
          <p style="font-size:14px;margin-top:18px">Coming soon: <strong>Bruno Approved 🐾</strong> neighborhood picks, dog-friendly buildings, patios, parks, and Chicago favorites.</p>
        </div>
      </div>
    </section>

    <section class="wrap" id="contact">
      <div class="cta">
        <div>
          <div class="eyebrow" style="color:#cbb7a1">Let’s talk real estate</div>
          <h2>What are you planning next?</h2>
          <p>Thinking about selling, buying, or investing in Chicago? Tell me what you’re considering and I’ll help you map out the smartest next move.</p>
        </div>
        @if (session('lead_success'))
        <div class="cta-box" id="lead-thanks">
          <p class="lead-success">Thank you, {{ session('lead_success') }}. I'll be in touch shortly.</p>
        </div>
        @else
        <form class="cta-box" id="lead-form" method="post" action="{{ route('leads.store') }}">
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
          @if (session('lead_error'))
            <p class="lead-banner">{{ session('lead_error') }}</p>
          @endif
          <label for="name">Name</label>
          <input id="name" name="name" type="text" placeholder="Your name" value="{{ old('name') }}" required maxlength="120">
          @error('name')<span class="field-error">{{ $message }}</span>@enderror
          <label for="email">Email</label>
          <input id="email" name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required maxlength="190">
          @error('email')<span class="field-error">{{ $message }}</span>@enderror
          <label for="phone">Phone</label>
          <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="40">
          @error('phone')<span class="field-error">{{ $message }}</span>@enderror
          <label for="goal">I’m looking to…</label>
          <select id="goal" name="goal" required>
            <option value="sell" @selected(old('goal', 'sell') === 'sell')>Sell a property</option>
            <option value="buy" @selected(old('goal') === 'buy')>Buy a home</option>
            <option value="invest" @selected(old('goal') === 'invest')>Invest in Chicago</option>
            <option value="consult" @selected(old('goal') === 'consult')>Talk through my options</option>
          </select>
          @error('goal')<span class="field-error">{{ $message }}</span>@enderror
          <label for="message">Anything else?</label>
          <textarea id="message" name="message" rows="3" maxlength="2000">{{ old('message') }}</textarea>
          @error('message')<span class="field-error">{{ $message }}</span>@enderror
          <button class="btn btn-dark" type="submit">Start the conversation</button>
        </form>
        @endif
      </div>
    </section>
  </main>

  <footer>
    <div class="wrap footer-inner">
      <div><strong>Nadia Glavin, REALTOR®</strong><br/>Keller Williams ONEChicago · Chicago, Illinois</div>
      <div>Equal Housing Opportunity · Add brokerage-required license, office, privacy, and legal disclosures before publishing.</div>
    </div>
  </footer>
  @stack('body_end_codes')
  @if ($chatAvailable)
    <script type="application/json" id="nadia-chat-boot">@json(['greeting' => $chatGreeting])</script>
    @vite(['resources/css/chat-widget.css', 'resources/js/chat-widget.js'])
  @endif
</body>
</html>
