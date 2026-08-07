<?php

namespace App\Application\Payments;

use App\Models\StripeProcessedSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreditGemsFromStripeSession
{
    /**
     * Atomically marks a Stripe checkout session as processed and credits gems to the user, or
     * does nothing (returns false) if the session was already processed. The DB-level unique
     * constraint on stripe_processed_sessions.stripe_session_id - not an application-side check -
     * is what makes this safe when the webhook and the itemshop success-url redirect both try to
     * process the same session concurrently.
     */
    public function execute(string $sessionId, User $user, int $gemAmount): bool
    {
        try {
            return DB::transaction(function () use ($sessionId, $user, $gemAmount) {
                StripeProcessedSession::create([
                    'stripe_session_id' => $sessionId,
                    'user_id' => $user->id,
                    'gem_amount' => $gemAmount,
                ]);

                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                $lockedUser->gems += $gemAmount;
                $lockedUser->save();

                return true;
            });
        } catch (QueryException $e) {
            return false;
        }
    }
}
