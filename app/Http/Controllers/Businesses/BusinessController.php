<?php

namespace App\Http\Controllers\Businesses;

use App\Actions\Businesses\CreateBusiness;
use App\Actions\Businesses\DeleteBusiness;
use App\Actions\Businesses\LeaveBusiness;
use App\Actions\Businesses\UpdateBusiness;
use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\DeleteBusinessRequest;
use App\Http\Requests\Businesses\SaveBusinessRequest;
use App\Http\Requests\Businesses\StoreBusinessRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
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
    public function store(StoreBusinessRequest $request, CreateBusiness $createBusiness): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        [$business, $requiresVerification] = $createBusiness->execute(
            $user,
            $validated['name'],
            $validated['email'] ?? $user->email,
            phone: $validated['phone'] ?? null,
            phoneCountry: isset($validated['phone_country']) ? Country::tryFrom($validated['phone_country']) : null
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business created.')]);

        if ($requiresVerification) {
            return to_route('businesses.phone.notice', ['business' => $business->slug]);
        }

        if ($user->businesses()->count() === 1) {
            return to_route('dashboard', ['business' => $business->slug]);
        }

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Show the business edit page.
     */
    public function edit(Request $request, Business $business): Response
    {
        $user = $request->user();

        $country = $business->phone_country?->value;

        return Inertia::render('businesses/edit', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'email' => $business->email,
                'phone' => $business->phone,
                'phoneCountry' => $country,
                'isPersonal' => $business->is_personal,
            ],
            'countries' => collect(Country::cases())->map(fn (Country $country) => [
                'code' => $country->value,
                'name' => $country->label(),
                'flag' => $country->flag(),
                'dialCode' => $country->dialCode(),
                'placeholder' => $country->placeholder(),
            ])->toArray(),
            'permissions' => $user->toBusinessPermissions($business),
        ]);
    }

    /**
     * Update the specified business.
     */
    public function update(SaveBusinessRequest $request, Business $business, UpdateBusiness $updateBusiness): RedirectResponse
    {
        Gate::authorize('update', $business);

        $user = $request->user();

        [$business, $requiresVerification] = $updateBusiness->execute($user, $business, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business updated.')]);

        if ($requiresVerification) {
            return to_route('businesses.phone.notice', ['business' => $business->slug]);
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
    public function leave(Request $request, Business $business, LeaveBusiness $leaveBusiness): RedirectResponse
    {
        Gate::authorize('leave', $business);

        $leaveBusiness->execute($request->user(), $business);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left the business ":name"', ['name' => $business->name])]);

        return to_route('dashboard');
    }

    /**
     * Delete the specified business.
     */
    public function destroy(DeleteBusinessRequest $request, Business $business, DeleteBusiness $deleteBusiness): RedirectResponse
    {
        $deleteBusiness->execute($request->user(), $business);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business deleted.')]);

        return to_route('dashboard');
    }
}
