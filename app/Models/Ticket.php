<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'location', 'category_id', 'description'];

    protected function casts(): array
    {
        return ['status' => TicketStatus::class, 'resolved_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
