<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nowe', self::InProgress => 'W trakcie', self::Resolved => 'Rozwiązane',
        };
    }
}
