@extends('layouts.app')

@section('robots', 'noindex')
@section('body-class', 'error')
@section('page-title', '404 - Page Not Found')
@section('page-tagline', 'The page you are looking for could not be found')
@section('header-avatar-src', '/images/404-man.avif')
@section('header-avatar-alt', '404 error')

@section('header-button')
  <a href="{{ url('/') }}" class="button">Back to Home Page</a>
@endsection

@section('header-form')
@endsection

@section('content')
@endsection
