# Przewodnik Wdrożenia Aplikacji Berserk Rush na Serwer VPS (Open Beta Launch)

Niniejszy przewodnik opisuje krok po kroku proces uruchomienia gry **Berserk Rush** na serwerze Linux VPS (Ubuntu 22.04 LTS / 24.04 LTS / Debian 12) dla testerów Open Beta.

---

## 📋 1. Wymagania Wstępne na Serwerze VPS

Przed rozpoczęciem upewnij się, że na serwerze zainstalowane są następujące pakiety:

- **PHP 8.2+** z rozszerzeniami:
  `php8.2-fpm`, `php8.2-cli`, `php8.2-pgsql`, `php8.2-mbstring`, `php8.2-xml`, `php8.2-bcmath`, `php8.2-curl`, `php8.2-zip`, `php8.2-gd`, `php8.2-intl`
- **PostgreSQL 17** (lub **MySQL 8.0**)
- **Node.js 20.x+** i **npm**
- **Composer 2.x+**
- **Nginx**
- **Supervisor** (do zarządzania procesami w tle: kolejki i WebSockets)
- **Certbot** (`python3-certbot-nginx`) do darmowego certyfikatu SSL (HTTPS)

---

## 🐘 Szybka Konfiguracja PostgreSQL na Ubuntu (Krok po Kroku)

Jeśli nie używałeś wcześniej PostgreSQL, konfiguracja jest bardzo prosta i zajmuje około 2 minut:

### 1. Instalacja pakietów:
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib php-pgsql -y
```

### 2. Utworzenie bazy danych i użytkownika:
Wykonaj poniższą serię komend w konsoli Ubuntu:

```bash
# Utworzenie bazy danych
sudo -u postgres psql -c "CREATE DATABASE berserk_rush;"

# Utworzenie użytkownika z bezpiecznym hasłem (zmień 'TwojeTajneHaslo123!')
sudo -u postgres psql -c "CREATE USER berserk_user WITH ENCRYPTED PASSWORD 'TwojeTajneHaslo123!';"

# Nadanie uprawnień do bazy i schematu publicznego
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE berserk_rush TO berserk_user;"
sudo -u postgres psql -d berserk_rush -c "GRANT ALL ON SCHEMA public TO berserk_user; ALTER SCHEMA public OWNER TO berserk_user;"
```

---

## 🚀 2. Krok po Kroku: Od Git Clone do Release

### Krok 1: Pobranie kodu z GitHub
Zaloguj się na serwer VPS przez SSH i sklonuj repozytorium:

```bash
cd /var/www
sudo git clone https://github.com/KenjiWriter/berserk-rush.git
cd berserk-rush
```

Ustaw odpowiednie uprawnienia dla katalogów zapisu:

```bash
sudo chown -R www-data:www-data /var/www/berserk-rush
sudo chmod -R 775 /var/www/berserk-rush/storage /var/www/berserk-rush/bootstrap/cache
```

---

### Krok 2: Instalacja zależności produkcyjnych PHP i Node.js

```bash
# Instalacja paczek PHP bez zależności deweloperskich
composer install --no-dev --optimize-autoloader

# Instalacja paczek Node.js i zbudowanie produkcyjnych assetów (Vite)
npm ci
npm run build
```

---

### Krok 3: Konfiguracja Pliku Środowiskowego (.env)

Skopiuj przykładowy plik `.env.example` i utwórz plik `.env`:

```bash
cp .env.example .env
```

Edytuj plik `.env` za pomocą `nano .env` i ustaw kluczowe wartości:

```ini
APP_NAME="Berserk Rush"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://twoja-domena-beta.pl

# Konfiguracja Bazy Danych PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=berserk_rush
DB_USERNAME=berserk_user
DB_PASSWORD=TwojeTajneHaslo123!

# Kolejki i Sesja
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# WebSockets Reverb (Powiadomienia i Walka w czasie rzeczywistym)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=berserk_beta_id
REVERB_APP_KEY=berserk_beta_key_12345
REVERB_APP_SECRET=berserk_beta_secret_67890
REVERB_HOST="twoja-domena-beta.pl"
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Wygeneruj unikalny klucz aplikacji:

```bash
php artisan key:generate
```

---

### Krok 4: Migracja Bazy Danych i Seederów

Uruchom migracje oraz seedery, aby utworzyć tabele i zaimplementować domyślną bazę przedmiotów, potworów, sklepu i osiągnięć:

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

### Krok 5: Dowiązanie Symboliczne Assetów (Dźwięki, Lektor, Galeria)

Wszystkie pliki dźwięków (`storage/app/public/sound`), dubbingu samouczka (`storage/app/public/voice`) oraz galerii znajdują się w Git. Aby były dostępne publicznie pod adresem `/storage/...`, utwórz dowiązanie symboliczne:

```bash
php artisan storage:link
```

---

### Krok 6: Optymalizacja Produkcyjna Laravela

Zbuforuj konfigurację, trasy oraz widoki dla uzyskania maksymalnej wydajności:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

### Krok 7: Konfiguracja Supervisor (Kolejki i Reverb WebSockets)

Utwórz plik konfiguracyjny supervisora dla roboczych kolejek gry: `/etc/supervisor/conf.d/berserk-worker.conf`:

```ini
[program:berserk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/berserk-rush/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/berserk-rush/storage/logs/worker.log
stopwaitsecs=3600
```

Utwórz plik konfiguracyjny supervisora dla daemona WebSockets Reverb: `/etc/supervisor/conf.d/berserk-reverb.conf`:

```ini
[program:berserk-reverb]
command=php /var/www/berserk-rush/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/berserk-rush/storage/logs/reverb.log
```

Załaduj i uruchom procesy:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

### Krok 8: Konfiguracja Nginx i SSL Certbot

Utwórz plik konfiguracji dla Nginx: `/etc/nginx/sites-available/berserk-rush`:

```nginx
server {
    listen 80;
    server_name twoja-domena-beta.pl;
    root /var/www/berserk-rush/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy dla WebSockets Reverb na ścieżce /app lub porcie 8080
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Włącz witrynę i przeładuj Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/berserk-rush /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Wygeneruj darmowy certyfikat SSL Certbot (HTTPS):

```bash
sudo certbot --nginx -d twoja-domena-beta.pl
```

---

## 🔄 3. Skrypt Szybkiej Aktualizacji (Deployment Update)

Stwórz plik `deploy.sh` w głównym folderze projektu na VPS do szybkiego wdrażania poprawek Open Bety:

```bash
#!/bin/bash
set -e

echo "🚀 Rozpoczynanie wdrażania nowej wersji..."

# Włączenie trybu konserwacji
php artisan down || true

# Pobranie zmian z repozytorium
git pull origin main

# Instalacja zależności
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Migracja bazy danych
php artisan migrate --force

# Czyszczenie i odświeżenie cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart workerów i daemona WebSockets
sudo supervisorctl restart all

# Wyłączenie trybu konserwacji
php artisan up

echo "✅ Wdrożenie zakończone sukcesem!"
```

Nadaj uprawnienia do wykonania:

```bash
chmod +x deploy.sh
```

Wdrożenie nowej wersji sprowadza się teraz do uruchomienia:
`./deploy.sh`
