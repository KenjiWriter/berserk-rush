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

### 6. System Reflinków (Poleceń)
Każdy zarejestrowany gracz posiada unikalny `referral_code` (generowany automatycznie
przy tworzeniu konta, `User::booted()`) oraz link postaci `{{route('register')}}?ref=KOD`,
prezentowany w sekcji "Reflinki" Sklepu Premium (`ItemShopComponent`).

- **Przechwytywanie kodu**: parametr `?ref=` jest zapisywany do sesji przy wejściu
  na stronę główną (`Homepage::mount()`), stronę rejestracji (`Register::mount()`)
  lub przekierowanie do logowania społecznościowego
  (`SocialLoginController::redirect()`), dzięki czemu działa niezależnie od tego,
  czy nowy gracz zakłada konto e-mail/hasłem, czy przez Google/Facebook.
- **Nagroda za rejestrację** (`ReferralService::applySignupReward()`): nowe konto
  założone z poprawnym kodem od razu otrzymuje **3 dni Konta VIP** oraz
  **3 dni darmowego dostępu do Lustra** (Lustro jest per-postać, więc bonus jest
  "zamrożony" na koncie w `referral_mirror_bonus_until` i przypisywany do
  pierwszej utworzonej postaci przez `ReferralService::grantPendingMirrorBonus()`,
  wołane z `CreateCharacter`).
- **Nagroda za osiągnięcie 30 poziomu przez znajomego**
  (`ReferralService::grantLevel30ReferralReward()`, wołane z listenera zdarzenia
  `CharacterLeveledUp` w `AppServiceProvider`): referrer otrzymuje **200 Gemów**
  wysłanych na pocztę w grze (`SendMailAction`, załącznik `type: 'gems'`) do
  swojej najaktywniej używanej postaci. Nagroda jest **jednorazowa na całe konto
  poleconego gracza** — znacznik `users.referral_level30_reward_granted_at` na
  koncie poleconego blokuje ponowną wypłatę, nawet jeśli kolejna postać na tym
  samym koncie również osiągnie 30 poziom.

## Technical Implementation
- **Livewire Components**: Managed via `ItemShopComponent` for the user interface (obsługuje zakupy Gemów, VIP, avatarów oraz zakupy zwojów przez `buyScroll()`), and `Admin\ItemShopPackages` for backend management.
- **Consumables Logic**: `ConsumeItemAction` realizuje efekt użycia przedmiotu po kliknięciu w ekwipunku postaci na podstawie pola `effect` w `base_stats` przedmiotu.
- **Database Models**: 
  - `ItemShopPackage` tracks available packages (price, gems, name).
  - `User` model tracks `gems` balance and `premium_until` datetime.
  - `Character` model updates `attributes` array and recalculates `character_points` or `skill_points` during scroll consumption.
- **Combat Integration**: `RewardMultiplierService` checks `$user->hasPremium()` and applies the 1.2x multipliers to base rewards.

## Legal & Terms of Purchase (Postanowienia Prawne)
- **Digital Content Delivery**: Real-world currency purchases of Gems and Premium perks represent digital content delivered immediately upon successful payment confirmation via Stripe webhooks.
- **Waiver of Right of Withdrawal**: Under EU Consumer Rights legislation (Art. 38 point 13 of the Polish Act on Consumer Rights), players explicitly request instant fulfillment upon purchase and acknowledge the loss of their 14-day right of withdrawal once Gems are credited.
- **Non-Refundability & License Nature**: Gems and Premium account status carry no real-world monetary value, cannot be converted into real currency, and represent a limited, non-exclusive license for in-game use. Purchases are non-refundable after immediate delivery.
- **Account Suspension Policy**: In the event of account suspension or termination due to violation of Game Rules (e.g. cheating, botting, Real Money Trading / RMT), all virtual currency, active Premium status, and items are forfeited without compensation or right of refund.
- **Payment Processing**: All transaction sensitive data (card details, authentication) is handled exclusively by Stripe Payments Europe, Ltd. The game platform stores only transaction identifiers, timestamps, and order statuses.

