<?php

namespace App\Http\Middleware;

use App\Contracts\MustVerifyPhone;
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
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check Business phone verification
        $businessRouteParam = $request->route('current_business') ?? $request->route('business');
        if ($businessRouteParam) {
            $business = $businessRouteParam;
            if (is_string($business)) {
                $business = Business::where('slug', $business)->first();
            }

            if ($business instanceof MustVerifyPhone && $business->getPhoneForVerification() && ! $business->hasVerifiedPhone()) {
                $slug = is_string($businessRouteParam) ? $businessRouteParam : $business->slug;
                if (! $request->is("{$slug}/phone/verify", "{$slug}/phone/verify/resend", 'logout')) {
                    return $request->expectsJson()
                        ? abort(403, 'The business phone number is not verified.')
                        : redirect()->route('businesses.phone.notice', ['current_business' => $slug]);
                }
            }
        }

        return $next($request);
    }
}
