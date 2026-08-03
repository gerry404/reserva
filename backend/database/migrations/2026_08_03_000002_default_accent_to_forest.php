<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La couleur d'accent par défaut passe au vert du produit.
 *
 * La colonne portait `#6366f1`, l'indigo de l'identité précédente. Le
 * changement de palette a réécrit les composants mais pas ce défaut : chaque
 * commerce créé depuis naissait en violet, et sa page publique sortait de la
 * charte dès la première visite, sans que personne n'ait choisi cette couleur.
 *
 * Les lignes encore sur l'un des deux anciens défauts sont reprises. Celles
 * dont le commerçant a explicitement choisi une teinte du nuancier ne sont pas
 * touchées : son choix lui appartient, même si c'est un violet.
 */
return new class extends Migration
{
    /** Le vert du produit, forest 600 dans src/design/tokens.js. */
    private const FOREST = '#14603C';

    /** Les défauts successifs de l'ancienne identité, jamais choisis par personne. */
    private const ANCIENS = ['#6366f1', '#8b5cf6'];

    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('accent_color', 7)->default(self::FOREST)->change();
        });

        DB::table('businesses')
            ->whereIn(DB::raw('LOWER(accent_color)'), self::ANCIENS)
            ->update(['accent_color' => self::FOREST]);
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('accent_color', 7)->default('#6366f1')->change();
        });
    }
};
