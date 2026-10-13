@php
    $navPrefix = request()->routeIs('landing') ? '' : url('/');
    $homeHref = $navPrefix === '' ? '#top' : url('/');
@endphp
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="{{ $homeHref }}">
      <span class="brand-name">Nadia Glavin</span>
      <span class="brand-sub">Chicago Real Estate · KW ONEChicago</span>
    </a>
    <nav class="nav-links" aria-label="Main">
      <a href="{{ $navPrefix }}#sellers">Sell</a>
      <a href="{{ $navPrefix }}#buyers">Buy</a>
      <a href="{{ $navPrefix }}#invest">Invest</a>
      <a href="{{ $navPrefix }}#about">About</a>
      <a href="{{ $navPrefix }}#bruno">Bruno</a>
    </nav>
    <a class="btn btn-primary" href="{{ $navPrefix }}#contact">Book a consultation</a>
  </div>
</header>
