<?php

namespace App\Http\Controllers\Businesses;

use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Rules\Phone;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessPhoneVerificationController extends Controller
{
    /**
     * Show the business phone verification notice page.
     */
    public function show(Request $request, Business $business): RedirectResponse|Response
    {
        if ($business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['business' => $business->slug]));
        }

        return Inertia::render('businesses/verify-phone', [
            'business' => [
                'name' => $business->name,
                'slug' => $business->slug,
                'email' => $business->email,
                'phone' => $business->phone,
                'phoneCountry' => $business->phone_country?->value,
            ],
            'canUpdatePhone' => $request->user()->ownsBusiness($business),
            'countries' => collect(Country::cases())->map(fn (Country $country) => [
                'code' => $country->value,
                'name' => $country->label(),
                'flag' => $country->flag(),
                'dialCode' => $country->dialCode(),
                'placeholder' => $country->placeholder(),
            ])->toArray(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Verify the business's phone number using the verification code.
     */
    public function verify(Request $request, Business $business): RedirectResponse
    {
        if ($business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['business' => $business->slug]));
        }

        $request->validate([
            'code' => [
                'required',
                'string',
                'size:6',
                function (string $attribute, mixed $value, Closure $fail) use ($business) {
                    $exists = $business->codes()
                        ->phoneVerification()
                        ->where('code', $value)
                        ->where('expires_at', '>=', now())
                        ->exists();

                    if (! $exists) {
                        $fail(__('The code you entered is invalid or has expired.'));
                    }
                },
            ],
        ]);

        $business->codes()
            ->phoneVerification()
            ->delete();

        $business->markPhoneAsVerified();

        return redirect()->intended(route('dashboard', ['business' => $business->slug]));
    }

    /**
     * Resend the business's phone verification code.
     */
    public function resend(Request $request, Business $business): RedirectResponse
    {
        if ($business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['business' => $business->slug]));
        }

        $business->sendPhoneVerificationNotification();

        return back()->with('status', 'code-sent');
    }
}
