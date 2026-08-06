<?php

use App\Application\Characters\CreateCharacter;
use App\Application\Referrals\ReferralService;
use App\Domain\Characters\Events\CharacterLeveledUp;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Mail;
use App\Models\User;

test('user gets a unique referral code on creation', function () {
    $user = User::factory()->create();

    expect($user->referral_code)->not->toBeNull();
    expect(strlen($user->referral_code))->toBe(8);
});

test('resolveReferrerFromCode finds the owning user', function () {
    $referrer = User::factory()->create();

    $found = app(ReferralService::class)->resolveReferrerFromCode($referrer->referral_code);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($referrer->id);
});

test('resolveReferrerFromCode returns null for unknown code', function () {
    $found = app(ReferralService::class)->resolveReferrerFromCode('DOESNOTEXIST');

    expect($found)->toBeNull();
});

test('applySignupReward grants VIP and freezes a mirror bonus for the new user', function () {
    $referrer = User::factory()->create();
    $newUser = User::factory()->create();

    app(ReferralService::class)->applySignupReward($newUser, $referrer);

    $newUser->refresh();

    expect((string) $newUser->referred_by_user_id)->toBe((string) $referrer->id);
    expect($newUser->hasPremium())->toBeTrue();
    expect($newUser->premium_until->diffInDays(now()))->toBeLessThanOrEqual(3);
    expect($newUser->referral_mirror_bonus_until)->not->toBeNull();
    expect($newUser->referral_mirror_bonus_until->isFuture())->toBeTrue();
});

test('applySignupReward does not grant reward twice', function () {
    $referrer = User::factory()->create();
    $newUser = User::factory()->create();

    app(ReferralService::class)->applySignupReward($newUser, $referrer);
    $newUser->refresh();
    $firstPremiumUntil = $newUser->premium_until;

    app(ReferralService::class)->applySignupReward($newUser, $referrer);
    $newUser->refresh();

    expect($newUser->premium_until->eq($firstPremiumUntil))->toBeTrue();
});

test('creating a character consumes the frozen mirror bonus', function () {
    $referrer = User::factory()->create();
    $newUser = User::factory()->create();

    app(ReferralService::class)->applySignupReward($newUser, $referrer);
    $newUser->refresh();

    $result = app(CreateCharacter::class)->handle(
        user: $newUser,
        name: 'ReferredHero',
        str: 3,
        int: 3,
        vit: 2,
        agi: 2,
    );

    expect($result->isOk())->toBeTrue();

    $character = $result->getPayload();
    $character->refresh();
    $newUser->refresh();

    expect($character->mirror_access_until)->not->toBeNull();
    expect($character->mirror_access_until->isFuture())->toBeTrue();
    expect($newUser->referral_mirror_bonus_until)->toBeNull();
});

test('referrer gets 200 gems mail once a referred account reaches level 30', function () {
    $referrer = User::factory()->create();
    $referrerCharacter = Character::create([
        'user_id' => $referrer->id,
        'name' => 'ReferrerHero',
        'level' => 10,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 3, 'int' => 3, 'vit' => 2, 'agi' => 2],
    ]);

    $referredUser = User::factory()->create(['referred_by_user_id' => $referrer->id]);
    $referredCharacter = Character::create([
        'user_id' => $referredUser->id,
        'name' => 'FriendHero',
        'level' => 30,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 3, 'int' => 3, 'vit' => 2, 'agi' => 2],
    ]);

    event(new CharacterLeveledUp($referredCharacter->fresh(), 29, 30));

    $referredUser->refresh();
    expect($referredUser->referral_level30_reward_granted_at)->not->toBeNull();

    $mail = Mail::where('to_character_id', $referrerCharacter->id)->first();
    expect($mail)->not->toBeNull();
    expect($mail->attachments)->toBe([
        ['type' => 'gems', 'qty' => 200],
    ]);
});

test('level 30 referral reward is not granted twice for a second character on the same account', function () {
    $referrer = User::factory()->create();
    $referrerCharacter = Character::create([
        'user_id' => $referrer->id,
        'name' => 'ReferrerHero2',
        'level' => 10,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 3, 'int' => 3, 'vit' => 2, 'agi' => 2],
    ]);

    $referredUser = User::factory()->create(['referred_by_user_id' => $referrer->id]);

    $firstCharacter = Character::create([
        'user_id' => $referredUser->id,
        'name' => 'FriendHeroOne',
        'level' => 30,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 3, 'int' => 3, 'vit' => 2, 'agi' => 2],
    ]);

    $secondCharacter = Character::create([
        'user_id' => $referredUser->id,
        'name' => 'FriendHeroTwo',
        'level' => 30,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 3, 'int' => 3, 'vit' => 2, 'agi' => 2],
    ]);

    event(new CharacterLeveledUp($firstCharacter->fresh(), 29, 30));
    event(new CharacterLeveledUp($secondCharacter->fresh(), 29, 30));

    $mailCount = Mail::where('to_character_id', $referrerCharacter->id)->count();
    expect($mailCount)->toBe(1);
});
