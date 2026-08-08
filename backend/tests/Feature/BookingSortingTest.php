<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le tri de la liste des réservations.
 *
 * La colonne demandée vient du client. Passée telle quelle à `orderBy`, elle
 * ouvrirait une injection : ces tests fixent la liste fermée et vérifient
 * qu'une colonne hors liste est refusée plutôt qu'ignorée en silence.
 */
class BookingSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_tri_par_montant_ordonne_toute_la_table(): void
    {
        $user = $this->merchant();

        foreach ([5000, 15000, 3000, 9000] as $prix) {
            Booking::factory()->for($user->business)->create(['price' => $prix]);
        }

        $montants = $this->actingAs($user)
            ->getJson('/api/bookings?sort=price&direction=desc')
            ->assertOk()
            ->json('data.*.price');

        $this->assertSame([15000, 9000, 5000, 3000], array_map('intval', $montants));
    }

    public function test_le_tri_par_nom_respecte_la_direction(): void
    {
        $user = $this->merchant();

        foreach (['Zoé', 'Amina', 'Marc'] as $nom) {
            Booking::factory()->for($user->business)->create(['customer_name' => $nom]);
        }

        $croissant = $this->actingAs($user)
            ->getJson('/api/bookings?sort=customer_name&direction=asc')
            ->json('data.*.customer_name');

        $decroissant = $this->actingAs($user)
            ->getJson('/api/bookings?sort=customer_name&direction=desc')
            ->json('data.*.customer_name');

        $this->assertSame('Amina', $croissant[0]);
        $this->assertSame(array_reverse($croissant), $decroissant);
    }

    /** Une colonne hors liste est refusée, pas ignorée. */
    public function test_une_colonne_inconnue_est_rejetee(): void
    {
        $user = $this->merchant();

        $this->actingAs($user)
            ->getJson('/api/bookings?sort=customer_phone')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort');

        $this->actingAs($user)
            ->getJson('/api/bookings?sort=price&direction=; DROP TABLE bookings')
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');
    }

    /**
     * À montant égal, l'ordre ne varie pas d'une requête à l'autre.
     *
     * Sans départage sur l'identifiant, le moteur ne garantit rien : une même
     * page pouvait rendre deux résultats différents d'un chargement à l'autre,
     * et une ligne apparaître sur deux pages successives.
     *
     * L'égalité est cherchée sur le prix, et non sur la date : deux
     * réservations ne peuvent pas partager un créneau, la contrainte d'unicité
     * sur `slot_key` l'interdit — c'est la protection contre le double
     * réservation, et elle rend ce cas impossible à construire.
     */
    public function test_l_ordre_est_stable_a_egalite(): void
    {
        $user = $this->merchant();

        Booking::factory()->count(6)->for($user->business)->create(['price' => 5000]);

        $premier = $this->actingAs($user)->getJson('/api/bookings?sort=price&direction=asc')->json('data.*.id');
        $second  = $this->actingAs($user)->getJson('/api/bookings?sort=price&direction=asc')->json('data.*.id');

        $this->assertSame($premier, $second);
        $this->assertCount(6, $premier);
    }

    private function merchant(): User
    {
        $user = User::factory()->create();
        Business::factory()->for($user, 'user')->create();

        return $user->fresh();
    }
}
