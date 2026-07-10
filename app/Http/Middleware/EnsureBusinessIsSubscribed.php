<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessIsSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $this->business($request);

        if ($business && ! $business->isSubscribed()) {
            return redirect()->route('businesses.subscriptions.index', ['business' => $business]);
        }

        return $next($request);
    }

    /**
     * Get the business associated with the request.
     */
    protected function business(Request $request): ?Business
    {
        $business = $request->route('business');

        if (is_string($business)) {
            $business = Business::where('slug', $business)->first();
        }

        return $business;
    }
}
