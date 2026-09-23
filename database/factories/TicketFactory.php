<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'title' => 'Usterka sprzętu demonstracyjnego',
            'location' => 'Pracownia A', 'description' => 'Urządzenie nie uruchamia się po naciśnięciu przycisku.',
            'status' => TicketStatus::New, 'resolution' => null, 'resolved_at' => null, 'archived_at' => null];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TicketStatus::InProgress]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['status' => TicketStatus::Resolved,
            'resolution' => 'Wymieniono uszkodzony przewód i sprawdzono działanie.', 'resolved_at' => now()]);
    }

    public function archived(): static
    {
        return $this->resolved()->state(fn () => ['archived_at' => now()]);
    }
}
