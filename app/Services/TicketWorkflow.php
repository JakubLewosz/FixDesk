<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use DomainException;

class TicketWorkflow
{
    public function canEdit(Ticket $ticket): bool
    {
        return $ticket->archived_at === null && $ticket->status !== TicketStatus::Resolved;
    }

    public function canStart(Ticket $ticket): bool
    {
        return $ticket->archived_at === null && $ticket->status === TicketStatus::New;
    }

    public function canResolve(Ticket $ticket): bool
    {
        return $ticket->archived_at === null && $ticket->status === TicketStatus::InProgress;
    }

    public function canArchive(Ticket $ticket): bool
    {
        return $ticket->archived_at === null && $ticket->status === TicketStatus::Resolved;
    }

    public function ensureAllowed(Ticket $ticket, string $action): void
    {
        $ticket->refresh();

        $allowed = match ($action) {
            'edit' => $this->canEdit($ticket),
            'start' => $this->canStart($ticket),
            'resolve' => $this->canResolve($ticket),
            'archive' => $this->canArchive($ticket),
        };

        if (! $allowed) {
            $message = match ($action) {
                'edit' => 'Edytować można tylko nowe zgłoszenia i zgłoszenia w trakcie, które nie są zarchiwizowane.',
                'start' => 'Obsługę można rozpocząć tylko dla nowego, niezarchiwizowanego zgłoszenia.',
                'resolve' => 'Rozwiązać można tylko niezarchiwizowane zgłoszenie będące w trakcie obsługi.',
                'archive' => 'Archiwizować można tylko rozwiązane zgłoszenie, które nie znajduje się jeszcze w archiwum.',
            };

            throw new DomainException($message);
        }
    }

    public function update(Ticket $ticket, array $data): void
    {
        $this->ensureAllowed($ticket, 'edit');
        $ticket->update($data);
    }

    public function start(Ticket $ticket): void
    {
        $this->ensureAllowed($ticket, 'start');
        $ticket->status = TicketStatus::InProgress;
        $ticket->save();
    }

    public function resolve(Ticket $ticket, string $resolution): void
    {
        $this->ensureAllowed($ticket, 'resolve');
        $ticket->status = TicketStatus::Resolved;
        $ticket->resolution = $resolution;
        $ticket->resolved_at = now();
        $ticket->save();
    }

    public function archive(Ticket $ticket): void
    {
        $this->ensureAllowed($ticket, 'archive');
        $ticket->archived_at = now();
        $ticket->save();
    }
}
