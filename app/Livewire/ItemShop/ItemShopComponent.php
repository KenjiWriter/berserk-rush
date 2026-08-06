<?php

namespace App\Livewire\ItemShop;

use Livewire\Component;
use App\Models\ItemShopPackage;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\File;

class ItemShopComponent extends Component
{
    public function buyPremium(int $days, int $gemCost)
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        if ($user->gems < $gemCost) {
            $this->dispatch('not-enough-gems');
            return;
        }

        // Pobierzemy gemy i przedłużymy premium
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $days, $gemCost) {
            $user->gems -= $gemCost;
            
            $now = now();
            if ($user->premium_until && $user->premium_until->isFuture()) {
                $user->premium_until = $user->premium_until->addDays($days);
            } else {
                $user->premium_until = $now->addDays($days);
            }
            
            $user->save();
        });

        $this->dispatch('notify', message: "Pomyślnie zakupiono Konto Premium na $days dni!", type: 'success');
    }

    public function buyAvatar(string $avatarFilename)
    {
        $user = Auth::user();
        if (!$user) return;

        $cost = 150;

        $unlocked = $user->unlocked_avatars ?? [];
        if (in_array($avatarFilename, $unlocked)) {
            $this->dispatch('notify', message: 'Już posiadasz ten avatar!', type: 'error');
            return;
        }

        if ($user->gems < $cost) {
            $this->dispatch('not-enough-gems');
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $cost, $avatarFilename, $unlocked) {
            $user->gems -= $cost;
            $unlocked[] = $avatarFilename;
            $user->unlocked_avatars = array_values(array_unique($unlocked));
            $user->save();
        });

        $this->dispatch('notify', message: 'Pomyślnie zakupiono avatar!', type: 'success');
    }

    public function mount()
    {
        if (request()->has('success') && request()->has('session_id')) {
            $sessionId = request('session_id');
            $this->processSessionGems($sessionId);
        }
    }

    protected function processSessionGems(string $sessionId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        try {
            $session = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            return;
        }

        if ($session->payment_status !== 'paid') {
            return;
        }

        $userId = $session->metadata->user_id ?? $session->client_reference_id ?? null;
        $gemAmount = (int) ($session->metadata->gem_amount ?? 0);

        if (!$userId || $gemAmount <= 0) {
            return;
        }

        $cacheKey = 'stripe_processed_session_' . $session->id;
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $gemAmount, $cacheKey) {
                $user->gems += $gemAmount;
                $user->save();
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(30));
            });

            $this->dispatch('notify', message: "Dziękujemy! Przypisano $gemAmount Gemów do Twojego konta.", type: 'success');
        }
    }

    public function buyGems(string $packageId)
    {
        $user = Auth::user();
        if (!$user) return;

        $package = ItemShopPackage::find($packageId);
        if (!$package || !$package->is_active) {
            $this->dispatch('notify', message: 'Ten pakiet jest niedostępny.', type: 'error');
            return;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $checkout_session = Session::create([
            'payment_method_types' => ['card', 'blik'], // BLIK is popular in PL
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($package->currency),
                    'product_data' => [
                        'name' => $package->name . ' (' . $package->gem_amount . ' Gemów)',
                    ],
                    'unit_amount' => $package->price_in_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('itemshop') . '?success=1&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('itemshop') . '?cancel=1',
            'client_reference_id' => $user->id,
            'metadata' => [
                'package_id' => $package->id,
                'gem_amount' => $package->gem_amount,
                'user_id' => $user->id,
            ]
        ]);

        return redirect($checkout_session->url);
    }

    public function buyScroll(string $templateId, int $gemCost)
    {
        $user = Auth::user();
        if (!$user) return;

        if ($user->gems < $gemCost) {
            $this->dispatch('not-enough-gems');
            return;
        }

        $activeCharacterId = session('active_character');
        $character = $activeCharacterId 
            ? $user->characters()->where('id', $activeCharacterId)->first() 
            : $user->characters()->first();

        if (!$character) {
            $this->dispatch('notify', message: 'Musisz posiadać utworzoną postać, aby zakupić ten przedmiot.', type: 'error');
            return;
        }

        if ($character->isBackpackFull()) {
            $this->dispatch('notify', message: 'Plecak aktywnej postaci jest pełny!', type: 'error');
            return;
        }

        $template = \App\Infrastructure\Persistence\ItemTemplate::find($templateId);
        if (!$template) {
            $template = \App\Infrastructure\Persistence\ItemTemplate::where('name', $templateId)->first();
        }

        if (!$template) {
            $this->dispatch('notify', message: 'Nie odnaleziono szablonu przedmiotu.', type: 'error');
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $character, $template, $gemCost) {
            $user->gems -= $gemCost;
            $user->save();

            $existing = \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $template->id)
                ->where('location', 'inventory')
                ->first();

            if ($existing) {
                $existing->increment('stack_size');
                $itemInstance = $existing;
            } else {
                $itemInstance = \App\Infrastructure\Persistence\ItemInstance::create([
                    'id' => (string) \Illuminate\Support\Str::ulid(),
                    'template_id' => $template->id,
                    'owner_character_id' => $character->id,
                    'location' => 'inventory',
                    'stack_size' => 1,
                    'rarity' => 'common',
                    'upgrade_level' => 0,
                ]);
            }

            \App\Infrastructure\Persistence\ItemLedger::create([
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $itemInstance->id,
                'action' => 'buy',
                'ref_type' => 'item_shop',
                'quantity_change' => 1,
                'idempotency_key' => 'shop_' . \Illuminate\Support\Str::ulid(),
            ]);
        });

        $this->dispatch('notify', message: "Pomyślnie zakupiono {$template->name}! Przedmiot dodano do plecaka postaci {$character->name}. Możesz go tam użyć w dowolnym momencie.", type: 'success');
    }

    public function resetSkills()
    {
        $user = Auth::user();
        if (!$user) return;

        $cost = 50;

        if ($user->gems < $cost) {
            $this->dispatch('not-enough-gems');
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $cost) {
            $user->gems -= $cost;
            $user->save();

            $characters = $user->characters;
            foreach ($characters as $character) {
                $spentPoints = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
                    ->where('character_id', $character->id)
                    ->get()
                    ->sum(function($cs) {
                        return $cs->skill->unlock_cost + ($cs->level - 1);
                    });
                
                \App\Infrastructure\Persistence\CharacterCombatSkill::where('character_id', $character->id)->delete();
                
                if ($spentPoints > 0) {
                    $character->skill_points += $spentPoints;
                    $character->save();
                }
            }
        });

        $this->dispatch('notify', message: 'Zresetowano umiejętności wszystkich postaci pomyślnie!', type: 'success');
    }

    public function resetAttributes()
    {
        $user = Auth::user();
        if (!$user) return;

        $cost = 50;

        if ($user->gems < $cost) {
            $this->dispatch('not-enough-gems');
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $cost) {
            $user->gems -= $cost;
            $user->save();

            $characters = $user->characters;
            foreach ($characters as $character) {
                $character->attributes = [
                    'str' => 0,
                    'int' => 0,
                    'vit' => 0,
                    'agi' => 0,
                ];
                $character->character_points = 10 + max(0, ($character->level - 1) * 3);
                $character->clearStatsCache();
                $character->save();
            }
        });

        $this->dispatch('notify', message: 'Zresetowano atrybuty wszystkich postaci pomyślnie!', type: 'success');
    }

    public function render()
    {
        $packages = ItemShopPackage::where('is_active', true)->orderBy('price_in_cents', 'asc')->get();

        $premiumAvatars = [];
        $avatarPath = public_path('img/avatars/premium');
        if (File::exists($avatarPath)) {
            $files = File::files($avatarPath);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'webp'])) {
                    $premiumAvatars[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        $user = Auth::user();

        if (empty($user->referral_code)) {
            do {
                $code = strtoupper(\Illuminate\Support\Str::random(8));
            } while (\App\Models\User::where('referral_code', $code)->exists());

            $user->update(['referral_code' => $code]);
        }

        return view('livewire.item-shop.item-shop-component', [
            'packages' => $packages,
            'premiumAvatars' => $premiumAvatars,
            'user' => $user,
            'referralLink' => $user->getReferralLink(),
            'referralCode' => $user->referral_code,
            'referredCount' => $user->referredUsers()->count(),
            'rewardedCount' => $user->referredUsers()->whereNotNull('referral_level30_reward_granted_at')->count(),
        ])->layout('components.layouts.app');
    }
}
