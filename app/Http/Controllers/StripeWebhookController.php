<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook Error: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe Webhook Error: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;
            default:
                Log::info('Stripe Webhook: Received unhandled event type', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        if ($session->payment_status !== 'paid') {
            return;
        }

        $metadata = $session->metadata;
        $userId = $metadata->user_id ?? $session->client_reference_id ?? null;
        $gemAmount = (int) ($metadata->gem_amount ?? 0);
        
        if (!$userId || $gemAmount <= 0) {
            Log::error('Stripe Webhook Error: Missing metadata in session', ['session_id' => $session->id]);
            return;
        }

        $cacheKey = 'stripe_processed_session_' . $session->id;
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::info('Stripe Webhook: Session already processed', ['session_id' => $session->id]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Stripe Webhook Error: User not found', ['user_id' => $userId]);
            return;
        }

        DB::transaction(function () use ($user, $gemAmount, $cacheKey) {
            $user->gems += $gemAmount;
            $user->save();
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(30));
        });

        Log::info("Stripe Webhook: Successfully added $gemAmount gems to user {$user->id}");
    }
}
