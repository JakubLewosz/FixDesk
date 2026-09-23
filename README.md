# FixDesk

Niewielki, lokalny rejestr usterek sprzętu dla jednego operatora pracowni komputerowej. Projekt demonstracyjny do nauki PHP/Laravela i rozmowy rekrutacyjnej. Temat i zakres są decyzją autora, nie wymaganiem Fingoweb.

Proces: **Nowe → W trakcie → Rozwiązane → Archiwizacja**. Rozwiązanie wymaga opisu; archiwizacja zachowuje rekord i umożliwia jego odczyt.

## Zakres

Dodawanie, odczyt, edycja otwartych zgłoszeń, obsługa procesu, dwie listy (bieżące i archiwum), filtry statusu i kategorii połączone AND, paginacja po 10 rekordów, walidacja i polskie komunikaty. Cztery kategorie oraz 16 fikcyjnych zgłoszeń: 6 nowych, 5 w trakcie, 3 rozwiązane bieżące, 2 archiwalne.

**Aplikacja lokalna, demonstracyjna, dla jednego operatora. Nie ma uwierzytelniania ani kontroli dostępu między użytkownikami. Nie udostępniać publicznie w tej postaci.** Nie ma usuwania, cofania statusów, przywracania, historii zmian, zarządzania kategoriami ani API. Nie deklaruje bezpiecznej równoczesnej pracy wielu operatorów.

## Wersje i wymagania

Sprawdzone: PHP **8.4.25**, Laravel **13.33.0**, MySQL Community **8.4.11**, Composer **2.10.3**, PHPUnit **12.5.35**. `composer.lock` przypina zależności. Projekt wymaga PHP 8.4.x; nie uruchamiaj go domyślnym PHP 8.5 z Homebrew.

Rozszerzenia PHP: ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer, xml, xmlwriter; iconv i zip są zalecane. `composer check-platform-reqs` sprawdza wymagania zainstalowanych pakietów. Wymagane zapisywalne `storage/` i `bootstrap/cache/`.

Interfejs: Blade + własny `public/css/app.css`, systemowe fonty. **Bez Node.js, npm, Vite, CDN, workera i zewnętrznych usług podczas używania aplikacji.** Sieć jest potrzebna do pierwszego pobrania zależności.

