@extends('layouts.app')

@section('body-class', 'admin')
@section('page-title', 'Admin page')
@section('page-tagline', 'Manage your spam-protected link')

@section('header-button')
  <a href="{{ url('/') }}" class="button">Back to Home Page</a>
@endsection

@section('header-form')
@endsection

@section('content')
  <section class="primary">
    <div class="wrapper">
      @if(session('success'))
        <div class="success">
          {{ session('success') }}
        </div>
      @endif

      <p>
        <label>Use this URL to redirect users:</label>
        <input type="text" id="redirect-url" value="{{ route('check', ['slug' => $urlMapping->slug]) }}" readonly style="width: 100%; max-width: 600px; padding: 0.5rem; font-family: monospace;">
      </p>
        <label>Admin URL - Save this URL to edit your redirect later. Keep it private!</label>
        <p>
          <input type="text" id="admin-url" value="{{ $adminUrl }}" readonly style="width: 100%; max-width: 600px; padding: 0.5rem; font-family: monospace;">
        </p>

       <h2>Edit Link</h2>
       <form method="POST" action="{{ route('admin.update', ['hash' => $urlMapping->admin_hash]) }}">
        @csrf
        @method('PUT')
        
        <p>
          <label for="from">From (slug)</label>
          <input type="text" name="from" id="from" value="{{ old('from', $urlMapping->slug) }}" placeholder="e.g., my-group" required>
          @error('from')
            <span>{{ $message }}</span>
          @enderror
        </p>
        
        <p>
          <label for="to">To (destination URL)</label>
          <input type="url" name="to" id="to" value="{{ old('to', $urlMapping->url) }}" placeholder="https://discord.gg/example" required>
          @error('to')
            <span>{{ $message }}</span>
          @enderror
        </p>
        
        <p>
          <label for="email">Email (optional)</label>
          <input type="email" name="email" id="email" value="{{ old('email', $urlMapping->email) }}" placeholder="your@email.com">
          @error('email')
            <span>{{ $message }}</span>
          @enderror
        </p>
        
        <p>
          <input class="button" type="submit" value="Update link">
        </p>
      </form>
    </div>
  </section>

  <script>
    function copyAdminUrl() {
      const input = document.getElementById('admin-url');
      input.select();
      input.setSelectionRange(0, 99999); // For mobile devices
      document.execCommand('copy');
      
      const button = document.getElementById('copy-button');
      const message = document.getElementById('copy-message');
      message.style.display = 'inline';
      setTimeout(() => {
        message.style.display = 'none';
      }, 2000);
    }

    function copyRedirectUrl() {
      const input = document.getElementById('redirect-url');
      input.select();
      input.setSelectionRange(0, 99999); // For mobile devices
      document.execCommand('copy');
      
      const button = document.getElementById('copy-redirect-button');
      const message = document.getElementById('copy-redirect-message');
      message.style.display = 'inline';
      setTimeout(() => {
        message.style.display = 'none';
      }, 2000);
    }
  </script>
@endsection
