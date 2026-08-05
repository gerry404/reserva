# Nuvo

Réservation en ligne pour les commerces de service en Afrique francophone.
Le commerçant publie une page de réservation, ses clients réservent 24h/24, il
reçoit la demande sur WhatsApp et par email.

**Laravel 11 (API) · Vue 3 + Vite · PostgreSQL ou MySQL**

---

## Ce que fait le produit

- **Page publique de réservation** par commerce (`/b/mon-salon`), sans compte client.
- **Moteur de disponibilité par intervalles** : un service de 3 h occupe 3 h, pas
  un créneau. Respecte les horaires d'ouverture, le délai minimum de réservation
  et le fuseau horaire du commerce.
- **Tableau de bord** : réservations, services, statistiques, export CSV.
- **Notifications** : WhatsApp (Twilio ou Meta), SMS (Africa's Talking), email.
  Rappel automatique la veille.
- **Paiement d'abonnement** par Mobile Money et carte, via Flutterwave.
- **Suivi client** : le client retrouve et annule sa réservation avec sa
  référence et son numéro, sans compte.

---

## Démarrage

Prérequis : PHP 8.2+, Composer, Node 18+, et MySQL 8 ou PostgreSQL 14+.

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Renseigner la connexion base de données dans .env, puis :
php artisan migrate --seed
php artisan storage:link
php artisan serve            # http://localhost:8000
```

Le worker de queue tourne à part : sans lui, les notifications restent en file :

```bash
php artisan queue:work
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev                  # http://localhost:5173
```

### Compte de démonstration

| Champ | Valeur |
|---|---|
| Email | `demo@nuvo.app` |
| Mot de passe | `password` |
| Page publique | `http://localhost:5173/b/salon-elegance-douala` |

---

## Tests

```bash
cd backend
php vendor/bin/phpunit
```

La suite tourne sur SQLite en mémoire. Elle couvre en priorité les endroits où
une erreur coûte de l'argent ou de la confiance : le moteur de disponibilité et
les collisions de créneaux, le quota du plan gratuit, la sécurité du webhook de
paiement, et le cloisonnement des données entre commerçants.

---

## Architecture

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/   Fins, sans logique métier
│   │   ├── Requests/      Validation
│   │   ├── Resources/     Sérialisation (Public* = version visiteur, réduite)
│   │   └── Middleware/    EnsureBusinessExists, RequiresPlan
│   ├── Services/
│   │   ├── AvailabilityService   Source de vérité des créneaux
│   │   ├── BookingService        Création sous verrou
│   │   ├── StatsService          Agrégations du tableau de bord
│   │   ├── SubscriptionService   Activation des plans
│   │   ├── FlutterwaveGateway    Appels au prestataire de paiement
│   │   └── MessagingService      WhatsApp / SMS
│   ├── Policies/          Cloisonnement par commerce
│   ├── Rules/             PhoneNumber, WorkingHours
│   └── Support/           Money, Uploads, WhatsAppLink, BookingMessage
└── tests/Feature/

frontend/src/
├── api/           Client HTTP unique, erreurs normalisées
├── stores/        Pinia (auth, bookings)
├── composables/   useAccent (contraste), usePhoneInput
└── views/         public/ · auth/ · dashboard/ · pages/
```

### Deux invariants à connaître avant de modifier le code

1. **Une réservation est un intervalle.** `date` + `time_slot` + `duration` sont
   les entrées ; `starts_at` / `ends_at` en sont dérivés à la sauvegarde et sont
   les seules colonnes que lit la logique de disponibilité. N'écrivez jamais
   directement dans les colonnes dérivées.

2. **Les droits sont calculés, pas stockés.** `User::effectivePlan()` traite un
   abonnement expiré comme gratuit immédiatement. La commande `plans:expire` ne
   sert qu'à garder la colonne cohérente pour le reporting.

---

## Déploiement (VPS auto-hébergé)

Tout tourne sur ta machine, derrière un seul domaine. Le frontend, l'API et les
images partagent la même origine : il n'y a donc **aucune configuration CORS à
faire**, et un seul certificat à renouveler.

```
                     ┌─────────────────────────────┐
   :443 ──────────►  │ caddy   TLS auto + SPA      │
                     └──┬──────────────────────┬───┘
                        │ /api/*               │ /storage/*
                     ┌──▼──────────┐        ┌──▼────────┐
                     │ app         │        │ volume    │
                     │ FrankenPHP  │        │ uploads   │
                     └──┬──────────┘        └───────────┘
       ┌────────────────┼────────────────┐
   ┌───▼──────┐   ┌─────▼─────┐   ┌──────▼──────┐
   │ worker   │   │ scheduler │   │ postgres    │
   │queue:work│   │schedule   │   │ volume      │
   └──────────┘   └───────────┘   └─────────────┘
```

Quatre processus, parce qu'un seul serveur web ne peut pas faire les quatre
métiers : le `worker` empêche les notifications de tourner **dans** la requête du
client, et sans le `scheduler` aucun rappel ne part jamais.

### Prérequis sur le VPS

- Docker Engine + plugin Compose
- Le DNS du domaine pointe déjà sur l'IP du VPS (sinon Let's Encrypt ne peut pas
  émettre le certificat au premier démarrage)
- Ports 80 et 443 ouverts

### Première installation

```bash
git clone <votre-dépôt> /srv/nuvo && cd /srv/nuvo

cp .env.example .env
nano .env                    # domaine, mot de passe base, SMTP, clés Flutterwave

# Générer la clé applicative une bonne fois
docker compose run --rm app php artisan key:generate --show
# → copier la valeur dans APP_KEY

docker compose up -d --build
```

Les migrations s'exécutent automatiquement au démarrage du conteneur `app`, et
lui seul ; trois processus qui migrent en même temps, c'est un schéma appliqué à
moitié.

### Déploiements suivants

```bash
./scripts/deploy.sh
```

Le script sauvegarde la base, construit les images **avant** d'arrêter quoi que
ce soit, déploie, puis attend que l'API réponde `healthy`. Si elle ne répond
jamais, il affiche les logs et s'arrête : un déploiement à moitié fait qui
annonce « OK » est pire qu'un déploiement qui échoue bruyamment.

### Sauvegardes

Auto-hébergé veut dire que les sauvegardes sont ta responsabilité. Installe-les
dans le crontab de l'hôte, sinon elles n'existent pas :

```bash
crontab -e
0 3 * * * cd /srv/nuvo && ./scripts/backup-db.sh >> /var/log/nuvo-backup.log 2>&1
```

Restauration : `./scripts/restore-db.sh backups/nuvo_2026-08-02_030000.sql.gz`
(demande confirmation et prend un instantané de sécurité avant d'écraser).

> Une sauvegarde jamais restaurée est une hypothèse, pas une sauvegarde.
> Teste une restauration de temps en temps.

### Variables sensibles

- `APP_KEY` : la générer une fois et ne plus y toucher : la changer invalide
  toutes les sessions et rend illisibles les valeurs chiffrées.
- `FLW_WEBHOOK_HASH` : **obligatoire**. Sans elle, le webhook de paiement refuse
  toutes les requêtes, volontairement : c'est la seule chose qui distingue
  Flutterwave de n'importe qui ayant trouvé l'URL.
- `MAIL_*` : sans transport réel, la réinitialisation de mot de passe ne peut
  pas fonctionner.

### Opérations courantes

```bash
docker compose logs -f app worker      # suivre les logs
docker compose ps                      # état des conteneurs
docker compose exec app php artisan tinker
docker compose exec postgres psql -U nuvo nuvo
docker compose restart worker          # après un changement de config de queue
```

---

## Tarifs

| Plan | Prix | Limite |
|---|---|---|
| Gratuit | 0 | 30 réservations / mois |
| Pro | 2 900 F CFA / mois | Illimité, statistiques avancées, lien personnalisé |
| Business | 7 900 F CFA / mois | Pro + SMS automatiques |

Tout compte démarre par 14 jours d'essai Pro, sans carte bancaire.
