<?php

use App\Http\Controllers\PokerController;
use App\Http\Middleware\ResolvePokerParticipant;
use Illuminate\Support\Facades\Route;

Route::middleware(ResolvePokerParticipant::class)->group(function (): void {
    Route::get('/', [PokerController::class, 'index'])->name('home');
    Route::post('/inscription', [PokerController::class, 'subscribe'])
        ->middleware('throttle:6,1')
        ->name('poker.subscribe');
    Route::post('/votes', [PokerController::class, 'storeVotes'])
        ->middleware('throttle:30,1')
        ->name('poker.votes.store');
    Route::post('/dates', [PokerController::class, 'storeProposedDate'])
        ->middleware('throttle:20,1')
        ->name('poker.dates.store');
    Route::post('/presence', [PokerController::class, 'storeAttendance'])
        ->middleware('throttle:30,1')
        ->name('poker.attendance.store');
    Route::post('/renvoyer-lien', [PokerController::class, 'resendAccessLink'])
        ->middleware('throttle:3,1')
        ->name('poker.access.resend');
    Route::post('/deconnexion', [PokerController::class, 'logout'])
        ->name('poker.logout');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
