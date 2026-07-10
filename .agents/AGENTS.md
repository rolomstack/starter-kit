
<RULE>
# Enums for Statuses
Always use PHP 8.1 Backed Enums for any `status`, `type`, or `role` column in the database.
- Cast the column in the Eloquent model using the enum class (e.g. `'status' => SubscriptionStatus::class`).
- Implement helper methods on the Enum (e.g. `public function active(): bool { return $this === self::Active; }`) so that conditionals can be written cleanly as `$model->status->active()`.
- Never use raw magic strings for assignments or comparisons.
</RULE>


<RULE>
# Strict Domain Decoupling via Events
Domains must not directly interact with each other's services or gateways. For example, the Subscription domain must not invoke the Payment Gateway.
- All cross-domain communication must happen via Laravel Events (e.g., `PaymentSucceeded`).
- The Payment domain exclusively handles gateway verification and tokenization, then broadcasts the event.
- Other domains (Subscriptions, Orders) act as listeners to these generic events.
</RULE>

