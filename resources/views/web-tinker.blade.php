<!DOCTYPE html>
<html lang="en" class="theme-{{ config('custom-custom-web-tinker.theme', config('custom-web-tinker.theme')) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Web Tinker (Custom)</title>

    <!-- Style sheets-->
    <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Mono:400,400i,600" rel="stylesheet">
    <link href='{{ asset(mix('app.css', 'vendor/custom-custom-web-tinker')) }}' rel='stylesheet' type='text/css'>
</head>
<body>

<div id="custom-web-tinker" v-cloak>
    <tinker path="{{ $path }}"></tinker>
</div>

<script src="{{ asset(mix('app.js', 'vendor/custom-custom-web-tinker')) }}"></script>
</body>
</html>
