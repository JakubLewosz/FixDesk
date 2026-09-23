# Raport weryfikacji

## Bieżąca sesja — 23.09.2026, poprawki po przeglądzie kodu

Sprawdzenia wykonał Codex na lokalnym stanie opartym na commicie `2fe4eb4`. Zakres: korekta strony poza zakresem, zachowanie filtrów i pustych stanów, cztery komunikaty odmowy operacji, czytelność wskazanych plików oraz dokumentacja. Poniższe wyniki pochodzą z rzeczywistego wykonania w tej sesji.

Użyto istniejących zależności i usług: PHP **8.4.25**, Laravel **13.33.0**, MySQL **8.4.11**, Composer **2.10.3**, PHPUnit **12.5.35**. Domyślny interpreter to PHP 8.5.10; polecenia projektu wykonywano przez PHP 8.4, ustawiając PATH tylko dla polecenia:

```sh
PATH="/opt/homebrew/opt/php@8.4/bin:$PATH" php artisan test
```

Przed testami osobne sprawdzenie po załadowaniu środowiska `testing` potwierdziło: brak cache konfiguracji, sterownik `mysql`, skonfigurowaną bazę `fixdesk_test` i wynik `SELECT DATABASE()` równy `fixdesk_test`. Niezmienione zabezpieczenie w `tests/TestCase.php` powtarza te kontrole przed `RefreshDatabase`. Nie zmieniano prywatnych plików środowiska, konfiguracji serwerów, danych demonstracyjnych ani `composer.lock`.

| Sprawdzenie / polecenie | Wynik bieżącej sesji |
| --- | --- |
| Przed poprawką: `php artisan test --compact --filter=test_page_beyond_last_redirects_to_last_active_page` | **1 niezaliczony test, 1 asercja**: otrzymano HTTP 200 zamiast przekierowania; odtworzono błąd |
| Po poprawce: `php artisan test --compact --filter='test_page_beyond\|test_filters_are_combined\|test_invalid_filters\|test_empty_states'` | **21 zaliczonych, 154 asercje**; ostatnia strona, filtry AND, archiwum, cztery warianty pustych wyników, istniejące strony i błędne parametry |
| `php artisan test --compact --filter='test_workflow\|test_forbidden_transition\|test_closed_tickets\|test_invalid_resolution'` | **14 zaliczonych, 119 asercji**; dokładne komunikaty i niezmienność rekordów, również przy błędnym formularzu i ponowieniu operacji |
| `php artisan test` | **61 zaliczonych, 486 asercji**, MySQL `fixdesk_test` |
| `composer validate --strict` | Manifest i lock poprawne, kod wyjścia 0 |
| `composer check-platform-reqs` | Wymagania zainstalowanych pakietów spełnione; `pdo_mysql` dodatkowo potwierdzono połączeniem testowym |
| `vendor/bin/pint --test` | Poprawne formatowanie PHP, kod wyjścia 0 |
| `php -l` dla pięciu zmienionych plików PHP poza Blade | Bez błędów składni |
| `php artisan view:cache`, następnie `php artisan view:clear` | Widoki skompilowane poprawnie, następnie usunięto cache widoków |
| `git diff --check` i przegląd różnic | Bez błędów białych znaków; zmiany ograniczone do wskazanego zakresu |

Przeglądarka na działającym `127.0.0.1:8000`: lista 14 bieżących rekordów, `page=999` → `page=2` z czterema rekordami; formularz filtrów zeruje stronę; status i kategoria pozostają po korekcie; archiwum zachowuje swój widok; brak dopasowań w archiwum kończy się na stronie 1 z komunikatem „Brak wyników filtrowania”. Wejście na edycję archiwalnego zgłoszenia przekierowuje na szczegóły i pokazuje nowy komunikat odmowy edycji. Sprawdzono także renderowanie formularza i wygląd zmienionych widoków. Nie wykonywano zapisów do danych demonstracyjnych.

