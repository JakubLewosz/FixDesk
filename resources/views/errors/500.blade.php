@extends('layouts.app')

@section('title', 'Nie udało się wykonać operacji')

@section('content')<section class="panel empty">
<p class="eyebrow">BŁĄD 500</p>
<h1>Nie udało się wykonać operacji</h1>
<p>Wystąpił błąd aplikacji. Spróbuj ponownie później.</p>
<a href="{{ route('tickets.index') }}">Wróć do listy</a>
</section>
@endsection
