@extends('layouts.app')

@section('title', 'Edytuj zgłoszenie')

@section('content')
<h1>Edytuj zgłoszenie</h1>
<p class="muted">Wszystkie pola są wymagane.</p>
<form class="panel form-panel" method="post" action="{{ route('tickets.update', $ticket) }}">
@csrf
@method('PATCH')

@include('tickets._form')</form>

@endsection
