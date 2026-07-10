<?php

use App\Contracts\PaymentGateway;
use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->create();
    $this->business->members()->attach($this->user, ['role' => BusinessRole::Owner->value]);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 500000,
        'currency' => 'NGN',
        'interval' => 'monthly',
        'is_active' => true,
    ]);
});

it('displays the subscription index page', function () {
    actingAs($this->user)
        ->get(route('businesses.subscriptions.index', $this->business))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('businesses/subscriptions/index')
            ->has('plans', 1)
        );
});

it('can initiate checkout', function () {
    $this->mock(PaymentGateway::class, function (MockInterface $mock) {
        $mock->shouldReceive('name')->andReturn('paystack');
        $mock->shouldReceive('initializeTransaction')->once()->andReturn(new \App\DTOs\Payments\GatewayInitialization(
            reference: 'ref_123',
            checkoutUrl: 'https://checkout.paystack.com/test',
            customerId: 'CUST_123',
            raw: []
        ));
    });

    actingAs($this->user)
        ->post(route('businesses.subscriptions.checkout', $this->business), [
            'plan_id' => $this->plan->id,
        ])
        ->assertRedirect('https://checkout.paystack.com/test');
});
