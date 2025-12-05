<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}" />
<meta property="og:title" content="Spam Destruction - Spam Annihilator" />
<meta property="og:site_name" content="Spam Annihilator" />
<meta property="og:description" content="This service hasn't proven very popular (partially due to lack of promotion) and so it has been deprecated. All old links will continue working however! Powered by the Spam Destroyer for WordPress" />
<meta property="og:url" content="{{ url('/') }}" />
<meta name="twitter:card" content="summary_large_image" />

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
<link rel="preload" href="/fonts/open-sans.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/style.css" as="style">
<link href="/style.css" rel="stylesheet" />
<title>Spam Destruction - Spam Annihilator</title>
<link rel="canonical" href="{{ url('/') }}" />
<link rel='dns-prefetch' href='//{{ parse_url(url('/'), PHP_URL_HOST) }}'>
<link rel="icon" type="image/png" href="/images/favicon.png" sizes="32x32">
<link rel="shortcut icon" href="/favicon.ico">
<meta name="theme-color" content="#1483c8">
<meta name="generator" content="Laravel" />
</head>
<body>

<header class="accent">
  <div class="wrapper">
    <img 
        src="/images/spam-attack.avif"
        alt="Robot being attacked by human" 
        fetchpriority="high"
        class="header-avatar"
        width="450"
        height="450"
    >
    <h1>Spam Annihilator</h1>
    <p>Easily block spam from Discord, Telegram and other invite links</p>

    <form method="POST">
        <p>
            <label for="from">From (slug)</label>
            <input type="text" name="from" id="from" placeholder="e.g., my-group" required>
        </p>
        <p>
            <label for="to">To (destination URL)</label>
            <input type="url" name="to" id="to" placeholder="https://discord.gg/example" required>
        </p>
        <p>
            <input class="button" type="submit" value="Create spam-protected link">
        </p>
    </form>
  </div>
</header>

<main>
  <section class="primary testimonials">
    <div class="wrapper">
        <h1>Testimonials</h1>

        <blockquote>
            <img src="/images/craig-sailor.avif" width="300" height="300" alt="Craig Sailor">
            <p>Stunning service! Spam made our large Telegram group unusable. Switching to Spam Annihilator instantly brought us zero spam. Members are thrilled!</p>
            <cite>Craig Sailor – Berlin Coworking Club</cite>
        </blockquote>

        <blockquote>
            <img src="/images/georg-preller.avif" width="300" height="300" alt="Georg Preller">
            <p>Spam was ruining the Abenteuer Freundschaft - Wandern / Hiking group. Spam Annihilator gave us zero spam immediately. Highly recommended!</p>
            <cite>Georg Preller – Abenteuer Freundschaft</cite>
        </blockquote>

        <blockquote>
            <img src="/images/liubov-kurilova.avif" width="300" height="300" alt="Liubov Kurilova">
            <p>Spammers constantly interrupted my DMs and comments, making it tough to connect with followers. Thanks to Spam Annihilator, my Kurilove_Art channel now has instant, zero-spam protection. I can finally focus on creating!</p>
            <cite>Liubov Kurilova – Kurilove_Art</cite>
        </blockquote>
    </div>
  </section>

  <section class="accent">
    <div class="wrapper">
      <h1>WordPress Spam Destroyer</h1>
      <p>Stops automated spam while remaining as unobtrusive as possible to regular commenters. Simply install, and enjoy a spam free website. Perfect if you're sick of spam. Works via JavaScript with cookies and hidden fields to block bots without bothering real users.</p>
      <p>
        <a href="https://wordpress.org/plugins/spam-destroyer/" class="button">Download Spam Destroyer WordPress plugin</a>
      </p>
    </div>
  </section>
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