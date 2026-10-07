<!doctype html>
<html lang="{{ $locale ?? app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-sunrice::seo />
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>

<body class="bg-white font-sans leading-normal text-zinc-800 antialiased">
    {{ $slot }}
</body>

</html>
