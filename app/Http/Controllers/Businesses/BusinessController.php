<?php

namespace App\Http\Controllers\Businesses;

use App\Actions\Businesses\CreateBusiness;
use App\Enums\BusinessRole;
use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\DeleteBusinessRequest;
use App\Http\Requests\Businesses\SaveBusinessRequest;
use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use App\Rules\BusinessName;
use App\Rules\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Display a listing of the user's businesses.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('businesses/index', [
            'businesses' => $user->toUserBusinesses(includeCurrent: true),
        ]);
    }

    /**
     * Show the page to create a new business.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('businesses/create', [
            'countries' => collect(Country::cases())->map(fn (Country $country) => [
                'code' => $country->value,
                'name' => $country->label(),
                'flag' => $country->flag(),
                'dialCode' => $country->dialCode(),
                'placeholder' => $country->placeholder(),
            ])->toArray(),
        ]);
    }

    /**
     * Store a newly created business.
     */
    public function store(Request $request, CreateBusiness $createBusiness): RedirectResponse
    {
        $user = $request->user();
        $isFirstBusiness = ! $user->personalBusiness() && ! $user->businesses()->exists();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new BusinessName],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_country' => ['nullable', new Enum(Country::class)],
            'phone' => ['nullable', 'string', new Phone('phone_country')],
        ]);

        $hasUsedPhoneBefore = false;
        $phone = $validated['phone'] ?? null;

        $business = DB::transaction(function () use ($phone, $createBusiness, $user, $validated, $isFirstBusiness, &$hasUsedPhoneBefore) {
            $business = $createBusiness->handle(
                $user,
                $validated['name'],
                $validated['email'] ?? $user->email,
                phone: $validated['phone'] ?? null,
                phoneCountry: isset($validated['phone_country']) ? Country::tryFrom($validated['phone_country']) : null,
                isPersonal: $isFirstBusiness
            );

            if ($phone) {
                $hasUsedPhoneBefore = $user->businesses()
                    ->where('phone', $phone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();

                if ($hasUsedPhoneBefore) {
                    $business->markPhoneAsVerified();
                }
            }

            return $business;
        });

        if ($phone && ! $hasUsedPhoneBefore) {
            $business->sendPhoneVerificationNotification();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business created.')]);

        if ($phone && ! $hasUsedPhoneBefore) {
            return to_route('businesses.phone.notice', ['current_business' => $business->slug]);
        }

        if ($isFirstBusiness) {
            return to_route('dashboard', ['current_business' => $business->slug]);
        }

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Show the business edit page.
     */
    public function edit(Request $request, Business $business): Response
    {
        $user = $request->user();

        $detectedCountry = $business->phone_country?->value;

        return Inertia::render('businesses/edit', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'email' => $business->email,
                'phone' => $business->phone,
                'phoneCountry' => $detectedCountry,
                'isPersonal' => $business->is_personal,
            ],
            'countries' => collect(Country::cases())->map(fn (Country $country) => [
                'code' => $country->value,
                'name' => $country->label(),
                'flag' => $country->flag(),
                'dialCode' => $country->dialCode(),
                'placeholder' => $country->placeholder(),
            ])->toArray(),
            'members' => $business->members()->get()->map(function (User $member) {
                /** @var Membership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'invitations' => $business->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => $user->toBusinessPermissions($business),
            'availableRoles' => BusinessRole::assignable(),
        ]);
    }

    /**
     * Update the specified business.
     */
    public function update(SaveBusinessRequest $request, Business $business): RedirectResponse
    {
        Gate::authorize('update', $business);

        $user = $request->user();
        $oldPhone = $business->phone;
        $newPhone = $request->validated('phone');

        $phoneChanged = $newPhone !== $oldPhone;
        $hasUsedPhoneBefore = false;

        $business = DB::transaction(function () use ($request, $business, $phoneChanged, $newPhone, $user, &$hasUsedPhoneBefore) {
            $business = Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            $business->fill([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $newPhone,
                'phone_country' => $request->validated('phone_country'),
            ]);

            if ($phoneChanged) {
                $business->phone_verified_at = null;
            }

            $business->save();

            if ($phoneChanged && $newPhone) {
                $hasUsedPhoneBefore = $user->businesses()
                    ->where('phone', $newPhone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();

                if ($hasUsedPhoneBefore) {
                    $business->markPhoneAsVerified();
                }
            }

            return $business;
        });

        if ($phoneChanged && $newPhone && ! $hasUsedPhoneBefore) {
            $business->sendPhoneVerificationNotification();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business updated.')]);

        if ($phoneChanged && $newPhone && ! $hasUsedPhoneBefore) {
            return to_route('businesses.phone.notice', ['current_business' => $business->slug]);
        }

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Switch the user's current business.
     */
    public function switch(Request $request, Business $business): RedirectResponse
    {
        abort_unless($request->user()->belongsToBusiness($business), 403);

        $request->user()->switchBusiness($business);

        return back();
    }

    /**
     * Leave the specified business.
     */
    public function leave(Request $request, Business $business): RedirectResponse
    {
        Gate::authorize('leave', $business);

        $user = $request->user();

        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left the business ":name"', ['name' => $business->name])]);

        return to_route('businesses.index');
    }

    /**
     * Delete the specified business.
     */
    public function destroy(DeleteBusinessRequest $request, Business $business): RedirectResponse
    {
        $user = $request->user();
        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        DB::transaction(function () use ($user, $business) {
            User::where('current_business_id', $business->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchBusiness($affectedUser->personalBusiness()));

            $business->invitations()->delete();
            $business->memberships()->delete();
            $business->delete();
        });

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business deleted.')]);

        return to_route('businesses.index');
    }
}
