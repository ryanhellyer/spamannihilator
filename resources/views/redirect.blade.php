<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Redirecting...</title>
	<meta http-equiv="refresh" content="0;url={{ $redirectUrl }}">
</head>
<body>
	<script>
		window.location.href = {!! json_encode($redirectUrl) !!};
	</script>
	<p>If you are not redirected automatically, <a href="{{ $redirectUrl }}">click here</a>.</p>
</body>
</html>