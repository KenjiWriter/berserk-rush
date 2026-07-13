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

### 3. Stripe Webhooks Integration
- Webhooks are used to asynchronously verify successful payments and credit Gems to the player's account.
- The `StripeWebhookController` listens for the `checkout.session.completed` event.
- Secure processing ensures players only receive Gems after the payment is fully authorized.

## Technical Implementation
- **Livewire Components**: Managed via `ItemShopComponent` for the user interface, and `Admin\ItemShopPackages` for backend management.
- **Database Models**: 
  - `ItemShopPackage` tracks available packages (price, gems, name).
  - `User` model tracks `gems` balance and `premium_until` datetime.
- **Combat Integration**: `RewardMultiplierService` checks `$user->hasPremium()` and applies the 1.2x multipliers to base rewards.
