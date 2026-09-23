<?php

namespace App\Http\Requests;

use App\Services\TicketWorkflow;
use Illuminate\Foundation\Http\FormRequest;

class SaveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($ticket = $this->route('ticket')) {
            app(TicketWorkflow::class)->ensureAllowed($ticket, 'edit');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'location' => ['required', 'string', 'min:2', 'max:100'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
