<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1E5AA8">

<title>{{ isset($title) && filled((string) $title) ? $title.' · ' : '' }}Jualin Aja ITK</title>
<meta name="description" content="Jual-beli barang bekas khusus mahasiswa ITK. Cari per jenis atau per lapak, COD aman di dalam dan sekitar kampus.">

@stack('meta')

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
