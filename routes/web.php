<?php

use App\Livewire\City\Hub;
use App\Livewire\Homepage;
use App\Livewire\City\Witch;
use App\Livewire\Auth\Register;
use App\Livewire\City\Adventure;
use App\Livewire\Characters\Show;
use App\Livewire\City\Armorsmith;
use App\Livewire\City\Weaponsmith;
use App\Livewire\Characters\Create;
use App\Livewire\Adventure\MapStub;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Character;

Route::get('/', Homepage::class)->name('homepage');
Route::get('/register', Register::class)->name('register');

// Stripe Webhook (Unauthenticated, CSRF exempt)
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/itemshop', \App\Livewire\ItemShop\ItemShopComponent::class)->name('itemshop');
    Route::get('/characters/create', Create::class)->name('characters.create');

    // Character selection redirect
    Route::get('/characters/{character}/play', function (Character $character) {
        if (auth()->user()->id !== $character->user_id) {
            abort(403);
        }

        session(['active_character' => $character->id]);
        return redirect()->route('city.hub', $character);
    })->name('characters.play');

    // City Hub and Buildings
    Route::prefix('play/{character}')->name('city.')->group(function () {
        Route::get('/', Hub::class)->name('hub');
        Route::get('/profile', \App\Livewire\City\Profile::class)->name('profile');
        Route::get('/armorsmith', Armorsmith::class)->name('armorsmith');
        Route::get('/weaponsmith', Weaponsmith::class)->name('weaponsmith');
        Route::get('/witch', Witch::class)->name('witch');
        Route::get('/wizard', \App\Livewire\City\Wizard::class)->name('wizard');
        Route::get('/market', \App\Livewire\Economy\MarketComponent::class)->name('market');
        Route::get('/mailbox', \App\Livewire\Mail\MailboxComponent::class)->name('mailbox');
        Route::get('/guild', \App\Livewire\City\GuildComponent::class)->name('guild');
        Route::get('/adventure', Adventure::class)->name('adventure');
        Route::get('/dungeon/{dungeon}', \App\Livewire\City\DungeonRun::class)->name('dungeon.run');
        Route::get('/pets', \App\Livewire\City\PetsComponent::class)->name('pets');
        Route::get('/arena', \App\Livewire\City\Arena::class)->name('arena');
        Route::get('/arena/combat/pvp/{pvpId}', \App\Livewire\City\ArenaCombat::class)->name('arena.combat.pvp');
        Route::get('/arena/combat/gvg/{gvgId}', \App\Livewire\City\ArenaCombat::class)->name('arena.combat.gvg');
        Route::get('/gladiator', \App\Livewire\City\GladiatorShop::class)->name('gladiator');
        Route::get('/quests', \App\Livewire\City\Quests::class)->name('quests');
    });

    // Adventure map routes
    Route::prefix('play/{character}/adventure')->name('adventure.')->group(function () {
        Route::get('/{map}', MapStub::class)->name('map');
    });
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/maps', \App\Livewire\Admin\Maps::class)->name('maps');
    Route::get('/monsters', \App\Livewire\Admin\Monsters::class)->name('monsters');
    Route::get('/item-templates', \App\Livewire\Admin\ItemTemplates::class)->name('item-templates');
    Route::get('/item-shop-packages', \App\Livewire\Admin\ItemShopPackages::class)->name('item-shop-packages');
    Route::get('/merchant-items', \App\Livewire\Admin\MerchantItems::class)->name('merchant-items');
    Route::get('/loot-tables', \App\Livewire\Admin\LootTables::class)->name('loot-tables');
    Route::get('/item-recipes', \App\Livewire\Admin\ItemRecipes::class)->name('item-recipes');
    Route::get('/upgrade-rules', \App\Livewire\Admin\UpgradeRules::class)->name('upgrade-rules');
    Route::get('/dungeons', \App\Livewire\Admin\Dungeons::class)->name('dungeons');
    Route::get('/pet-templates', \App\Livewire\Admin\PetTemplates::class)->name('pet-templates');
    Route::get('/quests', \App\Livewire\Admin\Quests::class)->name('quests');
    Route::get('/titles', \App\Livewire\Admin\Titles::class)->name('titles');
    Route::get('/achievements', \App\Livewire\Admin\Achievements::class)->name('achievements');
    Route::get('/gallery', \App\Livewire\Admin\Gallery::class)->name('gallery');
});

Route::get('/assets/items/{filename}', function ($filename) {
    $path = storage_path('app/assets/items/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
})->name('assets.items');

Route::get('/assets/monsters/avatars/{filename}', function ($filename) {
    $path = storage_path('app/assets/monsters/avatars/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
})->name('assets.monsters.avatars');

require __DIR__ . '/auth.php';
