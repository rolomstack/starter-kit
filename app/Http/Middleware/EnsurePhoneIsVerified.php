<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $this->resolveBusiness($request);

        if (! $business || ! $business->getPhoneForVerification() || $business->hasVerifiedPhone()) {
            return $next($request);
        }

        if ($request->is(
            "{$business->slug}/phone/verify",
            "{$business->slug}/phone/verify/resend",
            'logout'
        )) {
            return $next($request);
        }

        if ($request->routeIs('businesses.update') && $request->user()?->ownsBusiness($business)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403, 'The business phone number is not verified.')
            : redirect()->route('businesses.phone.notice', ['business' => $business]);
    }

    /**
     * Resolve the business model from the route parameters.
     */
    protected function resolveBusiness(Request $request): ?Business
    {
        $business = $request->route('business');

        /** @var ?Business $resolved */
        $resolved = is_string($business)
            ? Business::where('slug', $business)->first()
            : $business;

        return $resolved;
    }
}