Zgodność stosu: [Laravel 13 — wymagania](https://laravel.com/framework/docs/releases), [MySQL 8.4 LTS](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html).

## Pierwsze uruchomienie

1. Zainstaluj PHP 8.4, Composer i MySQL 8.4. Na macOS z Homebrew:

   ```sh
   brew install php@8.4 composer mysql@8.4
   export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/opt/mysql@8.4/bin:$PATH"
   brew services start mysql@8.4
   php -v
   composer --version
   ```

   Na Macu Intel ścieżki Homebrew zaczynają się zwykle od `/usr/local`. Pozostałe systemy: użyj odpowiednich instalatorów i upewnij się, że `php -v` wskazuje 8.4.

2. Sklonuj repozytorium:

   ```sh
   git clone https://github.com/JakubLewosz/FixDesk.git FixDesk
   cd FixDesk
   composer install
   composer check-platform-reqs
   cp .env.example .env
   ```

3. W kliencie MySQL jako administrator utwórz nowe bazy i lokalne konto. Nie używaj istniejącej bazy zawierającej inne dane. Zastąp przykładowe hasło własnym lokalnym hasłem i nie dodawaj go do Git:

   ```sql
   CREATE DATABASE fixdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE fixdesk_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'fixdesk'@'127.0.0.1' IDENTIFIED BY 'WPISZ_WLASNE_HASLO';
   GRANT ALL PRIVILEGES ON fixdesk.* TO 'fixdesk'@'127.0.0.1';
   GRANT ALL PRIVILEGES ON fixdesk_test.* TO 'fixdesk'@'127.0.0.1';
   ```

4. W `.env` ustaw `DB_HOST=127.0.0.1`, port swojego serwera (zwykle `3306`), `DB_DATABASE=fixdesk`, użytkownika i hasło. Pozostaw `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `APP_DEBUG=false`. Plik przykładowy nie zawiera hasła ani klucza.

5. Wygeneruj klucz i sprawdź nazwę bazy **przed** migracją:

   ```sh
   php artisan key:generate
   php artisan config:clear
   php artisan db:show
   # Sprawdź, że połączenie to mysql, baza fixdesk.
   php artisan migrate
   php artisan db:seed
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Otwórz [FixDesk lokalnie](http://127.0.0.1:8000). Seeder jest jawny; nie uruchamia się przy wejściu na stronę. Powtórne `db:seed` uzupełnia brakujące kategorie, ale jeśli istnieją zgłoszenia, nie dodaje ponownie demonstracyjnych rekordów.

## Ponowny start

Uruchom MySQL, wejdź do katalogu projektu i wykonaj:

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Nie generuj ponownie klucza, nie uruchamiaj resetu bazy ani seedera przy każdym starcie. Po zmianie `.env` wykonaj `php artisan config:clear`.

Opcjonalny **reset wyłącznie własnych danych demonstracyjnych**: `php artisan migrate:fresh --seed`. To polecenie **usuwa wszystkie tabele i dane aktywnej bazy**, a następnie odtwarza schemat. Nie jest normalnym poleceniem startowym. Przed użyciem sprawdź bazę przez `php artisan db:show` i wykonaj potrzebną kopię zapasową.

## Testy — osobna baza MySQL

```sh
cp .env.testing.example .env.testing
# Wpisz port, użytkownika i hasło; DB_DATABASE musi być fixdesk_test.
php artisan key:generate --env=testing
php artisan config:clear
php artisan db:show --env=testing
# Sprawdź: mysql / fixdesk_test, nigdy fixdesk.
php artisan test
```

Testy używają `RefreshDatabase` i faktycznych zapisów MySQL. **Baza fixdesk_test musi być przeznaczona wyłącznie na testy; jej dane mogą zostać usunięte.** `tests/TestCase.php` przed uruchomieniem traitów sprawdza środowisko, brak cache konfiguracji, sterownik, skonfigurowaną nazwę i wynik `SELECT DATABASE()`. Przy innej bazie przerywa przygotowanie przed `migrate:fresh`. Nie ma SQLite ani haseł w `phpunit.xml`.

Dodatkowe sprawdzenia:

```sh
composer validate --strict
composer check-platform-reqs
vendor/bin/pint --test
php artisan view:cache
php artisan view:clear
```

Wyniki rzeczywistych sprawdzeń oraz szczegóły lokalnego środowiska: [docs/verification.md](docs/verification.md).

## Jak działa kod

- `routes/web.php` — nazwane trasy, numeryczny identyfikator, route model binding i rzeczywiste 404.
- `app/Http/Controllers/TicketController.php` — zapytanie listy, eager loading kategorii, widoki i przekierowania.
- `app/Http/Requests/SaveTicketRequest.php`, `ResolveTicketRequest.php` — walidacja. Domyślne middleware Laravela przycina białe znaki i zamienia puste teksty na NULL przed walidacją.
- `app/Services/TicketWorkflow.php` — dopuszczalność działań i zapisy procesu. Odświeża stan przed operacją; nie stosuje blokad i nie obiecuje odporności na równoczesne zapisy.
- `app/Enums/TicketStatus.php` — jeden string-backed enum z polskimi etykietami; w MySQL zwykły string.
- `app/Models/` — relacje i casty; `database/migrations/` — dwie tabele domenowe oraz standardowa tabela migracji.
- `resources/views/` — Blade, współdzielony formularz, błędy 404/419/500 i własna paginacja.
- `tests/Feature/TicketTest.php` — zachowania HTTP, sesji i zapisanych danych.

Czas zapisujemy w **UTC**, a wyświetlamy w **Europe/Warsaw**, z uwzględnieniem czasu letniego. `archived_at` jest niezależne od statusu: rozwiązane zgłoszenie pozostaje na bieżącej liście aż do archiwizacji. Stan rozwiązania zapisuje jeden SQL UPDATE. Niedozwolony proces zgłasza `DomainException`, obsługiwany w `bootstrap/app.php`; błędy bazy nie są przechwytywane jako sukces.

Parametry listy to `view`, `status`, `category_id`, `page`. Puste filtry nie ograniczają wyników; niepoprawne parametry czyszczą wszystkie filtry i wracają do bieżącej listy. Zmiana widoku zeruje filtry, formularz filtrów pomija stronę, a paginacja zachowuje zastosowane filtry. Sortowanie: najnowsze `created_at`, następnie najwyższe `id`.

Poprawny numer strony przekraczający zakres wyników przekierowuje na ostatnią dostępną stronę, z zachowaniem zwalidowanego widoku i filtrów. Przy braku wyników i `page > 1` następuje jedno przekierowanie na stronę 1, która zwraca HTTP 200 i pokazuje pustą listę bieżącą, puste archiwum lub brak wyników filtrowania. Przykład: 14 bieżących zgłoszeń i `/tickets?page=999` → strona 2 z 4 zgłoszeniami.

## Scenariusz ręczny

1. Dodaj zgłoszenie, używając polskich znaków i wielowierszowego opisu.
2. Edytuj tytuł i sprawdź, że status nadal jest Nowe.
3. Rozpocznij obsługę — status W trakcie.
4. Spróbuj zapisać puste rozwiązanie. Przeglądarka blokuje pusty formularz; same spacje przechodzą jej kontrolę długości i pozwalają zobaczyć polski błąd serwera.
5. Wpisz opis naprawy (10–2000 znaków) i zapisz. Sprawdź opis i datę rozwiązania.
6. Spróbuj wejść na `/tickets/NUMER/edit` — powinno przekierować z wyjaśnieniem.
7. Archiwizuj; znajdź zgłoszenie w Archiwum i odczytaj szczegóły.
8. Sprawdź dwa filtry razem, drugą stronę listy oraz `page=999` z wynikami i bez dopasowań. Sprawdź również archiwum i nieistniejący numer zgłoszenia.

## Bezpieczeństwo i granice

Formularze mają `@csrf`, edycja także `@method('PATCH')`; brak mutacji przez GET. Blade escapuje dane, zachowując nowe linie przez CSS. HTML jest zwykłym tekstem. Eloquent wiąże parametry, a zapis formularza używa tylko `validated()` i czterech dozwolonych pól. Dosłane statusy i daty są ignorowane. Klucz obcy blokuje usunięcie używanej kategorii i przypisanie nieistniejącej. `.gitignore` wyłącza środowisko, logi, zależności i zrzuty SQL.

Laravel 13 używa middleware `PreventRequestForgery`: akceptuje wiarygodne `Sec-Fetch-Site: same-origin` albo pasujący token sesji. Zwykłe testy Feature pomijają tę kontrolę; podczas pierwotnej weryfikacji wykonano oddzielny sprawdzian przez rzeczywiste HTTP, opisany w [raporcie](docs/verification.md). Błąd bez tokena/z błędnym tokenem i bez prawidłowego pochodzenia daje 419. Nie wyłączono middleware ani nie dodano wyjątków CSRF.

Projekt nie przeszedł zewnętrznego audytu i nie jest wdrożeniem produkcyjnym.

## Zrzuty ekranu

Autentyczne zrzuty lokalnie działającej aplikacji:

![Lista zgłoszeń](docs/screenshots/lista.png)
![Rozwiązane zgłoszenie](docs/screenshots/rozwiazane.png)

[Notatka do nauki i ćwiczenie](docs/learning-notes.md).

## Wykorzystanie AI

Codex został wykorzystany do wygenerowania implementacji, testów i dokumentacji oraz wprowadzenia poprawek. Zakres wykonanych sprawdzeń i ich ograniczenia opisano w [docs/verification.md](docs/verification.md). Weryfikacja narzędziowa nie stanowi potwierdzenia osobistego przeglądu kodu przez autora.
