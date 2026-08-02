<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A demo salon with a believable week behind and ahead of it.
 *
 * Bookings are placed through AvailabilityService rather than written straight
 * to the table, so the seeded diary obeys the same rules as a real one: no
 * overlaps, nothing outside opening hours, nothing that runs past closing. A
 * seeder that can produce states the application refuses to create is a seeder
 * that hides bugs.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user     = $this->createMerchant();
        $business = $this->createBusiness($user);
        $services = $this->createServices($business);

        $this->createBookings($business, $services);

        $this->command->info('Données de démonstration créées.');
        $this->command->info('  Connexion : demo@nuvo.app / password');
        $this->command->info('  Page publique : ' . $business->public_url);
    }

    private function createMerchant(): User
    {
        return User::create([
            'name'            => 'Marie Nguema',
            'email'           => 'demo@nuvo.app',
            'password'        => Hash::make('password'),
            'phone'           => '+237612345678',
            'plan'            => User::PLAN_PRO,
            'plan_expires_at' => now()->addYear(),
        ]);
    }

    private function createBusiness(User $user): Business
    {
        return $user->business()->create([
            'name'        => 'Salon Élégance Douala',
            'slug'        => 'salon-elegance-douala',
            'category'    => 'Salon de coiffure',
            'city'        => 'Douala',
            'address'     => 'Akwa, Rue de la Liberté',
            'country'     => 'CM',
            'timezone'    => 'Africa/Douala',
            'currency'    => 'XAF',
            'phone'       => '+237612345678',
            'whatsapp'    => '+237612345678',
            'description' => 'Votre salon de coiffure et beauté au cœur de Douala. '
                           . 'Spécialiste en tresses, soins capillaires et maquillage.',
            'working_hours' => [
                'lundi'    => ['is_open' => true,  'open' => '08:00', 'close' => '19:00'],
                'mardi'    => ['is_open' => true,  'open' => '08:00', 'close' => '19:00'],
                'mercredi' => ['is_open' => true,  'open' => '08:00', 'close' => '19:00'],
                'jeudi'    => ['is_open' => true,  'open' => '08:00', 'close' => '19:00'],
                'vendredi' => ['is_open' => true,  'open' => '08:00', 'close' => '19:00'],
                'samedi'   => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
                'dimanche' => ['is_open' => false, 'open' => '09:00', 'close' => '13:00'],
            ],
            'slot_duration'          => 30,
            'booking_notice'         => 60,
            'notifications_whatsapp' => true,
            'notifications_email'    => true,
            'notifications_sms'      => false,
            'is_active'              => true,
            'accent_color'           => '#8b5cf6',
        ]);
    }

    /** @return array<int, Service> */
    private function createServices(Business $business): array
    {
        $catalogue = [
            ['name' => 'Coiffure naturelle', 'duration' => 60,  'price' => 5000,  'color' => '#8b5cf6'],
            ['name' => 'Tresses',            'duration' => 180, 'price' => 15000, 'color' => '#ec4899'],
            ['name' => 'Défrisage',          'duration' => 90,  'price' => 8000,  'color' => '#f59e0b'],
            ['name' => 'Soin capillaire',    'duration' => 45,  'price' => 4000,  'color' => '#10b981'],
            ['name' => 'Maquillage',         'duration' => 60,  'price' => 10000, 'color' => '#ef4444'],
            ['name' => 'Manucure',           'duration' => 30,  'price' => 3000,  'color' => '#06b6d4'],
        ];

        return array_map(
            fn (array $service) => $business->allServices()->create($service + ['is_active' => true]),
            $catalogue,
        );
    }

    /**
     * @param  array<int, Service>  $services
     */
    private function createBookings(Business $business, array $services): void
    {
        $customers = [
            ['Amina Diallo',      '+237690001122', 'amina@example.com'],
            ['Fatima Coulibaly',  '+237690003344', null],
            ['Sandra Mbarga',     '+237690005566', 'sandra@example.com'],
            ['Rose Biya',         '+237690007788', null],
            ['Carine Nkondo',     '+237690009900', 'carine@example.com'],
            ['Sylvie Atangana',   '+237691112233', null],
            ['Grace Etoundi',     '+237691114455', null],
            ['Bella Ngo',         '+237691116677', 'bella@example.com'],
        ];

        $availability = app(AvailabilityService::class);

        foreach ($customers as $index => [$name, $phone, $email]) {
            // Spread across last week and next week, so the dashboard has both
            // history to report on and upcoming work to show.
            $date    = now($business->timezone)->addDays($index - 3);
            $service = $services[$index % count($services)];

            // The engine only offers *future* slots, by design. Past history is
            // therefore placed by hand — one booking per day, so it cannot
            // overlap whatever else lands on that date.
            $slots = $date->isFuture()
                ? $availability->slotsFor($business, $service, $date->toDateString())
                : ['09:00'];

            if ($slots === []) {
                continue; // Closed that day, or nothing long enough left free.
            }

            Booking::create([
                'business_id'    => $business->id,
                'service_id'     => $service->id,
                'customer_name'  => $name,
                'customer_phone' => $phone,
                'customer_email' => $email,
                'date'           => $date->toDateString(),
                'time_slot'      => $slots[min($index, count($slots) - 1)],
                'duration'       => $service->duration,
                'price'          => $service->price,
                'status'         => $this->statusFor($date),
            ]);
        }
    }

    /** A past appointment is not still waiting on the merchant's decision. */

    private function statusFor(\Illuminate\Support\Carbon $date): string
    {
        return match (true) {
            $date->isPast()  => Booking::STATUS_COMPLETED,
            $date->isToday() => Booking::STATUS_CONFIRMED,
            default          => Booking::STATUS_PENDING,
        };
    }
}
