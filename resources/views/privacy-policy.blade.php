@extends('layouts.app')

@section('body-class', 'privacy-policy')
@section('page-title', 'Privacy Policy')
@section('page-tagline', 'How we protect and handle your personal data')
@section('title', 'Privacy Policy - Spam Destroyer')
@section('description', 'Learn how Spam Destroyer protects and handles your personal data. Our privacy policy covers data collection, cookies, analytics, and your rights.')
@section('robots', 'noindex')
@section('og:title', 'Privacy Policy - Spam Destroyer')
@section('og:description', 'Learn how Spam Destroyer protects and handles your personal data. Our privacy policy covers data collection, cookies, analytics, and your rights.')
@section('canonical', url('/privacy-policy/'))

@section('header-button')
<a href="/home-new/" class="button">Back to Home</a>
@endsection

@section('header-form')
@endsection

@section('content')
  <section class="primary">
    <div class="wrapper">
      <h2>Who Am I?</h2>
      <p>
        My website address is: <a href="https://spam-destroyer.com/">https://spam-destroyer.com/</a>.
      </p>

      <h2>What "Personal Data" I Collect and Why I Collect It</h2>
      <p>
        Since we are all participating in this exercise of "transparency," here is the riveting rundown of what data is collected.
      </p>

      <h3>Cookies</h3>
      <p>
        Yes, we use cookies. It is apparently impossible to operate a website without them.
      </p>
      <p>
        Should you choose to edit or publish an article (if you happen to be an administrator), an additional cookie is saved. This cookie includes no personal data and merely indicates the post ID of the article you just edited. It expires after 1 day.
      </p>

      <h3>Analytics</h3>
      <p>
        I track some things. It's what everyone does when they have a website.
      </p>

      <h2>Who We Share Your Data With</h2>
      <p>
        Only those we are required to by law.
      </p>

      <h2>How Long We Retain Your Data</h2>
      <p>
        We don't retain any permanent data about visitors aside from standard http server logs and storing the number of link clicks.
      </p>

      <h2>What Rights You Have Over Your Data</h2>
      <p>
        Since this is a fairly standard website that retains almost none of your personal information, the rights you have are mostly theoretical. However, in the spirit of compliance, you can request that I erase any personal data we happen to hold about you. Considering the scope of this site, that means you'll probably receive an empty file, but the request still stands. Important Note: This request does not cover any data we are obligated to keep for administrative, legal, or security purposes. Some data just has to stick around.
      </p>

      <h2>My Contact Information</h2>
      <p>
        If you really feel the need to send me more data, you can find the contact page here: <a href="https://ryan.hellyer.kiwi/contact/">https://ryan.hellyer.kiwi/contact/</a>
      </p>
    </div>
  </section>
@endsection
