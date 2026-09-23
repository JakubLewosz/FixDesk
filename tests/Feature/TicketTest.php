<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace(['title' => '  Uszkodzona klawiatura  ', 'location' => ' Pracownia Łódź ',
            'category_id' => Category::factory()->create()->id,
            'description' => "  Nie działają polskie znaki.\nSprawdzono połączenie.  "], $overrides);
    }

    public function test_creation_normalizes_fields_and_ignores_process_fields(): void
    {
        $data = $this->payload(['status' => 'resolved', 'resolution' => 'Fałszywe rozwiązanie',
            'resolved_at' => '2000-01-01', 'archived_at' => '2000-01-01', 'created_at' => '2000-01-01']);
        $this->post(route('tickets.store'), $data)->assertRedirect(route('tickets.show', Ticket::first()))->assertSessionHas('success');
        $ticket = Ticket::sole();
        $this->assertSame('Uszkodzona klawiatura', $ticket->title);
        $this->assertSame('Pracownia Łódź', $ticket->location);
        $this->assertSame("Nie działają polskie znaki.\nSprawdzono połączenie.", $ticket->description);
        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertNull($ticket->resolution);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->archived_at);
        $this->assertNotSame('2000-01-01', $ticket->created_at->toDateString());
    }

    public static function invalidFields(): array
    {
        return [
            'missing title' => ['title', ''], 'spaces title' => ['title', '   '],
            'short title' => ['title', 'abcd'], 'long title' => ['title', str_repeat('ą', 121)],
            'empty location' => ['location', ''], 'short location' => ['location', 'a'],
            'long location' => ['location', str_repeat('ł', 101)], 'spaces location' => ['location', '  '],
            'empty description' => ['description', ''], 'short description' => ['description', 'abc'],
            'long description' => ['description', str_repeat('ó', 5001)], 'spaces description' => ['description', " \n "],
            'missing category' => ['category_id', ''], 'unknown category' => ['category_id', 999999],
            'array title' => ['title', ['wrong']], 'array category' => ['category_id', [1]],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_creation_has_errors_and_preserves_input(string $field, mixed $value): void
    {
        $data = $this->payload([$field => $value]);
        $this->from(route('tickets.create'))->post(route('tickets.store'), $data)
            ->assertRedirect(route('tickets.create'))->assertSessionHasErrors($field)->assertSessionHas('_old_input', fn ($input) => array_key_exists('description', $input));
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_unicode_maximum_lengths_are_accepted(): void
    {
        $this->post(route('tickets.store'), $this->payload(['title' => str_repeat('ą', 120),
            'location' => str_repeat('ł', 100), 'description' => str_repeat('ż', 5000)]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame(120, mb_strlen(Ticket::sole()->title));
    }

    public function test_editing_preserves_status_and_creation_time(): void
    {
        $this->travelTo(now()->setDate(2026, 4, 12)->startOfDay());
        foreach ([TicketStatus::New, TicketStatus::InProgress] as $status) {
            $ticket = Ticket::factory()->create(['status' => $status]);
            $created = $ticket->created_at->toDateTimeString();
            $this->travel(1)->hours();
            $this->get(route('tickets.edit', $ticket))->assertOk()->assertSee('name="_method" value="PATCH"', false);
            $this->patch(route('tickets.update', $ticket), $this->payload(['title' => 'Poprawiony tytuł',
                'status' => 'resolved', 'created_at' => '2000-01-01', 'resolution' => 'Nieuprawnione rozwiązanie',
                'resolved_at' => '2000-01-01', 'archived_at' => '2000-01-01']))
                ->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
            $ticket->refresh();
            $this->assertSame('Poprawiony tytuł', $ticket->title);
            $this->assertSame($status, $ticket->status);
            $this->assertSame($created, $ticket->created_at->toDateTimeString());
            $this->assertNull($ticket->resolved_at);
            $this->assertNull($ticket->archived_at);
            $this->assertNull($ticket->resolution);
        }
    }

    public function test_workflow_and_repeated_operations_preserve_original_data(): void
    {
        $this->travelTo(now()->setDate(2026, 5, 10)->startOfDay());
        $ticket = Ticket::factory()->create();
        $this->post(route('tickets.start', $ticket))->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->resolution);
        $this->post(route('tickets.resolve', $ticket), ['resolution' => '  Naprawiono przewód i sprawdzono sprzęt.  '])
            ->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
        $ticket->refresh();
        $resolved = $ticket->getRawOriginal();
        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertSame(now()->toDateTimeString(), $ticket->resolved_at->toDateTimeString());
        $this->assertSame('Naprawiono przewód i sprawdzono sprzęt.', $ticket->resolution);
        $this->travel(1)->hours();
        $this->post(route('tickets.resolve', $ticket), ['resolution' => 'Inne poprawne rozwiązanie'])
            ->assertSessionHas('error')->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame($resolved, $ticket->refresh()->getRawOriginal());
        $this->post(route('tickets.archive', $ticket))->assertRedirect(route('tickets.index'))->assertSessionHas('success');
        $archived = $ticket->refresh()->getRawOriginal();
        $this->assertSame(now()->toDateTimeString(), $ticket->archived_at->toDateTimeString());
        $this->assertTrue($ticket->archived_at->greaterThanOrEqualTo($ticket->resolved_at));
        $this->assertSame($resolved['resolution'], $ticket->resolution);
        $this->travel(1)->hours();
        $this->post(route('tickets.archive', $ticket))->assertSessionHas('error');
        $this->assertSame($archived, $ticket->refresh()->getRawOriginal());
        $this->get(route('tickets.index'))->assertDontSee($ticket->title);
        $this->get(route('tickets.index', ['view' => 'archived']))->assertSee($ticket->title);
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('tylko do odczytu');
        $this->assertDatabaseCount('tickets', 1);
    }

    public static function invalidResolutions(): array
    {
        return [[''], ['   '], ['krótki'], [str_repeat('ą', 2001)], [['text']]];
    }

    #[DataProvider('invalidResolutions')]
    public function test_invalid_resolution_does_not_change_ticket(mixed $resolution): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $before = $ticket->refresh()->getRawOriginal();
        $this->from(route('tickets.show', $ticket))->post(route('tickets.resolve', $ticket), compact('resolution'))
            ->assertRedirect(route('tickets.show', $ticket))->assertSessionHasErrors('resolution');
        $this->assertSame($before, $ticket->refresh()->getRawOriginal());
    }

    public static function forbiddenTransitions(): array
    {
        return [['new', 'resolve'], ['new', 'archive'], ['in_progress', 'archive'],
            ['in_progress', 'start'], ['resolved', 'start'], ['archived', 'start'], ['archived', 'resolve']];
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbidden_transition_redirects_without_changes(string $state, string $action): void
    {
        $factory = Ticket::factory();
        $ticket = match ($state) {
            'resolved' => $factory->resolved()->create(), 'archived' => $factory->archived()->create(),
            'in_progress' => $factory->inProgress()->create(), default => $factory->create()
        };
        $before = $ticket->refresh()->getRawOriginal();
        $this->post(route('tickets.'.$action, $ticket), ['resolution' => ''])
            ->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('error')->assertSessionHasNoErrors();
        $this->assertSame($before, $ticket->refresh()->getRawOriginal());
    }

    public function test_closed_tickets_cannot_be_edited_even_with_invalid_payload(): void
    {
        foreach ([Ticket::factory()->resolved()->create(), Ticket::factory()->archived()->create()] as $ticket) {
            $before = $ticket->refresh()->getRawOriginal();
            $this->get(route('tickets.edit', $ticket))->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('error');
            foreach ([$this->payload(), []] as $data) {
                $this->patch(route('tickets.update', $ticket), $data)->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('error');
                $this->assertSame($before, $ticket->refresh()->getRawOriginal());
            }
        }
    }

    public function test_filters_are_combined_and_pagination_preserves_them(): void
    {
        $category = Category::factory()->create();
        $this->freezeTime();
        $matches = Ticket::factory()->count(12)->inProgress()->create(['category_id' => $category->id]);
        $new = Ticket::factory()->create(['category_id' => $category->id, 'title' => 'Nowy pasujący kategorią']);
        $other = Ticket::factory()->inProgress()->create(['title' => 'Inna kategoria sprzętu']);
        $archived = Ticket::factory()->archived()->create(['category_id' => $category->id, 'title' => 'Archiwalna usterka']);
        $params = ['view' => 'active', 'status' => 'in_progress', 'category_id' => $category->id];
        $response = $this->get(route('tickets.index', $params))->assertOk()->assertDontSee($new->title)->assertDontSee($other->title)->assertDontSee($archived->title);
        $response->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 12 && $tickets->count() === 10 && $tickets->first()->id === $matches->last()->id);
        $response->assertSee('status=in_progress', false)->assertSee('category_id='.$category->id, false)->assertSee('page=2', false);
        $this->get(route('tickets.index', $params + ['page' => 2]))->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 2);
        $this->get(route('tickets.index', ['status' => 'in_progress']))->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 13);
        $this->get(route('tickets.index', ['category_id' => $category->id]))->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 13);
        $this->get(route('tickets.index', ['view' => 'archived']))->assertSee($archived->title)->assertDontSee($new->title);
    }

    public static function invalidFilters(): array
    {
        return [[['status' => 'wrong']], [['status' => ['new']]], [['category_id' => [1]]], [['category_id' => 99999]],
            [['view' => 'wrong']], [['view' => ['active']]], [['page' => 0]], [['page' => -1]], [['page' => 'abc']],
            [['page' => '1.5']], [['page' => [1]]], [['page' => '']], [['page' => '999999999999999999999999']]];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_redirect_once_to_clean_list(array $params): void
    {
        $this->get(route('tickets.index', $params))->assertRedirect(route('tickets.index'))->assertSessionHas('error');
        $this->get(route('tickets.index'))->assertOk()->assertSee('Wyczyszczono błędne filtry');
    }

    public function test_empty_states_and_empty_filters(): void
    {
        $this->get(route('tickets.index'))->assertSee('Brak zgłoszeń');
        $this->get(route('tickets.index', ['view' => 'archived']))->assertSee('Archiwum jest puste');
        $this->get(route('tickets.index', ['status' => 'resolved']))->assertSee('Brak wyników filtrowania');
        $this->get(route('tickets.index', ['status' => '', 'category_id' => '', 'view' => '']))->assertOk()->assertSee('Brak zgłoszeń');
        $this->get(route('tickets.create'))->assertOk()->assertSee('Brak kategorii')->assertSee('disabled', false);
    }

    public function test_missing_ticket_returns_real_404_for_reads_and_writes(): void
    {
        foreach (['show', 'edit'] as $action) {
            $this->get(route('tickets.'.$action, 99999))->assertNotFound()->assertSee('Nie znaleziono zgłoszenia');
        }
        $this->patch(route('tickets.update', 99999), [])->assertNotFound();
        foreach (['start', 'resolve', 'archive'] as $action) {
            $this->post(route('tickets.'.$action, 99999))->assertNotFound();
        }
        $this->get('/tickets/not-a-number')->assertNotFound();
    }

    public function test_html_is_stored_as_text_and_escaped(): void
    {
        $html = '<script>alert("tekst")</script>';
        $this->post(route('tickets.store'), $this->payload(['title' => $html, 'description' => $html]));
        $ticket = Ticket::sole();
        $this->assertSame($html, $ticket->description);
        $this->get(route('tickets.show', $ticket))->assertSee(e($html), false)->assertDontSee($html, false);
        $this->get(route('tickets.index'))->assertSee(e($html), false)->assertDontSee($html, false);
    }

    public function test_invalid_edit_preserves_record_and_maximum_resolution_is_accepted(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $before = $ticket->refresh()->getRawOriginal();
        $this->patch(route('tickets.update', $ticket), $this->payload(['title' => ' ']))->assertSessionHasErrors('title');
        $this->assertSame($before, $ticket->refresh()->getRawOriginal());
        $this->post(route('tickets.resolve', $ticket), ['resolution' => str_repeat('ą', 2000)])->assertSessionHasNoErrors();
        $this->assertSame(2000, mb_strlen($ticket->refresh()->resolution));
    }

    public function test_demo_seed_has_consistent_states_and_is_not_duplicated(): void
    {
        $this->freezeTime();
        $this->seed();
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('tickets', 16);
        $this->assertSame(6, Ticket::where('status', 'new')->count());
        $this->assertSame(5, Ticket::where('status', 'in_progress')->count());
        $this->assertSame(3, Ticket::where('status', 'resolved')->whereNull('archived_at')->count());
        $this->assertSame(2, Ticket::whereNotNull('archived_at')->count());
        foreach (Ticket::all() as $ticket) {
            if ($ticket->status === TicketStatus::Resolved) {
                $this->assertNotEmpty($ticket->resolution);
                $this->assertTrue($ticket->resolved_at->greaterThanOrEqualTo($ticket->created_at));
                if ($ticket->archived_at) {
                    $this->assertTrue($ticket->archived_at->greaterThanOrEqualTo($ticket->resolved_at));
                }
            } else {
                $this->assertNull($ticket->resolution);
                $this->assertNull($ticket->resolved_at);
                $this->assertNull($ticket->archived_at);
            }
        }
        $this->seed();
        $this->assertDatabaseCount('tickets', 16);
    }

    public function test_database_restricts_deleting_a_used_category(): void
    {
        $ticket = Ticket::factory()->create();
        try {
            $ticket->category->delete();
            $this->fail('Usunięto kategorię używaną przez zgłoszenie.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
        $this->assertDatabaseHas('categories', ['id' => $ticket->category_id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_database_rejects_a_missing_category(): void
    {
        $this->expectException(QueryException::class);
        Ticket::factory()->create(['category_id' => 999999]);
    }

    public function test_invalid_array_input_can_be_rendered_after_redirect(): void
    {
        $this->followingRedirects()->from(route('tickets.create'))->post(route('tickets.store'), [
            'title' => ['bad'], 'location' => ['bad'], 'category_id' => ['bad'], 'description' => ['bad'],
        ])->assertOk()->assertSee('musi być tekstem');
        $this->assertDatabaseCount('tickets', 0);
        $ticket = Ticket::factory()->inProgress()->create();
        $this->followingRedirects()->from(route('tickets.show', $ticket))->post(route('tickets.resolve', $ticket), ['resolution' => ['bad']])
            ->assertOk()->assertSee('musi być tekstem');
        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);
    }
}
