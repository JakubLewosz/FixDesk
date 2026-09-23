<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\ResolveTicketRequest;
use App\Http\Requests\SaveTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private TicketWorkflow $workflow) {}

    public function index(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->only('view', 'status', 'category_id', 'page'), [
            'view' => ['nullable', 'string', Rule::in(['active', 'archived'])],
            'status' => ['nullable', 'string', Rule::enum(TicketStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:2147483647'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('tickets.index')->with('error', 'Wyczyszczono błędne filtry. Wyświetlono listę bieżącą.');
        }

        $filters = $validator->validated();
        $view = $filters['view'] ?? 'active';
        $query = Ticket::with('category');

        if ($view === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['category_id'] ?? null) {
            $query->where('category_id', $filters['category_id']);
        }

        $tickets = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($filters);

        if ($tickets->currentPage() > $tickets->lastPage()) {
            return redirect()->route('tickets.index', array_replace($filters, [
                'page' => $tickets->lastPage(),
            ]));
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'view' => $view,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', ['ticket' => new Ticket, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(SaveTicketRequest $request): RedirectResponse
    {
        $ticket = new Ticket($request->validated());
        $ticket->status = TicketStatus::New;
        $ticket->save();

        return redirect()->route('tickets.show', $ticket)->with('success', 'Dodano zgłoszenie.');
    }

    public function show(Ticket $ticket): View
    {
        return view('tickets.show', ['ticket' => $ticket->load('category'), 'workflow' => $this->workflow]);
    }

    public function edit(Ticket $ticket): View
    {
        $this->workflow->ensureAllowed($ticket, 'edit');

        return view('tickets.edit', ['ticket' => $ticket, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(SaveTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->workflow->update($ticket, $request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Zapisano zmiany.');
    }

    public function start(Ticket $ticket): RedirectResponse
    {
        $this->workflow->start($ticket);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Rozpoczęto obsługę.');
    }

    public function resolve(ResolveTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->workflow->resolve($ticket, $request->validated('resolution'));

        return redirect()->route('tickets.show', $ticket)->with('success', 'Rozwiązano zgłoszenie.');
    }

    public function archive(Ticket $ticket): RedirectResponse
    {
        $this->workflow->archive($ticket);

        return redirect()->route('tickets.index')->with('success', 'Zgłoszenie przeniesiono do archiwum.');
    }
}
