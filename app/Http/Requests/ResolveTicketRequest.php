<?php

namespace App\Http\Requests;

use App\Services\TicketWorkflow;
use Illuminate\Foundation\Http\FormRequest;

class ResolveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($ticket = $this->route('ticket')) {
            app(TicketWorkflow::class)->ensureAllowed($ticket, 'resolve');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
