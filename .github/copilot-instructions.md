# GitHub Copilot Instructions for Berserk Rush

## Project Overview
A **browser-based multiplayer RPG** built with Laravel 12 + Livewire + PostgreSQL. Medieval fantasy theme inspired by *Shakes & Fidget* and *Metin2*. Focus on micromanagement, economy, crafting, and emergent character classes.

## Architecture & Domain Design

### Domain-Driven Structure (Strictly Follow)
```
app/
├── Application/         # Actions/Services (business logic)
│   ├── Characters/     # CreateCharacter.php
│   └── Shared/        # Result.php, DTOs
├── Infrastructure/     # Persistence, External APIs
│   └── Persistence/   # Eloquent models (Character.php)
├── Livewire/          # UI Components
└── Domain/            # (Future: Entities, ValueObjects, Events)
```

### Core Patterns (Always Use)

**1. Result Pattern** - No exceptions for business failures:
```php
return Result::ok($payload);
return Result::error('CODE', 'Message', $context);
```

**2. Action/Service Pattern** - Single responsibility, testable:
```php
class CreateCharacter {
    public function handle(User $user, string $name, ...): Result
}
```

**3. Transaction Boundaries** - Economy operations in DB transactions:
```php
return DB::transaction(function () use (...) {
    // Double-check constraints inside transaction
    // Create/update entities
    return Result::ok($entity);
});
```

## Database Conventions

### PostgreSQL 17 Specifics
- **ULIDs** as primary keys: `$table->ulid('id')->primary()`
- **JSONB** for flexible data: `attributes`, `proficiencies` 
- **Optimistic locking**: `version` column for concurrency
- **Indexes**: `(user_id, created_at)` on hot tables
- **JSONB defaults**: `DB::raw("'{}'::jsonb")`

### Character System
- **4 attributes**: STR, INT, VIT, AGI (exactly 10 points at creation)
- **4 character limit** per user account
- **Attributes stored as JSONB**: `{'str': 3, 'int': 2, 'vit': 3, 'agi': 2}`

## Livewire Patterns

### Component Structure
```php
class ComponentName extends Component {
    // Properties with wire:model
    public string $property = '';
    
    // Real-time validation
    public function updated($propertyName): void {
        $this->validateOnly($propertyName);
    }
    
    // Actions return void, use redirect/dispatch
    public function handleAction(): void {
        $result = app(ActionClass::class)->handle(...);
        if ($result->isError()) {
            $this->addError('form', $result->getErrorMessage());
            return;
        }
        $this->redirect(route('...'), navigate: true);
    }
}
```

### UI/Styling Conventions
- **Medieval theme**: Amber/gold colors, Cinzel font, parchment textures
- **Component structure**: Decorative corners, backdrop blur, shadow-2xl
- **Responsive**: `lg:col-span-X` grid, mobile-first approach
- **Animations**: Floating particles, hover transforms, smooth transitions

## Testing with Pest

### Test Structure
```php
it('descriptive test name', function () {
    $user = User::factory()->create();
    $service = app(CreateCharacter::class);
    
    $result = $service->handle(...);
    
    expect($result->isOk())->toBeTrue()
        ->and($result->getPayload()->name)->toBe('Expected');
});
```

### Test Categories
- `tests/Unit/*` - Pure domain/service logic
- `tests/Feature/*` - Livewire interactions, HTTP flows
- Focus on: success, business failures, edge cases, concurrency

## Development Workflow

### Key Commands
```bash
# Development server with queue, vite
composer run dev

# Testing
composer run test
php artisan test --filter=CharacterTest

# Database
php artisan migrate:fresh --seed
php artisan tinker
```

### File Patterns
- **Actions**: Imperative verbs (`CreateCharacter`, `UpgradeItem`)
- **Events**: Past tense (`CharacterCreated`, `ItemUpgraded`)
- **Livewire**: Feature-based (`Characters\Create`, `Auth\LoginModal`)
- **Migrations**: ULID primary keys, JSONB with defaults

## Current Implementation Status

### ✅ Completed
- Character creation with 4-attribute system (STR/INT/VIT/AGI)
- 4-slot character limit per user
- Result pattern for error handling
- Medieval UI theme with Livewire
- Authentication with login modal
- Avatar selection from `public/img/avatars/`

### 🏗️ In Progress
- Character management system
- Authentication flow completion

### 📋 Next Features
- Combat system (turn-based, encounters)
- Item system (templates, instances, upgrades)
- Market/economy system
- Crafting system

## Code Generation Guidelines

1. **Always use the Result pattern** for business operations
2. **Follow DDD folder structure** - Application/Infrastructure separation
3. **Use JSONB for flexible data** with proper defaults
4. **Implement optimistic locking** for concurrent operations
5. **Keep Livewire components thin** - logic in Application layer
6. **Test business logic thoroughly** with Pest
7. **Maintain medieval UI consistency** - amber colors, decorative elements
8. **Use ULIDs for primary keys** and proper indexing strategies

## Domain Context

This is a **micromanagement-focused RPG** where players:
- Create up to 4 characters with emergent classes
- Fight turn-based battles on level-locked maps
- Collect and upgrade items (+0 to +9 enhancement system)
- Participate in player-driven economy
- Craft items and materials
- No mission timers - continuous gameplay loop
