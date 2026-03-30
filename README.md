# Réserva

Système de réservation en ligne pour les commerçants africains.
**Laravel 11 API + Vue 3 + Tailwind CSS**

---

## Stack technique

| Couche     | Technologie                          |
|------------|--------------------------------------|
| Frontend   | Vue 3, Vite, Tailwind CSS, Pinia     |
| Backend    | Laravel 11, Sanctum (API auth)       |
| Base de données | MySQL 8+                        |
| Notifications | WhatsApp (Twilio / Meta) + SMS (Africa's Talking) |
| Queues     | Laravel Queue (database driver)      |

---

## Installation rapide

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

---

### 1. Backend (Laravel)

```bash
# Dans le dossier backend/
cd backend

# Installer les dépendances
composer install

# Copier la config
cp .env.example .env

# Générer la clé
php artisan key:generate

# Configurer DB dans .env
# DB_DATABASE=reserva
# DB_USERNAME=root
# DB_PASSWORD=

# Créer la base de données
mysql -u root -e "CREATE DATABASE reserva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrer + seeder (données de démonstration)
php artisan migrate --seed

# Lier le storage public
php artisan storage:link

# Lancer le serveur
php artisan serve
# → http://localhost:8000
```

### 2. Frontend (Vue 3)

```bash
# Dans le dossier frontend/
cd frontend

# Installer les dépendances
npm install

# Lancer le serveur de dev
npm run dev
# → http://localhost:5173
```

---

## Compte de démonstration

| Champ    | Valeur              |
|----------|---------------------|
| Email    | demo@reserva.cm     |
| Mot de passe | password        |

---

## Page publique de démonstration

```
http://localhost:5173/b/salon-elegance-douala
```

---

## Structure du projet

```
reserva/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/   # AuthController, BookingController, etc.
│   │   ├── Models/             # User, Business, Service, Booking
│   │   ├── Notifications/      # BookingStatusNotification
│   │   └── Jobs/               # NotifyMerchantNewBooking
│   ├── database/
│   │   ├── migrations/         # Tables: businesses, services, bookings
│   │   └── seeders/            # Données de démo
│   └── routes/
│       └── api.php             # Toutes les routes API
│
└── frontend/                   # Vue 3 + Vite
    └── src/
        ├── api/                # Axios API client
        ├── router/             # Vue Router
        ├── stores/             # Pinia stores
        └── views/
            ├── LandingPage.vue          # Page d'accueil marketing
            ├── auth/                    # Login, Register
            ├── dashboard/               # Tableau de bord (4 pages)
            └── public/PublicBookingView.vue  # Page client de réservation
```

---

## API Endpoints

### Public (sans authentification)
```
GET  /api/b/{slug}            Infos du commerce + services
GET  /api/b/{slug}/slots?date= Créneaux disponibles
POST /api/b/{slug}/book        Créer une réservation
```

### Authentifié (Bearer token Sanctum)
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me

GET  /api/business
PUT  /api/business

GET/POST/PUT/DELETE /api/services

GET  /api/bookings
GET  /api/bookings/{id}
PATCH /api/bookings/{id}/status
GET  /api/bookings/export/csv

GET  /api/dashboard/stats
GET  /api/dashboard/upcoming
GET  /api/dashboard/chart
```

---

## Notifications WhatsApp/SMS

### Via Twilio (WhatsApp Sandbox)
Dans `.env` :
```env
WHATSAPP_PROVIDER=twilio
TWILIO_SID=ACxxxxxxxx
TWILIO_TOKEN=xxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

### Via Meta (WhatsApp Business API)
```env
WHATSAPP_PROVIDER=meta
META_WA_TOKEN=EAAxxxxxx
META_WA_PHONE_ID=123456789
```

### Via Africa's Talking (SMS)
```env
AT_USERNAME=votre_username
AT_API_KEY=votre_api_key
AT_SENDER_ID=RESERVA
```

---

## Plans tarifaires

| Plan     | Prix        | Limite         |
|----------|-------------|----------------|
| Gratuit  | 0 F CFA     | 30 résa/mois   |
| Pro      | 5 000 F CFA | Illimité + stats |
| Business | 15 000 F CFA| Multi-lieux    |

---

## Production

```bash
# Backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue worker (notifications)
php artisan queue:work --daemon

# Frontend
npm run build
# Déployer dist/ sur Nginx/Apache
```
