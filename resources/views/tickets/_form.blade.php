@if($categories->isEmpty())<div class="notice error">Brak kategorii. Wczytaj dane początkowe poleceniem php artisan db:seed.</div>
@endif

@foreach(['title' => ['Tytuł', 5, 120], 'location' => ['Lokalizacja', 2, 100]] as $field => [$label, $min, $max])
<div class="field">
<label for="{{ $field }}">{{ $label }}</label>
<input id="{{ $field }}" name="{{ $field }}" value="{{ is_string(old($field, $ticket->$field)) ? old($field, $ticket->$field) : '' }}" required minlength="{{ $min }}" maxlength="{{ $max }}" aria-describedby="{{ $field }}-help {{ $field }}-error">
<small id="{{ $field }}-help">{{ $min }}–{{ $max }} znaków.</small>
@error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>
@enderror</div>

@endforeach
<div class="field">
<label for="category_id">Kategoria</label>
<select id="category_id" name="category_id" required>
<option value="">Wybierz kategorię</option>
@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $ticket->category_id) == $category->id)>{{ $category->name }}</option>
@endforeach</select>
<small>Wybierz jedną kategorię sprzętu.</small>
@error('category_id')<p class="field-error">{{ $message }}</p>
@enderror</div>
<div class="field">
<label for="description">Opis usterki</label>
<textarea id="description" name="description" rows="7" required minlength="10" maxlength="5000">{{ is_string(old('description', $ticket->description)) ? old('description', $ticket->description) : '' }}</textarea>
<small>10–5000 znaków. Opisz objawy i okoliczności wystąpienia usterki.</small>
@error('description')<p class="field-error">{{ $message }}</p>
@enderror</div>
<div class="actions">
<button @disabled($categories->isEmpty()) type="submit">Zapisz zgłoszenie</button>
<a href="{{ $ticket->exists ? route('tickets.show', $ticket) : route('tickets.index') }}">Anuluj</a>
</div>
