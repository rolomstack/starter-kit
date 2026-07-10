<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ConfirmPayment;
use App\Contracts\PaymentGateway;
use App\DTOs\Payments\GatewayTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(
        Request $request,
        PaymentGateway $gateway,
        ConfirmPayment $confirmer
    ): JsonResponse {
        // Only allow POST requests
        if (! $request->isMethod('post')) {
            abort(405);
        }

        // Validate webhook signature
        $signature = $request->header('x-paystack-signature');
        $secret = config('services.paystack.secret_key');

        if (! $signature || ! hash_equals($signature, hash_hmac('sha512', $request->getContent(), $secret))) {
            Log::warning('Invalid Paystack Webhook Signature');
            abort(401, 'Invalid signature');
        }

        $payload = $request->all();
        $event = $payload['event'] ?? '';

        Log::info("Paystack Webhook Received: {$event}");

        switch ($event) {
            case 'charge.success':
                $transactionDto = $gateway->parseWebhookPayload($payload['data'] ?? []);
                $this->handleChargeSuccess($transactionDto, $confirmer);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleChargeSuccess(GatewayTransaction $transaction, ConfirmPayment $confirmer): void
    {
        $reference = $transaction->reference;

        if (! $reference) {
            return;
        }

        try {
            $confirmer->execute($reference, $transaction);
        } catch (\Exception $e) {
            Log::error('Webhook ConfirmPayment Failed: '.$e->getMessage());
        }
    }
}