**Ograniczenia bieżącej sesji:** brak blokad środowiskowych. Pozostałe odmowy procesu sprawdzono testami Feature, bez ponawiania całego procesu zapisów w przeglądarce. Nie powtarzano niezależnych prób CSRF przez HTTP, czystej instalacji ani testów responsywności na wielu szerokościach. Wynik Feature nie jest niezależnym potwierdzeniem ochrony CSRF. Nie sprawdzano innych systemów ani równoczesnej pracy wielu operatorów. Nie tworzono commitów, nie wysyłano zmian i nie wdrażano aplikacji. Własny przegląd Jakuba pozostaje niewykonaną checklistą w `docs/learning-notes.md`.

## Historyczna weryfikacja pierwotnej implementacji — 23.09.2026

Ta sekcja streszcza wcześniejszy raport zapisany w repozytorium. **55 zaliczonych przypadków i 410 asercji to wynik poprzedniej weryfikacji, a nie bieżącego uruchomienia.**

| Zakres wcześniejszego raportu | Zapisany wynik historyczny |
| --- | --- |
| Migracje i jawny seeder | 4 kategorie, 16 fikcyjnych zgłoszeń: 14 bieżących i 2 archiwalne |
| `php artisan test --compact` | 55 zaliczonych, 410 asercji na `fixdesk_test`; taki sam wynik w czystej kopii |
| Ochrona bazy testowej | Celowe wskazanie `fixdesk` przerwało przygotowanie przed migracjami; dane demonstracyjne pozostały |
| Composer, Pint, PHP lint, Blade | Sprawdzenia poprawne; lint obejmował wtedy 31 plików PHP |
| CSRF przez HTTP poza PHPUnit | Brak/błędny token oraz cross-site bez tokena: 419; poprawny token lub same-origin bez tokena: przejście do walidacji (302), zgodnie z middleware Laravel 13 |
| Przeglądarka w czystej kopii | Dodanie → edycja → rozpoczęcie → błąd walidacji → rozwiązanie → archiwizacja → odczyt |
| Responsywność i zrzuty | Widoki 1280 px i 390 px; autentyczne zrzuty w `docs/screenshots/` |
| Czysta instalacja | Kopia bez zależności i prywatnej konfiguracji, `composer install` z locka, nowy klucz, migracje i seeder na `fixdesk_clean` |

W chwili **pierwotnej** weryfikacji repozytorium nie było opublikowane; dlatego zamiast klonowania ze zdalnego Git użyto czystej kopii plików. To opis ówczesnego stanu, nie deklaracja aktualnej dostępności repozytorium. Wcześniejszy raport nie obejmował fizycznego telefonu, innych systemów, współbieżności, publicznego wdrożenia ani zewnętrznego audytu.

## Tymczasowy MySQL z pierwotnej sesji — nadal używany lokalnie

Istniejąca instancja działa na `127.0.0.1:3307`, z katalogiem danych `/tmp/fixdesk-mysql` i socketem `/tmp/fixdesk-mysql.sock`. Zawiera bazy `fixdesk`, `fixdesk_test` oraz historyczną bazę czystej instalacji `fixdesk_clean`. Dane dostępu są w prywatnej konfiguracji. Szablony projektu dla zwykłej instalacji wskazują port 3306. Historyczna kopia kontrolna znajdowała się w `/tmp/fixdesk-clean-install`.

To opis lokalnego środowiska weryfikacji, nie wymagana konfiguracja użytkownika. `/tmp` może zostać usunięty przez system; do trwałej instalacji użyj instrukcji README. Nie inicjalizuj ponownie istniejącego katalogu danych.

Jeśli ta instancja nie działa, ale jej katalog danych nadal istnieje, można wznowić ją w osobnym terminalu:

```sh
/opt/homebrew/opt/mysql@8.4/bin/mysqld --no-defaults \
  --datadir=/tmp/fixdesk-mysql --bind-address=127.0.0.1 --port=3307 \
  --socket=/tmp/fixdesk-mysql.sock --mysqlx=OFF \
  --pid-file=/tmp/fixdesk-mysql.pid --log-error=/tmp/fixdesk-mysql.log
```

W katalogu projektu, przy istniejącej konfiguracji lokalnej:

```sh
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/opt/mysql@8.4/bin:$PATH"
php artisan serve --host=127.0.0.1 --port=8000
# W drugim terminalu, z tym samym PATH:
php artisan test
```

Nie uruchamiaj drugiego serwera na zajętym porcie. Proces aplikacji można zakończyć Ctrl+C.
