<?php

use App\Http\Controllers\PokerController;
use App\Http\Middleware\ResolvePokerParticipant;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, [
    'Content-Type' => 'text/plain',
]))->name('robots');

Route::middleware(ResolvePokerParticipant::class)->group(function (): void {
    Route::get('/', [PokerController::class, 'index'])->name('home');
    Route::get('/historique', [PokerController::class, 'history'])->name('poker.history');
    Route::patch('/historique/dates/{proposedDate}/gagnant', [PokerController::class, 'updatePastNightWinner'])
        ->middleware('throttle:30,1')
        ->name('poker.history.winner.update');
    Route::get('/dates/{proposedDate}/agenda.ics', [PokerController::class, 'calendar'])
        ->name('poker.dates.calendar');
    Route::post('/inscription', [PokerController::class, 'subscribe'])
        ->middleware('throttle:6,1')
        ->name('poker.subscribe');
    Route::post('/connexion', [PokerController::class, 'quickLogin'])
        ->middleware('throttle:10,1')
        ->name('poker.login');
    Route::patch('/profil', [PokerController::class, 'updateProfile'])
        ->middleware('throttle:20,1')
        ->name('poker.profile.update');
    Route::post('/votes', [PokerController::class, 'storeVotes'])
        ->middleware('throttle:30,1')
        ->name('poker.votes.store');
    Route::post('/dates', [PokerController::class, 'storeProposedDate'])
        ->middleware('throttle:20,1')
        ->name('poker.dates.store');
    Route::patch('/dates/{proposedDate}', [PokerController::class, 'updateProposedDate'])
        ->middleware('throttle:20,1')
        ->name('poker.dates.update');
    Route::delete('/dates/{proposedDate}', [PokerController::class, 'destroyProposedDate'])
        ->middleware('throttle:20,1')
        ->name('poker.dates.destroy');
    Route::post('/dates/{proposedDate}/relance', [PokerController::class, 'remindNonVoters'])
        ->middleware('throttle:10,1')
        ->name('poker.dates.remind');
    Route::post('/presence', [PokerController::class, 'storeAttendance'])
        ->middleware('throttle:30,1')
        ->name('poker.attendance.store');
    Route::post('/renvoyer-lien', [PokerController::class, 'resendAccessLink'])
        ->middleware('throttle:3,1')
        ->name('poker.access.resend');
    Route::post('/deconnexion', [PokerController::class, 'logout'])
        ->name('poker.logout');
    Route::post('/admin/renvoyer-confirmations', [PokerController::class, 'adminResendConfirmationToAll'])
        ->middleware('throttle:6,1')
        ->name('poker.admin.confirmation.resend-all');
    Route::post('/admin/participants/{participant}/renvoyer-confirmation', [PokerController::class, 'adminResendConfirmationToParticipant'])
        ->middleware('throttle:20,1')
        ->name('poker.admin.participants.confirmation.resend');
    Route::post('/admin/participants/{participant}/renvoyer-lien', [PokerController::class, 'adminResendAccessLinkToParticipant'])
        ->middleware('throttle:20,1')
        ->name('poker.admin.participants.access.resend');
    Route::delete('/admin/participants/{participant}', [PokerController::class, 'adminDestroyParticipant'])
        ->middleware('throttle:20,1')
        ->name('poker.admin.participants.destroy');
});
