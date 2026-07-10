
## Billing & Subscriptions Architecture

* **App-Driven Subscriptions:** Never rely on a payment gateway's built-in recurring subscription engine (e.g., Paystack Subscriptions). The application must act as the sole source of truth for subscription lifecycles.
* **Tokenized Billing:** Use gateways strictly for processing one-off charges. Save tokenized payment methods (e.g., authorization codes) to a generic `PaymentMethod` model.
* **Cron-Based Renewals:** Subscriptions should be renewed via a daily scheduled job (cron) in Laravel that attempts to charge the tokenized `PaymentMethod` via the appropriate gateway.
* **Dunning & History:** Always track individual billing attempts (successes and failures) in a separate model (e.g., `SubscriptionPayment` or `Invoice`) to maintain a complete history of how many times a user has been billed or subscribed.

