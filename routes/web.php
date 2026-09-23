<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('tickets.index'))->name('home');
Route::pattern('ticket', '[0-9]+');
Route::resource('tickets', TicketController::class)->except(['destroy', 'update']);
Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
Route::post('/tickets/{ticket}/start', [TicketController::class, 'start'])->name('tickets.start');
Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
Route::post('/tickets/{ticket}/archive', [TicketController::class, 'archive'])->name('tickets.archive');
