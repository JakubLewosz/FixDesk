@extends('layouts.app')

@section('title', 'Formularz wygasł')

@section('content')<section class="panel empty">
<p class="eyebrow">BŁĄD 419</p>
<h1>Formularz wygasł</h1>
<p>Odśwież stronę i ponownie wyślij formularz.</p>
<a href="{{ route('tickets.index') }}">Wróć do listy</a>
</section>
@endsection
