---
# Copilot AI Coding Agent Instructions for Berserk Rush

Laravel 11 browser-based RPG following Domain-Driven Design with strict ULID usage and comprehensive audit trails.

## Core Architecture

**Domain-Driven Structure** organized by feature:
- `app/Application/` — Business services (EncounterService, DropService, RewardService)
- `app/Infrastructure/Persistence/` — Eloquent models with ULID primary keys
- `app/Infrastructure/RNG/` — Testable randomization interfaces
- `app/Livewire/` — UI components delegating to Application layer

## Critical Patterns

### ULID Everything
```php
// All models use ULIDs - NEVER auto-increment
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Model extends Eloquent {
    use HasUlids;
    public $incrementing = false;
    protected $keyType = 'string';
}

// Manual ULID generation when needed
$model->id = Str::ulid();
```

### Result Pattern for All Services
```php
// Every application service returns Result<T>
public function handle(...): Result {
    try {
        return Result::ok($data);
    } catch (\Exception $e) {
        Log::error('Service failed', ['error' => $e->getMessage()]);
        return Result::error('ERROR_CODE', 'User message', $context);
    }
}
```

### Database Transactions + Comprehensive Logging
```php
// Pattern in EncounterService, DropService, RewardService
return DB::transaction(function () use ($params) {
    Log::info('Operation started', $context);
    // ... business logic with intermediate logging
    Log::info('Operation completed', $result);
    return $result;
});
```

### Audit Trail Architecture
**Every transaction creates ledger entries**:
- `CurrencyLedger`: tracks gold/gems with running `balance_after`
- `ItemLedger`: tracks item movements with `quantity_change`
- All use `idempotency_key` to prevent duplicate operations

```php
// Currency ledger with running balance
$currentBalance = CurrencyLedger::where('character_id', $characterId)
    ->where('currency_type', 'gold')
    ->orderBy('created_at', 'desc')
    ->value('balance_after') ?? 0;

CurrencyLedger::create([
    'id' => Str::ulid(),
    'character_id' => $characterId,
    'currency_type' => 'gold',
    'amount' => $amount,
    'balance_after' => $currentBalance + $amount,
    'idempotency_key' => $key,
    // ...
]);
```

## Database Architecture

**PostgreSQL with JSONB**: All flexible data uses `jsonb` with array casting
```php
protected $casts = [
    'attributes' => 'array',      // Character stats: ['str' => 10, 'int' => 8, ...]
    'base_stats' => 'array',      // Item/Monster stats
    'combat_data' => 'array',     // Encounter metadata
    'result' => 'array'           // Drop/combat results
];
```

**ULID Consistency**: Always string, never bigint foreign keys
```php
// Migration pattern
Schema::create('table', function (Blueprint $table) {
    $table->string('id', 26)->primary(); // ULID
    $table->string('character_id', 26);  // FK to characters
    $table->foreign('character_id')->references('id')->on('characters');
});
```

## Service Dependencies

**Critical Service Bindings** in `AppServiceProvider`:
```php
public function register(): void {
    $this->app->bind(RandomProvider::class, DefaultRandomProvider::class);
}
```

## Livewire Patterns

**SPA Navigation**: Always use `navigate: true`
```php
$this->redirect(route('name', $params), navigate: true);
```

**State Management**: Clean transitions with reset methods
```php
public function resetEncounter(): void {
    $this->currentEncounterId = null;
    $this->player = [];
    $this->enemy = [];
    // ... reset all state properties
}
```

## Game Logic Architecture

**Weighted Random Selection**: Use `WeightedPicker` for loot/encounters
**Character Progression**: Attributes in JSONB, computed stats cached
**Combat Flow**: Encounter → EncounterService → DropService → AuditLogs
**Idempotency**: All economy operations use unique keys like `"encounter:{$id}:drop"`

## Testing Patterns

**Pest Framework**: `tests/Unit/` for services, `tests/Feature/` for Livewire
**Deterministic RNG**: `DeterministicRandomProvider` for reproducible tests
**Factory Pattern**: ULID-aware factories for all models

## Developer Commands

```bash
# Database reset with seeders  
php artisan migrate:fresh --seed

# Debug schema
php artisan tinker
Schema::getColumnListing('characters')

# Development server
composer run dev
```

## Common Gotchas

- **ULID Type Consistency**: Never mix string/bigint in foreign keys
- **Transaction Isolation**: Always wrap multi-model operations in `DB::transaction()`
- **Balance Calculations**: Use running totals in ledgers, not model attributes
- **Livewire State**: Handle `livewire:navigated` for proper JS initialization
- **Idempotency**: Check existing records before creating financial entries

## Architecture Examples

**Service Pattern**:
```php
$result = app(EncounterService::class)->start($character, $map);
if ($result->isError()) {
    Log::error('Encounter failed', $result->getContext());
    return;
}
$encounter = $result->getPayload();
```

**Livewire Component**:
```php
public function startBattle(): void {
    $result = app(EncounterService::class)->start($this->character, $this->map);
    if ($result->isError()) {
        $this->addError('battle', $result->getErrorMessage());
        return;
    }
    $this->currentEncounterId = $result->getPayload()->id;
    $this->dispatch('battle-started');
}
```

---
