@extends('layouts.app')

@section('title', 'Nie znaleziono zgłoszenia')

@section('content')<section class="panel empty">
<p class="eyebrow">BŁĄD 404</p>
<h1>Nie znaleziono zgłoszenia</h1>
<p>Ten adres nie wskazuje na istniejące zgłoszenie.</p>
<a href="{{ route('tickets.index') }}">Wróć do listy</a>
</section>
@endsection
