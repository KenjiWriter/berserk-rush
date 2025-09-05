---
applyTo: '**'
---
# Instruction for GitHub Copilot
*(Project context, architecture & coding conventions)*

## Project Overview
We are building a **browser-based multiplayer RPG** in the spirit of *Shakes & Fidget* mixed with *Metin2-style item upgrades*.  
Focus: **micromanagement, economy, crafting, emergent classes** (no class at start — class emerges from actions).

Core features:
- **Turn-based idle combat** on level-locked maps (e.g. 0–10, 10–30, 30–50).
- **Items** with random stats/rarity and upgrades (+0 → +9, success/fail).
- **Crafting** (recipes, materials, outputs).
- **Player-driven economy** via **market/auction house**.
- **No mission timers**: loop = fight → loot → trade → upgrade → craft.

---

## Technology Stack
- **Laravel 12** (PHP 8.3+), **Starter:** Laravel **Breeze + Livewire**.
- **PostgreSQL 17** (primary DB) + **pgAdmin 4**.
  - `jsonb` for stats/flags/rolls; **ULIDs** as primary keys; partial & specialized indexes.
- **Redis**: cache, queues (Horizon), leaderboards (Sorted Sets), locks.
- **Realtime**: Laravel WebSockets / Pusher / Reverb (for chat/market updates).
- **Testing**: **Pest** (on top of PHPUnit).

---

## High-level Data Model
- **Characters**: level, XP, currencies, attributes, proficiencies, skills (emergent).
- **Items**: `item_templates` (static) & `item_instances` (rolled stats, rarity, upgrade_level).
- **Combat**: `maps`, `monsters`, `encounters`, `turns`, `loot_tables`.
- **Crafting/Upgrades**: recipes, ingredients, upgrade_rules, attempts (audit).
- **Economy**: market_listings, purchases/trades, mail delivery, currency_ledger, item_ledger.

---

## Design Patterns & Architecture (Generate code that follows these rules)

### Domain & Modules
- Organize by **feature modules** (not layers-only):
  - `App/Domain/*` (Entities, ValueObjects, Policies)
  - `App/Application/*` (Actions/Commands/Services, DTOs)
  - `App/Infrastructure/*` (Repositories, Persistence, External)
  - `App/Livewire/*` (UI components)
- Keep **controllers/components thin**; logic in **Services/Actions**.

### Core Patterns
1. **Action/Service (Transaction Script where needed)**
   - Example: `App\Application\Combat\StartEncounter`, `PlayTurn`, `FinishEncounter`.
   - Every Action is **small, single-responsibility**, callable, testable.
   - Economy-changing Actions run in **DB transactions** and accept an `idempotencyKey`.

2. **Strategy** (pluggable formulas)
   - Damage formulas, loot weighting, upgrade success calculation, pricing rules.
   - Example interface: `DamageStrategy::calculate($attacker, $defender, Context $ctx): DamageResult`.

3. **State** (Encounter state machine)
   - Encounter states: `Ongoing`, `Won`, `Lost`.
   - Each state knows which transitions are legal (no “finish” twice).

4. **Specification/Query Object** (complex filtering)
   - Market filters, loot rules, map eligibility.
   - Example: `MarketListingSpecifications::active()->byItemSlot('weapon')->priceBetween(100, 5000)`.

5. **Factory + Builder**
   - **ItemFactory** to create rolled `item_instances` from `item_templates`.
   - **EncounterFactory** to pick monsters weighted by map loot table.
   - **RecipeBuilder** to assemble crafting outputs.

6. **Domain Events + Observer**
   - Fire events: `ItemUpgraded`, `EncounterFinished`, `ItemDropped`, `PurchaseCompleted`.
   - Handle side-effects (notifications, mail delivery, leaderboards) in **listeners**.
   - Use **Outbox** table if we add external integrations later.

7. **Value Objects**
   - `Money` (currency + amount), `Chance` (0..1), `LevelRange`, `Rarity`, `UpgradeLevel`, `StatsBag`.
   - Immutable; validate invariants on creation.

8. **Result/Either**
   - Actions return `Result::ok($payload)` or `Result::error($code, $message, $context)`.
   - No exceptions for business-as-usual failures (e.g. “not enough gold”).

9. **Repository (pragmatic)**
   - Use Eloquent directly for simple cases.
   - Introduce Repositories for aggregates with complex invariants (Inventory, Market).
   - Repos expose intentful methods: `reserveForMarket(ItemInstanceId $id, CharacterId $seller)`.

