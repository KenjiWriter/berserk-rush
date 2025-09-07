---
# Copilot AI Coding Agent Instructions for Berserk Rush

## Project Architecture & Patterns

- **Domain-Driven, Modular Structure**: Organize by feature (not just layers). Key folders:
    - `app/Application/` — Actions/Services (business logic, e.g. `StartEncounter`, `UpgradeItem`)
    - `app/Domain/` — Entities, ValueObjects, Events (future-proofed)
    - `app/Infrastructure/Persistence/` — Eloquent models, DB logic
    - `app/Livewire/` — UI components (thin, delegate to Application layer)

- **Result Pattern**: All business actions return `Result::ok($payload)` or `Result::error($code, $msg, $ctx)`. No exceptions for business logic failures.

- **Action/Service Pattern**: Each Action (e.g. `CreateCharacter`, `UpgradeItem`) is single-responsibility, testable, and called via `app(ActionClass::class)->handle(...)`.

- **Transactions**: Economy-changing actions use DB transactions (`DB::transaction(...)`).

- **Livewire SPA Navigation**: Use `navigate: true` for in-app transitions. JS initialization must handle `livewire:navigated` for correct UI state.

- **Attributes & Stats**: Character attributes (STR, INT, VIT, AGI) are stored as JSONB (`attributes` column). Always extract as array (`$character->attributes['str']`).

- **Leveling & Points**: On level up, grant 3 `character_points` (see `LevelUpService`). XP required: `LevelUpService::xpToNext($level)`.

- **Testing**: Use Pest. Place pure domain/service tests in `tests/Unit/`, Livewire/HTTP flows in `tests/Feature/`. Use factories for models.

## Developer Workflow

- **Build/Dev**: `composer run dev` (starts Vite, queue, etc.)
- **Test**: `composer run test` or `php artisan test`
- **DB Reset**: `php artisan migrate:fresh --seed`

## Project-Specific Conventions

- **ULIDs** for all primary keys
- **JSONB** for flexible columns (attributes, proficiencies)
- **Optimistic Locking**: Use `version` column for concurrency
- **Events**: Past tense (`CharacterLeveledUp`, `ItemUpgraded`)
- **Actions**: Imperative verbs (`CreateCharacter`, `UpgradeItem`)
- **UI**: Medieval theme (amber/gold, parchment, Cinzel font)
- **Market, Crafting, Combat**: All follow Action/Result/DTO pattern

## Integration Points

- **PostgreSQL 17** (primary DB)
- **Redis** (cache, queues, leaderboards)
- **Livewire** (SPA UI)
- **Pest** (testing)

## Examples

- **Action**:
    ```php
    $result = app(UpgradeItem::class)->handle($characterId, $itemId, idempotencyKey: 'abc');
    if ($result->isError()) { /* ... */ }
    ```
- **Livewire Component**:
    ```php
    public function handleAction(): void {
            $result = app(SomeAction::class)->handle(...);
            if ($result->isError()) {
                    $this->addError('form', $result->getErrorMessage());
                    return;
            }
            $this->redirect(route('...'), navigate: true);
    }
    ```

---
