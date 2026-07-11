<div align="center">
  <img src="https://via.placeholder.com/600x200.png?text=Berserk+Rush" alt="Berserk Rush Logo">

  <h1>Berserk Rush</h1>

  <p>
    <strong>A browser-based multiplayer RPG with emergent classes, micromanagement, economy, and dynamic item upgrades.</strong>
  </p>
  
  <p>
    <a href="#features">Features</a> •
    <a href="#tech-stack">Tech Stack</a> •
    <a href="#architecture">Architecture</a> •
    <a href="#installation">Installation</a> •
    <a href="#documentation">Documentation</a>
  </p>

  <p>
    <img src="https://img.shields.io/badge/PHP-8.3+-777BB4.svg?style=flat&logo=php" alt="PHP 8.3">
    <img src="https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=flat&logo=laravel" alt="Laravel 12">
    <img src="https://img.shields.io/badge/PostgreSQL-17-4169E1.svg?style=flat&logo=postgresql" alt="Postgres">
    <img src="https://img.shields.io/badge/Redis-Horizon-DC382D.svg?style=flat&logo=redis" alt="Redis">
  </p>
</div>

---

## 🗡️ About The Game

**Berserk Rush** is a browser-based, turn-based multiplayer RPG heavily inspired by games like *Shakes & Fidget* mixed with the intense item upgrade mechanics from *Metin2*.

The game focuses on **micromanagement, crafting, economy, and emergent classes**. There is no class selection at the start of your journey — your character's class and playstyle emerge naturally based on how you invest your attribute points and which items you choose to equip and upgrade.

### Core Gameplay Loop
`Fight ⚔️ → Loot 💰 → Trade 🤝 → Upgrade ⚒️ → Craft 🔮`

### In-Game Chat Commands

**Guild Commands (Available to guild members):**
- `/donate exp <amount>` - Donate character EXP to the guild's progression.
- `/donate gold <amount>` - Donate Gold to the guild's treasury.
- `/donate gems <amount>` - Donate Gems to the guild's treasury.

**Admin Commands (Requires permission_level == 9):**
- `/give <item_id> <amount>` - Spawn a specific item directly in your character's inventory.
- `/give gold <amount>` - Add Gold to your character.
- `/give gems <amount>` - Add Gems to your user account.
- `/give pet <pet_name>` - Dodaj określonego zwierzaka (np. Leśny Wilk) dla postaci.
- `/exp <amount>` - Grant EXP to your character and trigger level ups if applicable.
- `/set level <level>` - Instantly set your character to a specific level.
- `/set sp <amount>` - Add Stat Points (character_points) to your character.

## ✨ Features

- **Emergent Classes:** No fixed classes at character creation. Choose a name and distribute 10 attribute points (STR, INT, VIT, AGI). Your playstyle evolves based on your investments.
- **Turn-based Idle Combat:** Fight monsters on level-locked maps (e.g., Lv. 0–10, 10–30) with a fully simulated turn-based combat system.
- **Dynamic Item System:** Items drop with random stats, rarities, and can be upgraded from +0 to +9. Be careful, upgrades can fail!
- **Crafting & Professions:** Gather materials, learn recipes, and craft unique gear with a chance to proc higher Rarities (Common up to Legendary).
- **Player-Driven Economy:** An active market / auction house where players dictate the prices of items and materials.
- **Player vs Player (PvP):** Challenge other players in the Arena to climb the ELO leaderboard, earn leagues, and get Arena Tokens to spend in the Gladiator Shop.
- **Guilds & Social:** Join or create Guilds with their own treasuries, role structures, private real-time chat, and an internal mail invitation system. Members can use `/donate` commands to support their guild. Engage in Guild Wars (GvG) to earn rewards and assert dominance.
- **Real-time Global Chat:** A WebSocket-powered (Laravel Reverb) chat panel pinned to the corner of the screen. Supports multiple channels (Global/Guild), unread counters, in-chat slash commands, and player inspection tooltips showing equipped gear.
- **Admin Panel & Tools:** Game masters (permission_level == 9) have access to a suite of in-chat commands for testing and a dedicated web-based administrative panel to manage the game state.
- **World Bosses:** Epic global events where the entire server collaborates to defeat a massive boss, with live leaderboards and proportional rewards based on damage dealt.
- **No Mission Timers:** Play at your own pace without arbitrary stamina bars or time limits.

## 🛠️ Tech Stack

