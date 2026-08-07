<?php

namespace App\Application\Referrals;

use App\Application\Mail\Actions\SendMailAction;
use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    public const SIGNUP_VIP_DAYS = 3;
    public const SIGNUP_MIRROR_DAYS = 3;
    public const LEVEL30_REWARD_GEMS = 200;
    public const LEVEL30_THRESHOLD = 30;

    /** Dozwolone źródła kampanii marketingowych (reflinki np. do reklam na Facebooku/YouTube). */
    public const MARKETING_SOURCES = ['facebook', 'youtube', 'tiktok'];

    public function resolveReferrerFromCode(?string $code): ?User
    {
        if (empty($code)) {
            return null;
        }

        return User::where('referral_code', $code)->first();
    }

    /**
     * Przyznaje nowo zarejestrowanemu userowi bonus za rejestrację z linku
     * polecającego: 3 dni VIP + 3 dni "zamrożonego" dostępu do Lustra,
     * czekającego na przypisanie do pierwszej utworzonej postaci.
     */
    public function applySignupReward(User $newUser, User $referrer): void
    {
        if ($newUser->id === $referrer->id) {
            return;
        }

        if ($newUser->referral_signup_bonus_claimed_at !== null) {
            return;
        }

        DB::transaction(function () use ($newUser, $referrer) {
            $now = now();

            $premiumUntil = ($newUser->premium_until && $newUser->premium_until->isFuture())
                ? $newUser->premium_until->addDays(self::SIGNUP_VIP_DAYS)
                : $now->copy()->addDays(self::SIGNUP_VIP_DAYS);

            $newUser->update([
                'referred_by_user_id' => $referrer->id,
                'premium_until' => $premiumUntil,
                'referral_mirror_bonus_until' => $now->copy()->addDays(self::SIGNUP_MIRROR_DAYS),
                'referral_signup_bonus_claimed_at' => $now,
            ]);
        });

        Log::info('Referral signup reward granted', [
            'new_user_id' => $newUser->id,
            'referrer_id' => $referrer->id,
        ]);
    }

    /**
     * Przyznaje nowo zarejestrowanemu userowi taki sam bonus jak przy poleceniu
     * znajomego (3 dni VIP + 3 dni "zamrożonego" dostępu do Lustra), gdy konto
     * zostało założone poprzez reflink kampanii marketingowej (np. Facebook/YouTube),
     * a nie przez polecenie innego gracza. Nie ma tu "referrera" - służy wyłącznie
     * do atrybucji źródła rejestracji (`users.signup_source`) na potrzeby statystyk
     * w panelu administratora.
     */
    public function applyCampaignSignupReward(User $newUser, string $source): void
    {
        if (!in_array($source, self::MARKETING_SOURCES, true)) {
            return;
        }

        if ($newUser->referral_signup_bonus_claimed_at !== null) {
            return;
        }

        DB::transaction(function () use ($newUser, $source) {
            $now = now();

            $premiumUntil = ($newUser->premium_until && $newUser->premium_until->isFuture())
                ? $newUser->premium_until->addDays(self::SIGNUP_VIP_DAYS)
                : $now->copy()->addDays(self::SIGNUP_VIP_DAYS);

            $newUser->update([
                'signup_source' => $source,
                'premium_until' => $premiumUntil,
                'referral_mirror_bonus_until' => $now->copy()->addDays(self::SIGNUP_MIRROR_DAYS),
                'referral_signup_bonus_claimed_at' => $now,
            ]);
        });

        Log::info('Marketing campaign signup reward granted', [
            'new_user_id' => $newUser->id,
            'source' => $source,
        ]);
    }

    /**
     * Przypisuje "zamrożony" bonusowy dostęp do Lustra (jeśli jeszcze ważny
     * i nieprzypisany) do świeżo utworzonej postaci. Bonus jest jednorazowy,
     * więc po zużyciu czyścimy go z konta usera.
     */
    public function grantPendingMirrorBonus(Character $character): void
    {
        $user = $character->user;

        if (!$user || !$user->referral_mirror_bonus_until || $user->referral_mirror_bonus_until->isPast()) {
            return;
        }

        $bonusUntil = $user->referral_mirror_bonus_until;

        $currentAccess = $character->mirror_access_until;
        if (!$currentAccess || $bonusUntil->greaterThan($currentAccess)) {
            $character->mirror_access_until = $bonusUntil;
            $character->save();
        }

        $user->update(['referral_mirror_bonus_until' => null]);
    }

    /**
     * Wypłaca referrerowi 200 gemów na pocztę, gdy polecone przez niego konto
     * osiągnie 30 poziom na dowolnej postaci - jednorazowo na całe konto
     * poleconego, niezależnie od liczby postaci które wbiją 30 lvl.
     */
    public function grantLevel30ReferralReward(Character $character): void
    {
        if ($character->level < self::LEVEL30_THRESHOLD) {
            return;
        }

        $referredUser = $character->user;
        if (!$referredUser || !$referredUser->referred_by_user_id) {
            return;
        }

        if ($referredUser->referral_level30_reward_granted_at !== null) {
            return;
        }

        $referrer = User::find($referredUser->referred_by_user_id);
        if (!$referrer) {
            return;
        }

        $updated = DB::transaction(function () use ($referredUser) {
            return User::where('id', $referredUser->id)
                ->whereNull('referral_level30_reward_granted_at')
                ->update(['referral_level30_reward_granted_at' => now()]);
        });

        if ($updated === 0) {
            // Ktoś inny (równoległy request) już przyznał tę nagrodę.
            return;
        }

        $recipientCharacter = $referrer->characters()->orderByDesc('updated_at')->first();
        if (!$recipientCharacter) {
            return;
        }

        app(SendMailAction::class)->execute(
            toCharacterId: $recipientCharacter->id,
            subject: 'Nagroda za polecenie znajomego',
            body: "Twój znajomy {$referredUser->name} osiągnął 30 poziom! W nagrodę otrzymujesz " . self::LEVEL30_REWARD_GEMS . ' Gemów.',
            attachments: [
                ['type' => 'gems', 'qty' => self::LEVEL30_REWARD_GEMS],
            ],
        );

        Log::info('Referral level-30 reward granted', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'character_id' => $character->id,
        ]);
    }
}
