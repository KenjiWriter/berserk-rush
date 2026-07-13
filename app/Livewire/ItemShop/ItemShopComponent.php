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
    public string $activeTab = 'gems'; // 'gems' lub 'premium'

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

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

    public function buyGems(string $packageId)
    {
        $user = Auth::user();
        if (!$user) return;

        $package = ItemShopPackage::find($packageId);
        if (!$package || !$package->is_active) {
            $this->dispatch('notify', message: 'Ten pakiet jest niedostępny.', type: 'error');
            return;
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

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
            'success_url' => route('itemshop') . '?success=1',
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

        return view('livewire.item-shop.item-shop-component', [
            'packages' => $packages,
            'premiumAvatars' => $premiumAvatars,
            'user' => Auth::user(),
        ])->layout('components.layouts.app');
    }
}
