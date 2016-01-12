<?php

namespace App\Http\Middleware;

use Auth;
use Carbon\Carbon;
use Closure;
use Session;

class TrackInactivity
{
    protected $except = [
        'mship/auth/logout/1',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // if they're logged in
        if (Auth::check() && Session::has('last_activity')) {
            // if their session timeout has been exceeded
            $timeout = Auth::user()->session_timeout;
            $inactive = Carbon::now()->diffInMinutes(Carbon::parse(Session::get('last_activity')));
            if ($timeout !== 0 && $inactive >= $timeout) {
                // log them out
                return redirect()->route('mship.auth.logout', ['force' => 1]);
            }
        }

        // process the request
        $response = $next($request);

        // update their activity after the request has been processed
        Session::put('last_activity', Carbon::now());

        return $response;

    }

    /**
     * Determine if the request has a URI that should pass through.
     *
     * Method used from Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
