<?php

namespace App\Http\Controllers\Businesses;

use App\Actions\Businesses\AcceptBusinessInvitation;
use App\Actions\Businesses\CancelBusinessInvitation;
use App\Actions\Businesses\CreateBusinessInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\CreateBusinessInvitationRequest;
use App\Http\Requests\Businesses\RespondToBusinessInvitationRequest;
use App\Models\Business;
use App\Models\BusinessInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BusinessInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(
        CreateBusinessInvitationRequest $request,
        Business $business,
        CreateBusinessInvitation $createBusinessInvitation
    ): RedirectResponse {
        Gate::authorize('inviteMember', $business);

        $createBusinessInvitation->execute(
            $business,
            $request->validated('email'),
            $request->validated('role'),
            $request->user()
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('businesses.teams.index', ['business' => $business->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(
        Business $business,
        BusinessInvitation $invitation,
        CancelBusinessInvitation $cancelBusinessInvitation
    ): RedirectResponse {
        abort_unless($invitation->business_id === $business->id, 404);

        Gate::authorize('cancelInvitation', $business);

        $cancelBusinessInvitation->execute($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('businesses.teams.index', ['business' => $business->slug]);
    }

    public function accept(
        RespondToBusinessInvitationRequest $request,
        BusinessInvitation $invitation,
        AcceptBusinessInvitation $acceptBusinessInvitation,
    ): RedirectResponse {
        $user = $request->user();

        $acceptBusinessInvitation->handle($user, $invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

        return to_route('dashboard');
    }

    /**
     * Decline the invitation.
     */
    public function decline(RespondToBusinessInvitationRequest $request, BusinessInvitation $invitation): RedirectResponse
    {
        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return to_route('dashboard');
    }
}
