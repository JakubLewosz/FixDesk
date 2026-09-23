# FixDesk — notatki do nauki

## Jedno żądanie od formularza do bazy

`GET /tickets/create` trafia przez `routes/web.php` do `TicketController::create()`. Kontroler pobiera kategorie i przekazuje je do `tickets/create.blade.php`. Współdzielony `_form.blade.php` wyświetla cztery pola i token CSRF.

Po wysłaniu `POST /tickets` middleware uruchamia sesję i ochronę CSRF. `TrimStrings` przycina brzegi tekstów, a `ConvertEmptyStringsToNull` normalizuje pustą wartość. `SaveTicketRequest` waliduje długości, typy i istnienie kategorii. Błędy wracają przez sesję; `old()` odtwarza wartości, `@error` pokazuje komunikat.

`TicketController::store()` otrzymuje wyłącznie dozwolone dane z `validated()`, ustawia `TicketStatus::New` i zapisuje model przez Eloquent. Przekierowanie prowadzi do GET szczegółów. To POST/Redirect/GET: odświeżenie szczegółów nie powtarza zapisu.

`composer.json` opisuje zależności, `composer.lock` ich dokładne wersje, a `vendor/` zawiera pobrane biblioteki. `.env` jest lokalny i nie należy do repozytorium. Artisan to wejście do poleceń frameworka, np. migracji i testów.

## Dlaczego rozwiązanie jest osobną operacją

`ResolveTicketRequest` sprawdza, czy opis ma 10–2000 znaków. `TicketWorkflow::ensureAllowed()` sprawdza odświeżony stan zgłoszenia: musi być w trakcie i niezarchiwizowane. Stan jest sprawdzany przed walidacją opisu, więc nawet pusty formularz wysłany dla zamkniętego zgłoszenia daje wyjaśnienie blokady procesu.

`TicketWorkflow::resolve()` ustawia enum, opis i `now()` przed jednym `save()`. Jeden UPDATE zapisuje komplet danych; nie ma momentu, w którym aplikacja osobno zapisuje sam status rozwiązania. Ponowna operacja jest zabroniona i nie nadpisuje daty. Nie używamy transakcji ani blokad: to demonstracja dla jednego operatora, bez gwarancji równoległych zapisów.

`NULL` oznacza brak rozwiązania/daty, a nie pusty opis albo datę zero. Baza przechowuje UTC, widoki przeliczają do Europe/Warsaw. Reguły procesu realizuje serwis, nie observer ani ukryty formularz.

Cztery komunikaty odmowy powstają w `TicketWorkflow::ensureAllowed()`, w `match` zależnym od operacji: `edit`, `start`, `resolve`, `archive`. Serwis rzuca `DomainException`, a `bootstrap/app.php` przekierowuje na szczegóły i przekazuje komunikat przez sesję. Kontrola w Form Request poprzedza walidację; powtórna kontrola w operacji serwisu odświeża stan przed zapisem.

## Pusta strona a brak wyników

`$tickets->count()` i `isEmpty()` opisują tylko bieżącą stronę, natomiast `total()` obejmuje cały wynik zapytania z filtrami. Przy 14 rekordach strona 999 jest pusta, chociaż zbiór wyników nie jest pusty. `TicketController::index()` porównuje `currentPage()` z `lastPage()` istniejącego paginatora i przekierowuje na ostatnią stronę. Dla zera wyników `lastPage()` wynosi 1, więc przekierowanie nie zapętla się. `array_replace()` zastępuje stare `page`, zachowując wyłącznie zwalidowane parametry. Widok `tickets/index.blade.php` wybiera pusty stan, gdy `total()` wynosi 0.

## Dziesięć pytań rekrutacyjnych

1. **Migracja, seeder i factory — czym się różnią?** Migracja tworzy strukturę, seeder wczytuje jawnie demonstracyjne dane, factory tworzy warianty danych testowych. Zobacz `database/`.
2. **Co chroni klucz obcy?** `category_id` musi wskazywać istniejącą kategorię; `restrictOnDelete` nie pozwala usunąć kategorii z rekordami. Nie usuwa zgłoszeń kaskadowo.
3. **Co to relacja i eager loading?** `Ticket::category()` to belongsTo, odwrotna to hasMany; `with('category')` pobiera kategorie zbiorczo i unika N+1 na liście.
4. **Czym różnią się GET, POST i PATCH?** Odczyt, polecenie/zapis, częściowa aktualizacja. HTML obsługuje POST, Laravel rozpoznaje ukryte `_method=PATCH`.
5. **Walidacja a reguła biznesowa?** Długość opisu to walidacja pola; zakaz rozwiązania nowego rekordu to reguła procesu. Obie są kontrolowane na serwerze.
6. **Po co enum i cast?** `TicketStatus` ogranicza wartości w kodzie; Eloquent zamienia tekst z bazy na enum. Daty są obiektami Carbon dzięki castom.
7. **XSS a CSRF?** XSS: niebezpieczny tekst wyświetlany jako kod — chroni escapowanie Blade. CSRF: obce żądanie używające sesji — kontroluje standardowe middleware. To różne problemy.
8. **Dlaczego nie `request()->all()`?** Użytkownik może dosłać `status` i daty mimo braku pól. `validated()` i `$fillable` zawężają zapis do dozwolonych danych.
9. **Jak filtry wpływają na SQL?** Parametry GET po walidacji dodają warunki WHERE połączone AND; archiwum używa IS NOT NULL. Paginacja dodaje limit/offset, sortowanie jest ustalone.
10. **Jak udowodnić, że odmowa nie zmieniła danych?** Test wysyła prawdziwe żądanie, sprawdza przekierowanie i sesję, a następnie porównuje świeży rekord z bazą przed operacją. `RefreshDatabase` izoluje testy na osobnej bazie. Najpierw czytaj pierwszy błąd i jego asercję, nie sam kolor wyniku.

## Ćwiczenie — samodzielnie, poza MVP

Zwiększ limit tytułu ze 120 do 160 znaków:

1. Utwórz **nową** migrację zmieniającą istniejącą kolumnę `tickets.title` na string(160); nie edytuj migracji już wykonanej. Rozważ, co stanie się z długimi danymi podczas cofania zmiany.
2. Zmień `max` w `SaveTicketRequest` i limit/podpowiedź w `_form.blade.php`.
3. Dostosuj testy: 160 polskich znaków zapisuje się poprawnie, 161 daje błąd i pozostawia dane bez zmian. Sprawdź tworzenie i edycję.
4. Uruchom migrację na lokalnej bazie oraz testy na `fixdesk_test`. Sprawdź widok długiego tytułu na telefonie.

Ćwiczenie jest tylko opisane — w aktualnym MVP limit pozostaje 120.

## Własny przegląd Jakuba — do wykonania

Poniższe punkty pozostają niewykonane, dopóki Jakub sam ich nie potwierdzi:

- [ ] Przejrzeć kontroler, Form Request, serwis procesu i testy; wyjaśnić korektę strony oraz kolejność kontroli przed zapisem.
- [ ] Samodzielnie uruchomić testy na `fixdesk_test` i scenariusz ręczny z README.
- [ ] Wykonać opisane ćwiczenie w osobnej zmianie i przygotować własne wyjaśnienie implementacji oraz zakresu użycia AI.
