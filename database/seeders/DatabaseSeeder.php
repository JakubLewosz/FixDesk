<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Komputery', 'Drukarki', 'Sieć', 'Inne'])
            ->map(fn ($name) => Category::firstOrCreate(['name' => $name]));
        // Ponowne jawne wywołanie nie powiela zgłoszeń ani nie resetuje cudzej pracy.
        if (Ticket::exists()) {
            $this->command?->info('Zgłoszenia już istnieją — pominięto dane demonstracyjne.');

            return;
        }
        $titles = ['Komputer nie uruchamia się', 'Drukarka nie pobiera papieru', 'Brak połączenia z siecią',
            'Projektor wyświetla rozmyty obraz', 'Klawiatura gubi polskie znaki', 'Blady wydruk strony testowej',
            'Przerywane połączenie sieciowe', 'Głośny wentylator projektora', 'Monitor gaśnie po kilku minutach',
            'Zacięcie papieru w podajniku', 'Uszkodzona końcówka przewodu sieciowego', 'Mysz nie reaguje na ruch',
            'Komputer przegrzewa się podczas pracy', 'Drukarka pozostaje w trybie offline',
            'Brak sygnału w gnieździe sieciowym', 'Głośniki nie odtwarzają dźwięku'];
        foreach ($titles as $index => $title) {
            $created = now()->subDays(20 - $index);
            $status = $index < 6 ? TicketStatus::New : ($index < 11 ? TicketStatus::InProgress : TicketStatus::Resolved);
            $ticket = new Ticket(['title' => $title, 'category_id' => $categories[$index % 4]->id,
                'location' => 'Pracownia '.(['A', 'B', 'C'][$index % 3]).' — stanowisko demonstracyjne '.($index + 1),
                'description' => "Usterka wystąpiła podczas sprawdzania wyposażenia pracowni. Sprzęt nie działa zgodnie z oczekiwaniem.\n\nPonowne uruchomienie nie usunęło problemu. Sprawdzono podstawowe połączenia i zasilanie. To fikcyjne zgłoszenie służy prezentacji aplikacji."]);
            $ticket->status = $status;
            $ticket->created_at = $created;
            $ticket->updated_at = $created;
            if ($status === TicketStatus::Resolved) {
                $ticket->resolution = 'Sprawdzono połączenia, oczyszczono elementy urządzenia i wykonano poprawny test działania.';
                $ticket->resolved_at = $created->copy()->addHours(2);
                $ticket->updated_at = $ticket->resolved_at;
            }
            if ($index >= 14) {
                $ticket->archived_at = $created->copy()->addHours(3);
                $ticket->updated_at = $ticket->archived_at;
            }
            $ticket->save();
        }
    }
}
