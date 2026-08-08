<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Un compte par état que l'application sait produire.
 *
 * Le seeder de démonstration n'en créait qu'un, en Pro payé : tout le reste du
 * produit (le quota du plan gratuit, la bannière d'essai, la bascule à
 * l'expiration, l'écran d'accueil sans commerce) ne pouvait se tester qu'en
 * modifiant la base à la main.
 *
 * Les états ne sont pas écrits en dur mais construits par leurs causes réelles :
 * l'essai se reconnaît à une échéance proche sans paiement réussi, l'expiration
 * à une échéance passée, le quota au nombre de réservations créées dans le mois.
 * Un compte fabriqué autrement afficherait un état que le produit ne peut pas
 * atteindre, et masquerait précisément les bugs qu'on cherche.
 */
class TestAccountsSeeder extends Seeder
{
    /** Le même pour tous : ces comptes ne servent qu'en local. */
    private const PASSWORD = 'password';

    public function run(): void
    {
        $comptes = [
            $this->gratuit(),
            $this->gratuitAuQuota(),
            $this->proEnEssai(),
            $this->proPaye(),
            $this->proExpire(),
            $this->business(),
            $this->sansCommerce(),
        ];

        $this->command->newLine();
        $this->command->info('Comptes de test (mot de passe : ' . self::PASSWORD . ')');
        $this->command->newLine();

        $this->command->table(
            ['Email', 'État à tester'],
            array_map(fn (array $c) => [$c['email'], $c['etat']], $comptes),
        );
    }

    /** Plan Découverte, sous le quota : les limites sont visibles sans bloquer. */
    private function gratuit(): array
    {
        $user = $this->merchant('gratuit@nuvo.app', 'Awa Traoré', User::PLAN_FREE, null);
        $business = $this->business_($user, 'Coiffure Awa', 'coiffure-awa', 'Bamako');
        $services = $this->services($business, 3);
        $this->bookings($business, $services, 6);

        return ['email' => $user->email, 'etat' => 'Découverte, 6 réservations sur 30'];
    }

    /**
     * Plan Découverte au plafond.
     *
     * Le quota compte sur `created_at` et non sur la date du rendez-vous : les
     * réservations sont donc datées dans le mois courant côté création, quelle
     * que soit la date à laquelle elles ont lieu.
     */
    private function gratuitAuQuota(): array
    {
        $user = $this->merchant('quota@nuvo.app', 'Ibrahim Sow', User::PLAN_FREE, null);
        $business = $this->business_($user, 'Barbier Ibrahim', 'barbier-ibrahim', 'Dakar');
        $services = $this->services($business, 3);
        $this->bookings($business, $services, 30);

        return ['email' => $user->email, 'etat' => 'Découverte au plafond, 30 sur 30'];
    }

    /**
     * Pro en période d'essai.
     *
     * onTrial() exige trois choses ensemble : le plan Pro, une échéance dans les
     * quatorze jours, et aucun paiement réussi. Retirer la troisième suffirait à
     * faire passer un abonné payant pour un essayeur.
     */
    private function proEnEssai(): array
    {
        $user = $this->merchant('essai@nuvo.app', 'Chantal Mbala', User::PLAN_PRO, now()->addDays(9));
        $business = $this->business_($user, 'Institut Chantal', 'institut-chantal', 'Yaoundé');
        $services = $this->services($business, 5);
        $this->bookings($business, $services, 12);

        return ['email' => $user->email, 'etat' => 'Essai Pro, 9 jours restants'];
    }

    /** Pro payé : l'échéance est lointaine et un paiement réussi existe. */
    private function proPaye(): array
    {
        $user = $this->merchant('pro@nuvo.app', 'Serge Kouassi', User::PLAN_PRO, now()->addMonths(10));
        $business = $this->business_($user, 'Spa Serenity', 'spa-serenity', 'Abidjan');
        $services = $this->services($business, 6);
        $this->bookings($business, $services, 18);
        $this->paiement($user, User::PLAN_PRO, 9900);

        return ['email' => $user->email, 'etat' => 'Pro payé, actif'];
    }

    /**
     * Pro expiré.
     *
     * Le plan reste écrit `pro` en base : c'est effectivePlan() qui rétrograde
     * sur une échéance passée. Écrire `free` ici ne testerait pas la bascule.
     */
    private function proExpire(): array
    {
        $user = $this->merchant('expire@nuvo.app', 'Nadia Benali', User::PLAN_PRO, now()->subDays(5));
        $business = $this->business_($user, 'Beauté Nadia', 'beaute-nadia', 'Casablanca');
        $services = $this->services($business, 4);
        $this->bookings($business, $services, 9);
        $this->paiement($user, User::PLAN_PRO, 9900, now()->subYear());

        return ['email' => $user->email, 'etat' => 'Pro expiré depuis 5 jours, rétrogradé en Découverte'];
    }

    /** Le palier supérieur. */
    private function business(): array
    {
        $user = $this->merchant('business@nuvo.app', 'Kofi Mensah', User::PLAN_BUSINESS, now()->addYear());
        $business = $this->business_($user, 'Mensah Grooming', 'mensah-grooming', 'Accra');
        $services = $this->services($business, 6);
        $this->bookings($business, $services, 24);
        $this->paiement($user, User::PLAN_BUSINESS, 24900);

        return ['email' => $user->email, 'etat' => 'Business, actif'];
    }

    /**
     * Compte sans commerce.
     *
     * EnsureBusinessExists renvoie ce cas vers l'écran de configuration : c'est
     * le seul moyen de tester le parcours d'accueil sans créer un compte à la
     * main à chaque fois.
     */
    private function sansCommerce(): array
    {
        $user = User::create([
            'name'     => 'Nouveau Commerçant',
            'email'    => 'nouveau@nuvo.app',
            'password' => Hash::make(self::PASSWORD),
            'phone'    => '+237600000000',
            'plan'     => User::PLAN_FREE,
        ]);

        return ['email' => $user->email, 'etat' => 'Sans commerce, arrive sur la configuration'];
    }

    // ── Fabriques ────────────────────────────────────────────────────────────

    private function merchant(string $email, string $name, string $plan, ?\Illuminate\Support\Carbon $expires): User
    {
        return User::create([
            'name'            => $name,
            'email'           => $email,
            'password'        => Hash::make(self::PASSWORD),
            'phone'           => '+237' . random_int(600000000, 699999999),
            'plan'            => $plan,
            'plan_expires_at' => $expires,
        ]);
    }

    /**
     * Une teinte par commerce, tirée du nuancier proposé aux commerçants.
     *
     * Tous partageaient le défaut de la colonne : la page publique rendait à
     * l'identique d'un compte à l'autre, et rien ne montrait que la couleur
     * d'accent traverse réellement l'interface.
     */
    private const ACCENTS = ['#14603C', '#0EA5E9', '#F59E0B', '#EC4899', '#06B6D4', '#F97316'];

    private function business_(User $user, string $name, string $slug, string $city): Business
    {
        return $user->business()->create([
            'name'        => $name,
            'slug'        => $slug,
            'category'    => 'Salon de coiffure',
            'city'        => $city,
            'address'     => 'Centre-ville',
            'country'     => 'CM',
            'timezone'    => 'Africa/Douala',
            'currency'    => 'XAF',
            'phone'       => $user->phone,
            'whatsapp'    => $user->phone,
            'description' => 'Compte de test : ' . $name . '.',
            'working_hours' => [
                'lundi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
                'mardi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
                'mercredi' => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
                'jeudi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
                'vendredi' => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
                'samedi'   => ['is_open' => true,  'open' => '09:00', 'close' => '16:00'],
                'dimanche' => ['is_open' => false, 'open' => '09:00', 'close' => '13:00'],
            ],
            'slot_duration'          => 30,
            'booking_notice'         => 60,
            'notifications_whatsapp' => true,
            'notifications_email'    => true,
            'notifications_sms'      => false,
            'is_active'              => true,
            'accent_color'           => self::ACCENTS[$user->id % count(self::ACCENTS)],
        ]);
    }

    /** @return array<int, Service> */
    private function services(Business $business, int $combien): array
    {
        // Les couleurs sont explicites : sans elles, les prestations prenaient
        // le défaut de la colonne, et tout le tableau de bord affichait la
        // même teinte. Elles sortent du nuancier de src/design/tokens.js.
        $catalogue = [
            ['name' => 'Coupe simple',     'duration' => 30,  'price' => 3000,  'color' => '#14603C'],
            ['name' => 'Coupe + barbe',    'duration' => 45,  'price' => 5000,  'color' => '#0EA5E9'],
            ['name' => 'Soin capillaire',  'duration' => 60,  'price' => 6000,  'color' => '#06B6D4'],
            ['name' => 'Défrisage',        'duration' => 90,  'price' => 8000,  'color' => '#F59E0B'],
            ['name' => 'Tresses',          'duration' => 180, 'price' => 15000, 'color' => '#EC4899'],
            ['name' => 'Massage détente',  'duration' => 120, 'price' => 12000, 'color' => '#F97316'],
        ];

        return array_map(
            fn (array $s) => $business->allServices()->create($s + ['is_active' => true]),
            array_slice($catalogue, 0, $combien),
        );
    }

    /**
     * Des rendez-vous répartis autour d'aujourd'hui.
     *
     * Un seul par jour et par commerce : deux prestations longues posées à la
     * même heure se chevaucheraient, et le tableau de bord montrerait un état
     * que le moteur de disponibilité refuse de créer.
     */
    private function bookings(Business $business, array $services, int $combien): void
    {
        $clients = [
            ['Amina Diallo', '+237690001122'], ['Fatou Bèye', '+237690003344'],
            ['Sandra Mbarga', '+237690005566'], ['Rose Biya', '+237690007788'],
            ['Carine Nkondo', '+237690009900'], ['Sylvie Atangana', '+237691112233'],
            ['Grace Etoundi', '+237691114455'], ['Bella Ngo', '+237691116677'],
        ];

        // Deux pièges se sont succédé ici, et le second n'est pas l'inverse du
        // premier. Compter les tours de boucle plutôt que les réservations
        // créées laissait le compte « au plafond » sous le plafond, parce que
        // les dimanches sont fermés et sautés. Puis compter toutes les
        // réservations l'y a laissé de nouveau, parce que le quota exclut les
        // annulées : seules celles qui pèsent sur le quota font avancer le
        // compteur, les annulées viennent en plus.
        $comptees = 0;
        $rang = 0;
        $decalage = -intdiv($combien, 2);

        while ($comptees < $combien) {
            $jour = now($business->timezone)->addDays($decalage++);

            if ($jour->isSunday()) {
                continue; // Fermé : une réservation ce jour-là serait impossible.
            }

            $service = $services[$rang % count($services)];
            [$nom, $tel] = $clients[$rang % count($clients)];
            $statut = $this->statut($jour, $rang);
            $rang++;

            if ($statut !== Booking::STATUS_CANCELLED) {
                $comptees++;
            }

            Booking::create([
                'business_id'    => $business->id,
                'service_id'     => $service->id,
                'customer_name'  => $nom,
                'customer_phone' => $tel,
                'customer_email' => strtolower(explode(' ', $nom)[0]) . '@example.com',
                'date'           => $jour->toDateString(),
                'time_slot'      => '09:00',
                'duration'       => $service->duration,
                'price'          => $service->price,
                'status'         => $statut,
            ]);
        }
    }

    /**
     * Un mélange de statuts que le filtre puisse réellement trier.
     *
     * « Passé égale terminé, futur égale en attente » ne produisait que deux
     * statuts sur cinq : filtrer sur « confirmé » ou « annulé » renvoyait une
     * liste vide sur tous les comptes, ce qui ressemblait à un bug du filtre.
     *
     * La répartition suit le rang plutôt que le hasard, pour que deux exécutions
     * du seeder donnent la même base et qu'un test reste reproductible.
     */
    private function statut(\Illuminate\Support\Carbon $jour, int $rang): string
    {
        if ($jour->isPast()) {
            return match ($rang % 5) {
                0       => Booking::STATUS_NO_SHOW,
                1       => Booking::STATUS_CANCELLED,
                default => Booking::STATUS_COMPLETED,
            };
        }

        if ($jour->isToday()) {
            return Booking::STATUS_CONFIRMED;
        }

        return match ($rang % 3) {
            0       => Booking::STATUS_CONFIRMED,
            1       => Booking::STATUS_PENDING,
            default => Booking::STATUS_PENDING,
        };
    }

    /** Un paiement réussi : c'est lui qui distingue un abonné d'un essayeur. */
    private function paiement(User $user, string $plan, int $montant, ?\Illuminate\Support\Carbon $quand = null): void
    {
        $paiement = Payment::create([
            'user_id'            => $user->id,
            'plan'               => $plan,
            'billing_cycle'      => Payment::CYCLE_MONTHLY,
            'amount'             => $montant,
            'currency'           => 'XAF',
            'status'             => Payment::STATUS_SUCCESSFUL,
            'tx_ref'             => 'test-' . $user->id . '-' . uniqid(),
            'flw_ref'            => 'FLW-TEST-' . random_int(100000, 999999),
            'flw_transaction_id' => (string) random_int(100000, 999999),
            'payment_method'     => 'mobilemoney',
            'paid_at'            => $quand ?? now()->subDays(3),
        ]);

        // `created_at` n'est pas assignable en masse, et c'est lui que lit
        // l'historique de facturation.
        $paiement->forceFill(['created_at' => $quand ?? now()->subDays(3)])->save();
    }
}
