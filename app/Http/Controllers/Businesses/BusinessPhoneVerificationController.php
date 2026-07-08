<?php

namespace App\Http\Controllers\Businesses;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BusinessPhoneVerificationController extends Controller
{
    /**
     * Show the business phone verification notice page.
     */
    public function show(Request $request, Business $current_business): RedirectResponse|Response
    {
        if ($current_business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['current_business' => $current_business->slug]));
        }

        return Inertia::render('businesses/verify-phone', [
            'business' => [
                'name' => $current_business->name,
                'slug' => $current_business->slug,
                'phone' => $current_business->phone,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Verify the business's phone number using the verification code.
     */
    public function verify(Request $request, Business $current_business): RedirectResponse
    {
        if ($current_business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['current_business' => $current_business->slug]));
        }

        $request->validate([
            'code' => [
                'required',
                'string',
                'size:6',
                function (string $attribute, mixed $value, Closure $fail) use ($current_business) {
                    $exists = DB::table('phone_verification_tokens')
                        ->where('tokenable_type', $current_business->getMorphClass())
                        ->where('tokenable_id', $current_business->getKey())
                        ->where('token', $value)
                        ->where('expires_at', '>=', now())
                        ->exists();

                    if (! $exists) {
                        $fail(__('The code you entered is invalid or has expired.'));
                    }
                },
            ],
        ]);

        DB::table('phone_verification_tokens')
            ->where('tokenable_type', $current_business->getMorphClass())
            ->where('tokenable_id', $current_business->getKey())
            ->delete();

        $current_business->markPhoneAsVerified();

        return redirect()->intended(route('dashboard', ['current_business' => $current_business->slug]));
    }

    /**
     * Resend the business's phone verification code.
     */
    public function resend(Request $request, Business $current_business): RedirectResponse
    {
        if ($current_business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['current_business' => $current_business->slug]));
        }

        $current_business->sendPhoneVerificationNotification();

        return back()->with('status', 'code-sent');
    }
}
