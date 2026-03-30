# Réserva — Guide de démarrage

## Ce qui a été construit

Application complète de réservation en ligne pour commerçants africains.
**50 fichiers · Laravel 11 API · Vue 3 Frontend · Tailwind CSS**

---

## Architecture

```
reserva/
├── backend/        # Laravel 11 — API REST
└── frontend/       # Vue 3 + Vite + Tailwind CSS
```

---

## Backend Laravel (`backend/`)

| Fichier | Rôle |
|---|---|
| `AuthController` | Inscription, connexion, déconnexion |
| `BusinessController` | Profil du commerce + upload logo |
| `ServiceController` | CRUD services (créer, modifier, supprimer, activer) |
| `BookingController` | Liste + filtres + changement de statut + export CSV |
| `DashboardController` | Statistiques, graphiques, prochaines réservations |
| `PublicBookingController` | Page publique + créneaux disponibles + réservation client |
| `NotifyMerchantNewBooking` | Job async — WhatsApp (Twilio/Meta) + SMS (Africa's Talking) |
| `BookingStatusNotification` | Messages WhatsApp/SMS formatés |
| 4 migrations | Tables : `users`, `businesses`, `services`, `bookings` |
| `DatabaseSeeder` | Données de démonstration complètes |

---

## Frontend Vue 3 (`frontend/`)

| Page | Description |
|---|---|
| `LandingPage.vue` | Page marketing — hero, fonctionnalités, tarifs, témoignages |
| `LoginView.vue` | Connexion avec compte démo |
| `RegisterView.vue` | Inscription en 2 étapes (compte → commerce) |
| `DashboardView.vue` | Statistiques, graphique 7 jours, réservations imminentes |
| `BookingsView.vue` | Tableau + filtres + actions + pagination |
| `ServicesView.vue` | Grille de services + modal CRUD + couleurs |
| `SettingsView.vue` | Profil, horaires d'ouverture, notifications, couleur |
| `PublicBookingView.vue` | **Page client** : service → calendrier → créneau → formulaire → confirmation |

---

## Démarrage

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

---

### Étape 1 — Backend

```bash
cd backend

# 1. Installer les dépendances PHP
composer install

# 2. Copier la config
cp .env.example .env

# 3. Générer la clé d'application
php artisan key:generate

# 4. Créer la base de données MySQL
mysql -u root -e "CREATE DATABASE reserva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Configurer .env (ouvrir le fichier et renseigner)
# DB_DATABASE=reserva
# DB_USERNAME=root
# DB_PASSWORD=ton_mot_de_passe

# 6. Lancer les migrations + données de démo
php artisan migrate --seed

# 7. Lier le dossier storage
php artisan storage:link

# 8. Démarrer le serveur
php artisan serve
# → http://localhost:8000
```

---

### Étape 2 — Frontend

```bash
cd frontend

# 1. Installer les dépendances JS
npm install

# 2. Démarrer le serveur de développement
npm run dev
# → http://localhost:5173
```

---

## Accès

| URL | Description |
|---|---|
| `http://localhost:5173` | Page d'accueil marketing |
| `http://localhost:5173/login` | Connexion |
| `http://localhost:5173/register` | Inscription |
| `http://localhost:5173/dashboard` | Tableau de bord commerçant |
| `http://localhost:5173/b/salon-elegance-douala` | Page publique cliente (démo) |

---

## Compte de démonstration

| Champ | Valeur |
|---|---|
| Email | `demo@reserva.cm` |
| Mot de passe | `password` |

---

## Routes API principales

### Sans authentification
```
GET  /api/b/{slug}             Infos du commerce
GET  /api/b/{slug}/slots       Créneaux disponibles
POST /api/b/{slug}/book        Créer une réservation
```

### Avec Bearer token
```
POST  /api/auth/register
POST  /api/auth/login
GET   /api/auth/me

GET   /api/business
PUT   /api/business

GET   /api/services
POST  /api/services
PUT   /api/services/{id}
DELETE /api/services/{id}

GET   /api/bookings
PATCH /api/bookings/{id}/status
GET   /api/bookings/export/csv

GET   /api/dashboard/stats
GET   /api/dashboard/upcoming
GET   /api/dashboard/chart
```

---

## Configurer les notifications (optionnel)

### WhatsApp via Twilio
```env
WHATSAPP_PROVIDER=twilio
TWILIO_SID=ACxxxxxxxx
TWILIO_TOKEN=xxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

### WhatsApp via Meta Business API
```env
WHATSAPP_PROVIDER=meta
META_WA_TOKEN=EAAxxxxxx
META_WA_PHONE_ID=123456789
```

### SMS via Africa's Talking
```env
AT_USERNAME=ton_username
AT_API_KEY=ton_api_key
AT_SENDER_ID=RESERVA
```

### Activer le worker de files d'attente
```bash
php artisan queue:work
```

---

## Plans tarifaires implémentés

| Plan | Prix | Limite |
|---|---|---|
| Gratuit | 0 F CFA/mois | 30 réservations/mois |
| Pro | 5 000 F CFA/mois | Illimité + rappels + stats |
| Business | 15 000 F CFA/mois | Multi-employés + multi-lieux |

---

## PWA — Installation sur mobile

L'application est une Progressive Web App complète.

| Fonctionnalité | Détail |
|---|---|
| Bannière d'installation | Apparaît automatiquement après 4 secondes |
| Android / Chrome | Bouton "Installer" natif via `beforeinstallprompt` |
| iPhone / Safari | Guide pas-à-pas affiché automatiquement |
| Hors ligne | Page de fallback si aucune connexion |
| Cache | API (5 min), assets (1 an), fonts Google (1 an) |
| Icônes | 192×192, 512×512, maskable, apple-touch |
| Re-proposition | Si rejeté, re-propose après 3 jours |

### Générer les icônes PNG (déjà fait, à relancer si vous changez le design)
```bash
cd frontend
npm run generate:icons
```

---

## Build production

```bash
# Frontend
cd frontend
npm run build
# → Déployer le dossier dist/ sur Nginx/Apache

# Backend
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work --daemon
```
