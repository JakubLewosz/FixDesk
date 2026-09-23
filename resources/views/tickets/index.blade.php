@extends('layouts.app')

@section('title', 'Zgłoszenia')

@section('content')
    <div class="heading">
        <div>
            <p class="eyebrow">PRACOWNIA POD KONTROLĄ</p>
            <h1>Zgłoszenia</h1>
            <p class="muted">Od pierwszego zgłoszenia do rozwiązanej usterki.</p>
        </div>
        <a class="button" href="{{ route('tickets.create') }}">+ Nowe zgłoszenie</a>
    </div>

    <nav class="tabs" aria-label="Widok zgłoszeń">
        <a @class(['selected' => $view === 'active']) href="{{ route('tickets.index') }}"
            @if($view === 'active')
                aria-current="page"
            @endif
        >Bieżące</a>
        <a @class(['selected' => $view === 'archived']) href="{{ route('tickets.index', ['view' => 'archived']) }}"
            @if($view === 'archived')
                aria-current="page"
            @endif
        >Archiwum</a>
    </nav>

    <form class="filters panel" method="get" action="{{ route('tickets.index') }}">
        <input type="hidden" name="view" value="{{ $view }}">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Wszystkie statusy</option>
                @foreach(App\Enums\TicketStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category_id">Kategoria</label>
            <select id="category_id" name="category_id">
                <option value="">Wszystkie kategorie</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit">Filtruj</button>
        <a href="{{ route('tickets.index', ['view' => $view]) }}">Wyczyść filtry</a>
    </form>

    <p class="muted">{{ $view === 'active' ? 'Bieżące zgłoszenia' : 'Archiwum zgłoszeń' }} · {{ $tickets->total() }} wyników</p>

    @if($tickets->total() === 0)
        <div class="panel empty">
            <h2>
                @if(($filters['status'] ?? null) || ($filters['category_id'] ?? null))
                    Brak wyników filtrowania
                @elseif($view === 'archived')
                    Archiwum jest puste
                @else
                    Brak zgłoszeń
                @endif
            </h2>
            <p>Zmień filtry lub wróć do pierwszej strony listy.</p>
            <a href="{{ route('tickets.index', ['view' => $view]) }}">Pokaż cały wybrany widok</a>
        </div>
    @else
        <div class="panel table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Numer</th>
                        <th>Tytuł</th>
                        <th>Kategoria</th>
                        <th>Lokalizacja</th>
                        <th>Status</th>
                        <th>Utworzono</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td>#{{ $ticket->id }}</td>
                            <td>
                                <a class="ticket-title" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->title }}</a>
                            </td>
                            <td>{{ $ticket->category->name }}</td>
                            <td>{{ $ticket->location }}</td>
                            <td>
                                <span class="badge {{ $ticket->status->value }}">{{ $ticket->status->label() }}</span>
                            </td>
                            <td>{{ $ticket->created_at->timezone('Europe/Warsaw')->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{ $tickets->links('pagination.simple') }}
@endsection
