@extends('layouts.app')

@section('title', 'Nowe zgłoszenie')

@section('content')
<h1>Nowe zgłoszenie</h1>
<p class="muted">Wszystkie pola są wymagane.</p>
<form class="panel form-panel" method="post" action="{{ route('tickets.store') }}">
@csrf

@include('tickets._form')</form>

@endsection
