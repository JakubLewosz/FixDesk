# Raport weryfikacji — 23.09.2026

Weryfikację wykonał Codex. Projekt nie został opublikowany, wdrożony ani wysłany do zdalnego Git. Repozytorium początkowo było puste, bez commitów i instrukcji AGENTS.md. Szkielet pobrano z oficjalnej gałęzi Laravel 13; usunięto jego frontend Vite, tabele użytkowników/kolejek/cache oraz instrukcje instalacji dodatkowych narzędzi AI, niepotrzebne w zakresie tego projektu.

## Wykonane sprawdzenia

| Sprawdzenie | Wynik |
| --- | --- |
| PHP / Composer / MySQL | 8.4.25 / 2.10.3 / 8.4.11 |
| Framework / PHPUnit | Laravel 13.33.0 / PHPUnit 12.5.35, przypięte w composer.lock |
| Migracje i jawny seeder | 4 kategorie, 16 zgodnych zgłoszeń; 14 bieżących, 2 archiwalne |
| `php artisan test --compact` | **55 zaliczonych, 410 asercji**, MySQL fixdesk_test |
| Ochrona bazy testowej | Celowo wskazano fixdesk: test przerwany przed migracjami; nadal 16 rekordów demonstracyjnych |
| `composer validate --strict` | Poprawny manifest i lock |
| `composer check-platform-reqs` | Wymagania pakietów spełnione; pdo_mysql sprawdzone rzeczywistym połączeniem |
| PHP lint | 31 plików PHP bez błędów składni |
| `vendor/bin/pint --test` | Poprawne formatowanie PHP |
| `php artisan view:cache` | Wszystkie widoki Blade kompilują się |
| CSRF przez HTTP poza PHPUnit | Brak tokena: 419; błędny: 419; cross-site bez tokena: 419; poprawny token: przejście do walidacji (302); same-origin bez tokena: przejście do walidacji (302), zgodne z kodem Laravel 13 |
| Przeglądarka, czysta instalacja | Dodanie → edycja → rozpoczęcie → błąd rozwiązania z samych spacji → rozwiązanie → archiwizacja → szczegóły archiwalne |
| Widok responsywny | 1280 px i 390 px; przy 390 px lista, formularz i szczegóły mają scrollWidth = clientWidth = 390. Tabela ma własny obszar przewijania |
| Zrzuty | Autentyczna lista i rozwiązane zgłoszenie w docs/screenshots |
| Testy w czystej kopii | Po konfiguracji .env.testing i klucza ponownie 55 zaliczonych / 410 asercji |
| Czysta kopia | Bez vendor, .env, cache i sesji; composer install z lock, nowy klucz, nowe migracje i seeder, start na 127.0.0.1:8001 |

Pierwszy przebieg testów: 35 zaliczonych, 16 błędów asercji. Poprawiono porównywanie świeżego obrazu rekordu (kolejność kluczy tablicy nie była zmianą danych) oraz asercję zachowania pustego wejścia normalizowanego do NULL. Po poprawce: 51/348. Następnie dodano sprawdzenia seedera i integralności kluczy obcych: 54/404. Przegląd wykrył ponadto błąd renderowania tablicy dosłanej zamiast tekstu. Test odtworzył HTTP 500; po zabezpieczeniu odtwarzania wartości i sprawdzeniu pełnego łańcucha przekierowań końcowy wynik to 55/410.

Pierwsze pobranie zależności i dostęp do bazy były blokowane przez sandbox. Homebrew, pobranie zależności, proces MySQL, lokalne HTTP i testy wykonano przez dozwoloną eskalację narzędzia. Nie zastąpiono MySQL przez SQLite.

## Lokalne środowisko tej sesji

Homebrew zainstalował PHP 8.4, Composer i MySQL 8.4. Composer dodatkowo zależy od domyślnego PHP Homebrew (8.5); projekt uruchamiano jawnie przez PHP 8.4. Nie zmieniano globalnego wyboru PHP.

Dane projektu są na **odizolowanym, tymczasowym serwerze**:

- katalog danych `/tmp/fixdesk-mysql`;
- adres `127.0.0.1:3307`, socket `/tmp/fixdesk-mysql.sock`;
- bazy `fixdesk`, `fixdesk_test`, oraz dodatkowa `fixdesk_clean` do sprawdzenia instalacji;
- lokalne konto `fixdesk`, bez hasła na tej odizolowanej instancji; nie jest to konfiguracja do udostępniania;
- `.env` i `.env.testing` wskazują port 3307; szablony dla normalnej instalacji wskazują 3306;
- kopia kontrolna: `/tmp/fixdesk-clean-install`; jej dodatkowy rekord z testu przeglądarkowego nie zmienił 16 rekordów głównej bazy.

Katalog `/tmp` może zostać usunięty przez system. Do trwałego korzystania skonfiguruj własny serwer według README. Nie przeprowadzaj ponownej inicjalizacji istniejącego katalogu danych.

Jeśli tymczasowy MySQL już nie działa, ale katalog danych nadal istnieje, uruchom go w osobnym terminalu:

```sh
/opt/homebrew/opt/mysql@8.4/bin/mysqld --no-defaults \
  --datadir=/tmp/fixdesk-mysql --bind-address=127.0.0.1 --port=3307 \
  --socket=/tmp/fixdesk-mysql.sock --mysqlx=OFF \
  --pid-file=/tmp/fixdesk-mysql.pid --log-error=/tmp/fixdesk-mysql.log
```

W katalogu projektu, do aplikacji lub testów:

```sh
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/opt/mysql@8.4/bin:$PATH"
php artisan serve --host=127.0.0.1 --port=8000
# W drugim terminalu, z tym samym PATH:
php artisan test
```

Nie uruchamiaj drugiego serwera na zajętym porcie. Proces aplikacji można zakończyć Ctrl+C.

## Granice sprawdzenia

Nie sprawdzano innych systemów operacyjnych ani fizycznego telefonu — wąski widok był emulowany w przeglądarce. Nie testowano równoczesnej pracy wielu operatorów, bo jest poza zakresem. Nie wykonywano publicznego wdrożenia ani zewnętrznego audytu. Nie klonowano z serwera Git, ponieważ repozytorium nie zostało opublikowane; użyto czystej kopii plików przeznaczonych do wersjonowania i świeżej bazy `fixdesk_clean`.

Jakub nie potwierdził jeszcze własnego przeglądu ani wykonania ćwiczenia — to kolejny krok przygotowania do rozmowy.