Berserk Rush is built using modern and robust technologies designed for scale and real-time multiplayer features:

- **Backend:** Laravel 12 (PHP 8.3+) using Laravel Breeze + Livewire for reactive UI components.
- **Database:** PostgreSQL 17 + pgAdmin 4. Utilizing `jsonb` for dynamic stats/rolls, ULIDs as primary keys, and partial indexes.
- **Cache & Queues:** Redis (Horizon) for caching, background queue processing, leaderboards (Sorted Sets), and locking mechanisms.
- **Realtime Events:** Laravel WebSockets / Reverb / Pusher for live chat, market updates, and combat events.
- **Testing:** Pest (built on top of PHPUnit) with high coverage on complex domain actions.

## 🏛️ Architecture & Patterns

The codebase is structured around **Domain-Driven Design (DDD)** concepts and **CQRS-lite** principles to maintain a clean, scalable, and testable environment.

- **Action/Service Pattern:** Core logic is handled by specific Actions (e.g., `StartEncounter`, `UpgradeItem`) using the Transaction Script pattern for robust DB operations.
- **Result Object Pattern:** Avoiding exceptions for standard business logic failures. Actions return `Result::ok()` or `Result::error()`.
- **Domain Events:** Side-effects (mail delivery, leaderboards) are handled asynchronously via listeners reacting to events like `ItemUpgraded` or `EncounterFinished`.
- **Idempotency:** Core economy and crafting actions use `idempotency_key` ledgers to prevent duplication and ensure consistency.
- **Ledgers:** All item and currency movements are strictly audited in `CurrencyLedger` and `ItemLedger` tables.

*See the `docs/` folder for in-depth documentation on implemented modules.*

## 🚀 Installation

*Note: Ensure you have PHP 8.3+, Composer, Node.js, PostgreSQL 17, and Redis installed on your local machine.*

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/berserk-rush.git
   cd berserk-rush
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install Node Dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Update the `.env` file with your PostgreSQL database credentials and set `CACHE_DRIVER`, `QUEUE_CONNECTION`, and `SESSION_DRIVER` to `redis`.*

5. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

6. **Start the WebSocket server (Laravel Reverb)**

   > [!IMPORTANT]
   > The real-time global chat requires the Reverb WebSocket server to be running. Without it, chat messages will not be delivered between players.

   ```bash
   php artisan reverb:start
   ```

   Alternatively, use the all-in-one dev command which starts **all** required processes concurrently (web server, queue, Vite, and Reverb):

   ```bash
   composer dev
   ```

7. **Start the Queue Worker (Combat & Async Tasks)**

   > [!IMPORTANT]
   > Combat calculation is fully offloaded to background workers to ensure horizontal scaling. Without running a worker, battles will remain stuck in the "Calculating..." state.

   **For Local Development:**
   ```bash
   php artisan queue:work
   ```

   **For Production (Supervisor):**
   In a production environment, you should use **Supervisor** or **Laravel Horizon** to manage and scale your worker processes. Example Supervisor configuration (`/etc/supervisor/conf.d/berserk-worker.conf`):
   ```ini
   [program:berserk-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/berserk-rush/artisan queue:work --sleep=1 --tries=3
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=8
   redirect_stderr=true
   stdout_logfile=/path/to/berserk-rush/storage/logs/worker.log
   ```

8. **Open in browser**

   Navigate to `http://localhost:8000`.

## 📖 Documentation

Detailed documentation of implemented systems can be found in the [docs/](./docs) directory:
- [Architecture & Conventions](./docs/architecture.md)
- [Characters Module](./docs/modules/characters.md)
- [Combat Module](./docs/modules/combat.md)
- [Loot & Economy Module](./docs/modules/loot.md)
- [Equipment & Upgrades](./docs/modules/upgrades.md)
- [Wizard (Enchanting)](./docs/modules/wizard.md)
- [Witch & Crafting](./docs/modules/witch_and_crafting.md)
- [Economy & Mail](./docs/modules/economy.md)
- [**Global & Guild Chat (Reverb)**](./docs/modules/global_chat.md)
- [**Guilds**](./docs/modules/guilds.md)
- [**PvP Arena & Guild Wars**](./docs/modules/pvp_and_arena.md)
- [**World Bosses**](./docs/modules/world_boss.md)
- [**Tutorial & Onboarding**](./docs/modules/tutorial.md)

## 🛡️ License

Berserk Rush is a proprietary project. All rights reserved. Do not distribute or copy without explicit permission.
