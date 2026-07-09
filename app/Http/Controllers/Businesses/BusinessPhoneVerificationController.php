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
    public function show(Request $request, Business $current_business): RedirectResponse|Response
    {
        if ($current_business->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard', ['current_business' => $current_business->slug]));
        }

        return Inertia::render('businesses/verify-phone', [
            'business' => [
                'name' => $current_business->name,
                'slug' => $current_business->slug,
                'email' => $current_business->email,
                'phone' => $current_business->phone,
                'phoneCountry' => $current_business->phone_country?->value,
            ],
            'canUpdatePhone' => $request->user()->ownsBusiness($current_business),
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
                    $exists = $current_business->codes()
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

        $current_business->codes()
            ->phoneVerification()
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
