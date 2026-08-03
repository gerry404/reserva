<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les compteurs par statut de la liste des réservations.
 *
 * Ils étaient calculés côté client à partir de la page reçue. Filtrer sur
 * « confirmé » vidait donc la liste de tout le reste, et le commerçant lisait
 * « 0 en attente » alors qu'il en avait dix : exactement le chiffre sur lequel
 * il s'appuie pour décider de changer de filtre.
 *
 * Deux propriétés à tenir, et elles tirent en sens opposé : les compteurs
 * ignorent le filtre de statut, mais suivent les autres filtres.
 */
class BookingCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_compteurs_ne_bougent_pas_avec_le_filtre_de_statut(): void
    {
        $user = $this->merchant();

        $this->seed_bookings($user->business, [
            Booking::STATUS_PENDING   => 3,
            Booking::STATUS_CONFIRMED => 2,
            Booking::STATUS_COMPLETED => 4,
        ]);

        $attendu = [
            'all'       => 9,
            'pending'   => 3,
            'confirmed' => 2,
            'completed' => 4,
            'cancelled' => 0,
            'no_show'   => 0,
        ];

        foreach (['', 'pending', 'confirmed', 'cancelled'] as $filtre) {
            $url = '/api/bookings' . ($filtre ? "?status={$filtre}" : '');

            $this->actingAs($user)->getJson($url)
                ->assertOk()
                ->assertJsonPath('meta.counts.all', $attendu['all'])
                ->assertJsonPath('meta.counts.pending', $attendu['pending'])
                ->assertJsonPath('meta.counts.confirmed', $attendu['confirmed'])
                ->assertJsonPath('meta.counts.cancelled', $attendu['cancelled']);
        }
    }

    public function test_la_liste_elle_meme_reste_filtree(): void
    {
        $user = $this->merchant();

        $this->seed_bookings($user->business, [
            Booking::STATUS_PENDING   => 3,
            Booking::STATUS_CONFIRMED => 2,
        ]);

        $this->actingAs($user)->getJson('/api/bookings?status=confirmed')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Le filtre de date, lui, doit bien resserrer les compteurs.
     *
     * Sans cette distinction, « ignorer le filtre » se serait transformé en
     * « ignorer tous les filtres », et les pastilles auraient affiché des
     * totaux sans rapport avec la liste sous les yeux.
     */
    public function test_les_compteurs_suivent_les_autres_filtres(): void
    {
        $user = $this->merchant();
        $jour = now()->addDays(3)->toDateString();

        $this->seed_bookings($user->business, [Booking::STATUS_PENDING => 2], $jour);
        $this->seed_bookings($user->business, [Booking::STATUS_PENDING => 5], now()->addDays(10)->toDateString());

        $this->actingAs($user)->getJson("/api/bookings?date={$jour}")
            ->assertOk()
            ->assertJsonPath('meta.counts.all', 2)
            ->assertJsonPath('meta.counts.pending', 2);
    }

    /** Un statut absent vaut zéro, jamais une clé manquante. */
    public function test_un_statut_absent_vaut_zero(): void
    {
        $user = $this->merchant();

        $this->actingAs($user)->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonPath('meta.counts.all', 0)
            ->assertJsonPath('meta.counts.no_show', 0);
    }

    /** @param  array<string, int>  $repartition */
    private function seed_bookings(Business $business, array $repartition, ?string $date = null): void
    {
        foreach ($repartition as $statut => $combien) {
            Booking::factory()
                ->count($combien)
                ->for($business)
                ->state(fn () => ['status' => $statut] + ($date ? ['date' => $date] : []))
                ->create();
        }
    }

    private function merchant(): User
    {
        $user = User::factory()->create();
        Business::factory()->for($user, 'user')->create();

        return $user->fresh();
    }
}
