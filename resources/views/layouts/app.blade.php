<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<meta name="description" content="@yield('description', 'Easily block spam from Discord, Telegram and other invite links')" />
@hasSection('robots')
<meta name="robots" content="@yield('robots')" />
@endif
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}" />
<meta property="og:title" content="@yield('og:title', 'Spam Destruction - Spam Annihilator')" />
<meta property="og:site_name" content="Spam Annihilator" />
<meta property="og:description" content="@yield('og:description', 'Easily block spam from Discord, Telegram and other invite links')" />
<meta property="og:url" content="{{ url('/') }}" />
<meta name="twitter:card" content="summary_large_image" />

@section('structured-data')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@graph": [
    {
      "@@type": "WebPage",
      "@@id": "{{ url('/') }}",
      "url": "{{ url('/') }}",
      "name": "Spam Destruction - Spam Annihilator",
      "description": "Easily block spam from Discord, Telegram and other invite links. Powered by the Spam Destroyer for WordPress.",
      "datePublished": "2025-01-01T00:00:00+00:00",
      "dateModified": "2025-01-01T00:00:00+00:00",
      "author": {
        "@@id": "{{ url('/') }}#author"
      },
      "isPartOf": {
        "@@id": "{{ url('/') }}#website"
      },
      "inLanguage": "{{ str_replace('_', '-', app()->getLocale()) }}"
    },
    {
      "@@type": "WebSite",
      "@@id": "{{ url('/') }}#website",
      "url": "{{ url('/') }}",
      "name": "Spam Annihilator",
      "description": "Easily block spam from Discord, Telegram and other invite links.",
      "inLanguage": "{{ str_replace('_', '-', app()->getLocale()) }}"
    }
  ]
}
</script>
@show
<link rel="preload" href="/fonts/open-sans.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/style.css" as="style">
<link href="/style.css" rel="stylesheet" />
<title>@yield('title', 'Spam Destruction - Spam Annihilator')</title>
<link rel="canonical" href="@yield('canonical', url('/'))" />
<link rel='dns-prefetch' href='//{{ parse_url(url('/'), PHP_URL_HOST) }}'>
<link rel="icon" type="image/png" href="/images/favicon.png" sizes="32x32">
<link rel="shortcut icon" href="/favicon.ico">
<meta name="theme-color" content="#1483c8">
<meta name="generator" content="Laravel" />
@stack('head')
</head>
<body class="@yield('body-class', '')">

<header class="accent">
  <div class="wrapper">
    <img 
        src="@yield('header-avatar-src', '/images/spam-attack.avif')"
        alt="@yield('header-avatar-alt', 'Robot being attacked by human')" 
        fetchpriority="high"
        class="header-avatar"
        width="450"
        height="450"
    >
    <h1>@yield('page-title', 'Spam Annihilator')</h1>
    <p>@yield('page-tagline', 'Easily block spam from Discord, Telegram and other invite links')</p>

    @hasSection('header-button')
    <p>
        @yield('header-button')
    </p>
    @endif

    @section('header-form')
    <form method="POST" action="{{ route('redirect.store') }}">
        @csrf
        <p>
            <label for="to">Link to protect</label>
            <input type="url" name="to" id="to" placeholder="https://discord.gg/example" required>
        </p>
        <p>
            <input class="button" type="submit" value="Create spam-protected link">
        </p>
    </form>
    @show
  </div>
</header>

<main>
@yield('content')
</main>

<footer id="footer">
  <div class="wrapper">
    Copyright &copy; Ryan Hellyer {{ date('Y') }}
    <ul>
        <li><a href="/privacy-policy/" rel="nofollow">Privacy Policy</a></li>
        <li><a href="/legal-notice/" rel="nofollow">Legal Notice</a></li>
    </ul>
  </div>
</footer>

</body>
</html>
