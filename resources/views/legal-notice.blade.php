@extends('layouts.app')

@section('body-class', 'legal-notice')
@section('page-title', 'Legal Notice')
@section('page-tagline', 'Unimportant mumbo jumbo for legal purposes')
@section('title', 'Legal Notice - Spam Annihilator')
@section('og:title', 'Legal Notice - Spam Annihilator')
@section('canonical', url('/legal-notice/'))

@section('header-button')
<a href="/home-new/" class="button">Back to Home</a>
@endsection

@section('header-form')
@endsection

@section('content')
  <section class="primary">
    <div class="wrapper">
      <h2>Website Operator</h2>
      
      <h3>Provider/Operator:</h3>
      <p>
        Ryan Hellyer<br>
        Friedrichstraße 114a<br>
        Berlin 10117<br>
        Germany
      </p>

      <h3>Contact Details:</h3>
      <p>
        Telephone: <a href="tel:+491745390977">+49 174 5390977</a><br>
        E-Mail: <a href="mailto:ryan@hellyer.kiwi">ryan@hellyer.kiwi</a>
      </p>
    </div>
  </section>
@endsection
