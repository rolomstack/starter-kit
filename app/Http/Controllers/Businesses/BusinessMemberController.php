<?php

namespace App\Http\Controllers\Businesses;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\UpdateBusinessMemberRequest;
use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessMemberController extends Controller
{
    /**
     * Show the business teams page.
     */
    public function index(Request $request, Business $business): Response
    {
        $user = $request->user();

        return Inertia::render('businesses/teams/index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'isPersonal' => $business->is_personal,
            ],
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
     * Update the specified business member's role.
     */
    public function update(UpdateBusinessMemberRequest $request, Business $business, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $business);

        $newRole = BusinessRole::from($request->validated('role'));

        $business->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('businesses.teams.index', ['business' => $business->slug]);
    }

    /**
     * Remove the specified business member.
     */
    public function destroy(Business $business, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $business);

        abort_if($business->owner()?->is($user), 403, __('The business owner cannot be removed.'));

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentBusiness($business)) {
            $user->switchBusiness($user->personalBusiness());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('businesses.teams.index', ['business' => $business->slug]);
    }
}
