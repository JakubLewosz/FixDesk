<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Rejestr usterek') · FixDesk</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header>
<nav class="container">
<a class="brand" href="{{ route('tickets.index') }}">Fix<span>Desk</span>
</a>
<a href="{{ route('tickets.index') }}">Rejestr usterek</a>
<span class="local">Pracownia · lokalnie</span>
</nav>
</header>
<main class="container">
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>
@endif

@if(session('error'))<div class="notice error" role="alert">{{ session('error') }}</div>
@endif
@yield('content')</main>
<footer class="container">FixDesk · demonstracyjny rejestr dla jednego operatora</footer>
</body>
</html>
