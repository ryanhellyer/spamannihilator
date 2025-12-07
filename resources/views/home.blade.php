@extends('layouts.app')

@section('body-class', 'home')
@section('page-title', 'Spam Destroyer')
@section('page-tagline', 'Block invite link spam from Discord, Telegram and others')

@section('content')
	<section class="primary testimonials">
		<div class="wrapper">
			<h1>Testimonials</h1>

			<blockquote>
				<img src="/images/craig-sailor.avif" width="300" height="300" alt="Craig Sailor">
				<p>Stunning service! Spam made our large Telegram group unusable. Switching to Spam Destroyer instantly brought us zero spam. Members are thrilled!</p>
				<cite>Craig Sailor – Berlin Coworking Club</cite>
			</blockquote>

			<blockquote>
				<img src="/images/georg-preller.avif" width="300" height="300" alt="Georg Preller">
				<p>Spam has threatened the telegram groups of Abenteuer Freundschaft. Spam Destroyer stopped spam bots immediatly down to zero. Also, our wordpress comment section is spam-free now. Thank you for the releave!</p>
				<cite>Georg Preller – <a href="https://www.abenteuer-freundschaft.de">Abenteuer Freundschaft</a></cite>
			</blockquote>

			<blockquote>
				<img src="/images/liubov-kurilova.avif" width="300" height="300" alt="Liubov Kurilova">
				<p>Spammers constantly interrupted my DMs and comments, making it tough to connect with followers. Thanks to Spam Destroyer, my Kurilove_Art channel now has instant, zero-spam protection. I can finally focus on creating!</p>
				<cite>Liubov Kurilova – Kurilove_Art</cite>
			</blockquote>
		</div>
	</section>

	<section class="accent secondary">
		<div class="wrapper">
			<h1>WordPress Spam Destroyer</h1>
			<p>Stops automated spam while remaining as unobtrusive as possible to regular commenters. Simply install, and enjoy a spam free website. Perfect if you're sick of spam. Works via JavaScript with cookies and hidden fields to block bots without bothering real users.</p>
			<p>
				<a href="https://wordpress.org/plugins/spam-destroyer/" class="button">Download Spam Destroyer WordPress plugin</a>
			</p>
		</div>
	</section>
@endsection
