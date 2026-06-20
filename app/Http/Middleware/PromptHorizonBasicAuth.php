<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Horizon\Horizon;
use Symfony\Component\HttpFoundation\Response;

class PromptHorizonBasicAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing']) || Horizon::check($request)) {
            return $next($request);
        }

        if (blank(config('horizon.allowed_email')) || blank(config('horizon.password'))) {
            abort(403);
        }

        return response('Unauthorized.', 401, [
            'WWW-Authenticate' => 'Basic realm="Horizon", charset="UTF-8"',
        ]);
    }
}
