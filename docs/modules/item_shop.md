# Item Shop & Premium System

## Overview
The Item Shop is a premium feature allowing players to purchase Gems via real-world transactions (powered by Stripe) and spend them on various premium packages, perks, and account upgrades.

## Features

### 1. Premium Currency (Gems)
- Gems are the secondary, premium currency of Berserk Rush.
- Players can purchase Gems using real money through a secure Stripe checkout integration.
- The administrator can manage Gem packages from the Admin Panel.

### 2. Premium Account Status
Players can spend Gems to activate or extend their **Premium Account**. Premium status provides the following perks:
- **Golden Nickname**: The player's name is highlighted in gold in the global and guild chats, making them stand out.
- **Bonus EXP & Gold**: Premium users receive a +20% bonus to both Experience and Gold gained from all PvE battles.
- **Expanded Backpack Capacity**: Inventory size is doubled from 32 to 64 slots (`Character::getBackpackCapacity()`).
- **Extra Equipment Loadout Sets**: Access to 2 additional saved equipment loadout sets (Set II and Set III) in the character profile.

### 3. Stripe Webhooks Integration
- Webhooks are used to asynchronously verify successful payments and credit Gems to the player's account.
- The `StripeWebhookController` listens for the `checkout.session.completed` event.
- Secure processing ensures players only receive Gems after the payment is fully authorized.

### 5. Zwoje Użytkowe i Resety
Zamiast bezpośredniego natychmiastowego resetu konta, gracze mogą kupować w Sklepie Premium fizyczne Zwoje Użytkowe (`consumable`), które trafiają do ekwipunku aktywnej postaci:
- **Zwój Resetu Umiejętności (50 Gemów):** Pozwala zresetować umiejętności bojowe aktywnej postaci i zwrócić zainwestowane punkty umiejętności (`skill_points`).
- **Zwój Resetu Atrybutów (50 Gemów):** Pozwala zresetować przydzielone punkty atrybutów (STR, INT, VIT, AGI) do 0 dla aktywnej postaci i zwraca całą pulę punktów postaci (`character_points` = 10 + (poziom - 1) * 3) do ponownego rozdania.
- **Zwój Pełnego Resetu (90 Gemów):** Pozwala zresetować zarówno atrybuty, jak i umiejętności bojowe aktywnej postaci za jednym razem.
- **Zwój Areny Walki (30 Gemów):** Przywraca 1 wykorzystaną próbę na Arenie Walk w danym dniu dla aktywnej postaci.

## Technical Implementation
- **Livewire Components**: Managed via `ItemShopComponent` for the user interface (obsługuje zakupy Gemów, VIP, avatarów oraz zakupy zwojów przez `buyScroll()`), and `Admin\ItemShopPackages` for backend management.
- **Consumables Logic**: `ConsumeItemAction` realizuje efekt użycia przedmiotu po kliknięciu w ekwipunku postaci na podstawie pola `effect` w `base_stats` przedmiotu.
- **Database Models**: 
  - `ItemShopPackage` tracks available packages (price, gems, name).
  - `User` model tracks `gems` balance and `premium_until` datetime.
  - `Character` model updates `attributes` array and recalculates `character_points` or `skill_points` during scroll consumption.
- **Combat Integration**: `RewardMultiplierService` checks `$user->hasPremium()` and applies the 1.2x multipliers to base rewards.
