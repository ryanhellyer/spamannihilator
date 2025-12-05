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

       <form method="POST" action="{{ route('admin.update', ['hash' => $urlMapping->admin_hash]) }}">
        @csrf
        @method('PUT')

        <p>
          <label for="from">Your spam-protected link:</label>
          <div class="from-wrapper">
            @php
              $fullUrl = route('check', ['slug' => $urlMapping->slug]);
              $checkIndex = strpos($fullUrl, '/check/');
              $baseUrl = $checkIndex !== false ? substr($fullUrl, 0, $checkIndex + strlen('/check/')) : '';
            @endphp
            <span id="from-prefix">{{ $baseUrl }}</span>
            <input type="text" name="from" id="from" value="{{ $urlMapping->slug }}" placeholder="e.g., my-group" required style="flex: 1;">
            <button type="button" class="copy-button" onclick="copyField('from', '{{ $baseUrl }}')" title="Copy">Copy</button>
          </div>
          @error('from')
            <span class="inline-error">{{ $message }}</span>
          @enderror
        </p>

        <p>
          <label for="to">To (destination URL)</label>
          <input type="url" name="to" id="to" value="{{ old('to', $urlMapping->url) }}" placeholder="https://discord.gg/example" required>
          @error('to')
            <span class="inline-error">{{ $message }}</span>
          @enderror
        </p>
        
        <label>Admin URL - Save this private URL to edit your link later!</label>
        <p>
            <input type="text" id="admin-url" value="{{ $adminUrl }}" readonly style="flex: 1;">
            <button type="button" class="copy-button" onclick="copyField('admin-url')" title="Copy">Copy</button>
        </p>
        
        <p>
          <label for="email">Email (optional - allows us to contact you if anything changes with your URL)</label>
          <input type="email" name="email" id="email" value="{{ old('email', $urlMapping->email) }}" placeholder="your@email.com">
          @error('email')
            <span class="inline-error">{{ $message }}</span>
          @enderror
        </p>

        <p>
          <input class="button" type="submit" value="Update link">
        </p>
      </form>
    </div>
  </section>

  <script>
    function copyField(fieldId, baseUrl = '') {
      const input = document.getElementById(fieldId);
      let textToCopy = input.value;
      
      // For the "from" field, combine base URL with slug
      if (fieldId === 'from' && baseUrl) {
        textToCopy = baseUrl + textToCopy;
      }
      
      // Use modern Clipboard API if available
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy).then(() => {
          showCopyFeedback(input);
        }).catch(() => {
          fallbackCopy(textToCopy, input);
        });
      } else {
        fallbackCopy(textToCopy, input);
      }
    }
    
    function fallbackCopy(text, input) {
      // Fallback for older browsers
      const tempInput = document.createElement('input');
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      tempInput.setSelectionRange(0, 99999);
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      showCopyFeedback(input);
    }
    
    function showCopyFeedback(input) {
      const originalValue = input.value;
      input.value = 'Copied!';
      input.style.color = '#28a745';
      setTimeout(() => {
        input.value = originalValue;
        input.style.color = '';
      }, 1000);
    }
  </script>
@endsection
