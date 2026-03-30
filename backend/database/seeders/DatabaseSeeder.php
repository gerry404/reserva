<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo merchant
        $user = User::create([
            'name' => 'Marie Nguema',
            'email' => 'demo@reserva.cm',
            'password' => Hash::make('password'),
            'phone' => '+237612345678',
            'plan' => 'pro',
        ]);

        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Salon Élégance Douala',
            'category' => 'Salon de coiffure',
            'city' => 'Douala',
            'country' => 'CM',
            'phone' => '+237612345678',
            'whatsapp' => '+237612345678',
            'description' => 'Votre salon de coiffure et beauté au cœur de Douala. Spécialiste en tresses, soins capillaires et maquillage.',
            'working_hours' => [
                'lundi' => ['is_open' => true, 'open' => '08:00', 'close' => '19:00'],
                'mardi' => ['is_open' => true, 'open' => '08:00', 'close' => '19:00'],
                'mercredi' => ['is_open' => true, 'open' => '08:00', 'close' => '19:00'],
                'jeudi' => ['is_open' => true, 'open' => '08:00', 'close' => '19:00'],
                'vendredi' => ['is_open' => true, 'open' => '08:00', 'close' => '19:00'],
                'samedi' => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
                'dimanche' => ['is_open' => false, 'open' => '09:00', 'close' => '13:00'],
            ],
            'slot_duration' => 30,
            'booking_notice' => 60,
            'notifications_whatsapp' => true,
            'is_active' => true,
            'accent_color' => '#8b5cf6',
        ]);

        $services = [
            ['name' => 'Coiffure naturelle', 'duration' => 60, 'price' => 5000, 'color' => '#8b5cf6'],
            ['name' => 'Tresses ', 'duration' => 180, 'price' => 15000, 'color' => '#ec4899'],
            ['name' => 'Défrisage', 'duration' => 90, 'price' => 8000, 'color' => '#f59e0b'],
            ['name' => 'Soin capillaire', 'duration' => 45, 'price' => 4000, 'color' => '#10b981'],
            ['name' => 'Maquillage', 'duration' => 60, 'price' => 10000, 'color' => '#ef4444'],
            ['name' => 'Manucure', 'duration' => 30, 'price' => 3000, 'color' => '#06b6d4'],
        ];

        foreach ($services as $svc) {
            Service::create(array_merge($svc, ['business_id' => $business->id, 'is_active' => true]));
        }

        // Sample bookings
        $names = ['Amina Diallo', 'Fatima Coulibaly', 'Sandra Mbarga', 'Rose Biya', 'Carine Nkondo', 'Sylvie Atangana'];
        $phones = ['+237690001122', '+237690003344', '+237690005566', '+237690007788', '+237690009900', '+237691112233'];
        $serviceIds = Service::where('business_id', $business->id)->pluck('id')->toArray();
        $statuses = ['confirmed', 'confirmed', 'confirmed', 'pending', 'completed', 'completed'];
        $times = ['09:00', '10:00', '11:00', '14:00', '15:30', '16:30'];

        for ($i = 0; $i < 6; $i++) {
            Booking::create([
                'business_id' => $business->id,
                'service_id' => $serviceIds[$i % count($serviceIds)],
                'customer_name' => $names[$i],
                'customer_phone' => $phones[$i],
                'date' => now()->addDays($i - 2)->toDateString(),
                'time_slot' => $times[$i],
                'status' => $statuses[$i],
            ]);
        }

        $this->command->info('Demo data seeded! Email: demo@reserva.cm / Password: password');
    }
}
