@extends('layouts.app')

@section('title', 'Zgłoszenie #'.$ticket->id)

@section('content')
    <a href="{{ route('tickets.index', $ticket->archived_at ? ['view' => 'archived'] : []) }}">← Wróć do listy</a>
    <div class="heading">
        <div>
            <p class="eyebrow">ZGŁOSZENIE #{{ $ticket->id }}</p>
            <h1>{{ $ticket->title }}</h1>
        </div>
        <span class="badge {{ $ticket->status->value }}">{{ $ticket->status->label() }}</span>
    </div>

    @if($ticket->archived_at)
        <div class="notice">Zgłoszenie archiwalne — tylko do odczytu.</div>
    @endif

    <div class="detail-grid">
        <section class="panel">
            <h2>Opis usterki</h2>
            <p class="multiline">{{ $ticket->description }}</p>
            @if($ticket->resolution)
                <hr>
                <h2>Rozwiązanie</h2>
                <p class="multiline">{{ $ticket->resolution }}</p>
            @endif
        </section>
        <aside class="panel">
            <h2>Informacje</h2>
            <dl>
                <dt>Kategoria</dt>
                <dd>{{ $ticket->category->name }}</dd>
                <dt>Lokalizacja</dt>
                <dd>{{ $ticket->location }}</dd>

                @foreach([
                    'created_at' => 'Utworzono',
                    'updated_at' => 'Zaktualizowano',
                    'resolved_at' => 'Rozwiązano',
                    'archived_at' => 'Zarchiwizowano',
                ] as $field => $label)
                    <dt>{{ $label }}</dt>
                    <dd>{{ $ticket->$field?->timezone('Europe/Warsaw')->format('d.m.Y H:i:s') ?? '—' }}</dd>
                @endforeach
            </dl>
            <small>Czas: Europe/Warsaw</small>
        </aside>
    </div>

    <div class="actions">
        @if($workflow->canEdit($ticket))
            <a class="button secondary" href="{{ route('tickets.edit', $ticket) }}">Edytuj</a>
        @endif

        @if($workflow->canStart($ticket))
            <form method="post" action="{{ route('tickets.start', $ticket) }}">
                @csrf
                <button>Rozpocznij obsługę</button>
            </form>
        @endif

        @if($workflow->canArchive($ticket))
            <form method="post" action="{{ route('tickets.archive', $ticket) }}">
                @csrf
                <button>Archiwizuj</button>
            </form>
        @endif
    </div>

    @if($workflow->canResolve($ticket))
        <form class="panel form-panel" method="post" action="{{ route('tickets.resolve', $ticket) }}">
            @csrf
            <h2>Rozwiąż zgłoszenie</h2>
            <label for="resolution">Opis rozwiązania</label>
            <textarea id="resolution" name="resolution" required minlength="10" maxlength="2000" rows="5">{{ is_string(old('resolution')) ? old('resolution') : '' }}</textarea>
            <small>10–2000 znaków. Napisz, co naprawiono i jak sprawdzono rezultat.</small>
            @error('resolution')
                <p class="field-error">{{ $message }}</p>
            @enderror
            <div class="actions">
                <button>Zapisz rozwiązanie</button>
            </div>
        </form>
    @endif
@endsection