10. **Policy/Authorization**
    - Enforce ownership/inventory rules with Policies + inline guards in Actions.
    - Example: `canUpgrade(ItemInstance $item, Character $who)` checks bind/ownership.

11. **Idempotency & Concurrency**
    - `idempotency_key` stored in ledgers; reject duplicate effects.
    - Use Postgres `SELECT ... FOR UPDATE` + optimistic `version` column for `characters` and `item_instances`.

12. **CQRS-lite (Optional)**
    - Separate **read models** for UI (denormalized queries) from write services.
    - Keep write side clean with Actions/Services.

### Folder Layout (enforce this)
app/
Domain/
Combat/ (Entities, ValueObjects, Events)
Items/
Crafting/
Economy/
Application/
Combat/StartEncounter.php
Combat/PlayTurn.php
Items/UpgradeItem.php
Crafting/CraftItem.php
Market/CreateListing.php
Shared/DTOs/...
Infrastructure/
Persistence/ (Eloquent models, Repositories)
RNG/ (RandomProvider, seeded RNG for tests)
Notifications/
Livewire/
Combat/Adventure.php (+ view)
Inventory/...
Market/...

### Character Attributes & Stats

Each character has **four primary attributes**:

- **Strength (STR)**  
  - Increases physical attack damage.  
  - Increases survivability slightly (bonus HP).  

- **Intelligence (INT)**  
  - Increases magical attack damage.  
  - Increases maximum mana pool.  

- **Vitality (VIT)**  
  - Increases maximum health points (HP).  
  - Reduces damage taken slightly.  

- **Agility (AGI)**  
  - Increases ranged weapon damage.  
  - Increases chance to dodge attacks.  
  - Increases critical hit chance.  

**Character creation rules**:  
- New accounts may have up to **4 characters**.  
- At character creation, players choose a **nickname** and distribute **10 starting attribute points** across the four stats.  
- Emergent class system: class identity comes from how players invest and develop these attributes over time.

### Interfaces to aid testing & AI consistency
- `RandomProvider` (production: `mt_rand`/`random_int`; test: deterministic seed).
- `Clock` (production: `now()`, test: frozen time).
- `DamageStrategy`, `LootStrategy`, `UpgradeStrategy` (inject via container).

---

## Coding Conventions
- **ULID** for IDs, **jsonb** for flexible data, DB transactions around economy changes.
- DTOs or typed arrays between UI ↔ Services.
- Names: Actions use **imperative verbs**: `UpgradeItem`, `CreateListing`, `BuyListing`.
- Events use **past tense**: `ItemUpgraded`, `EncounterFinished`.
- Keep functions short; extract private helpers for formulas.
- Prefer **early returns** with `Result` over nested `if`s.

---

## Testing (Pest)
- Structure: `tests/Unit/*` (pure domain/services), `tests/Feature/*` (HTTP/Livewire flows).
- Use fakes for `RandomProvider` and `Clock`.
- Golden-path + edge-cases for each Action:
  - success, business failure (not enough gold), idempotency repeat, concurrency collision.
- RNG/loot distribution: statistical sanity tests on large samples.
- Sample Pest style:
```php
it('upgrades item from +0 to +1 on success', function () {
    $rng = new DeterministicRandom([0.01]); // force success
    $svc = app()->make(UpgradeItem::class, ['rng' => $rng]);
    $result = $svc->handle($characterId, $itemId, idempotencyKey: 'abc');

    expect($result->isOk())->toBeTrue()
        ->and($result->payload->newLevel)->toBe(1);
});

Realtime & Background Work

Use queues for: loot generation at scale, market settlement, mail delivery.

Non-critical side effects go to domain listeners (queueable).

Leaderboards in Redis ZSET, rebuild periodically from snapshots.

Database Guidelines (PostgreSQL 17)

Indices:

FKs + (character_id, created_at) on hot tables.

Partial index on market_listings(status='active').

GIN on item_instances.roll_stats and item_templates.base_stats when filtering by JSON.

Locking:

FOR UPDATE on inventory & currency rows during economic operations.

Migrations: prefer jsonb defaults via DB::raw("'{}'::jsonb").

Current Milestone

Migrations & seeders (maps, monsters, items, loot tables).

Implement Actions: StartEncounter, PlayTurn, FinishEncounter, RollLoot, UpgradeItem, CreateListing, BuyListing.

Livewire Adventure flow (map → fight → loot).

Inventory UI (equip/unequip) + Market basics.

Pest tests for Actions (success/failure/idempotency/concurrency).

Copilot: generate code that strictly follows these patterns, names, and structure.