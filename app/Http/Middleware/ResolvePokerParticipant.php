<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use App\Services\PokerSchedulingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePokerParticipant
{
    public function __construct(private PokerSchedulingService $scheduling) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token') ?? $request->cookie(config('poker.cookie_name'));
        $participant = $this->scheduling->findParticipantByToken($token);

        $request->attributes->set('poker_participant', $participant);

        $response = $next($request);

        if ($participant instanceof Participant && $request->query('token') === $participant->token) {
            return $response->withCookie(cookie(
                name: config('poker.cookie_name'),
                value: $participant->token,
                minutes: config('poker.cookie_lifetime'),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
