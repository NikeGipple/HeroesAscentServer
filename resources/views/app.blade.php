<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ env('APP_NAME', 'Heroes Ascent') }}</title>

    @viteReactRefresh
    @vite(['resources/js/main.tsx'])

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">

    
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
