<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La couleur par défaut d'une prestation passe au vert du produit.
 *
 * Même défaut que celui déjà corrigé sur `businesses.accent_color`, et raté au
 * premier passage : la colonne portait encore `#6366f1`, l'indigo de l'identité
 * précédente. Toute prestation créée sans couleur explicite naissait donc en
 * violet, et le tableau de bord affichait des pastilles et des barres de durée
 * hors palette alors que le reste de l'interface était passé au vert.
 *
 * Les lignes encore sur cet ancien défaut sont reprises. Celles dont le
 * commerçant a choisi une teinte du nuancier ne sont pas touchées : son choix
 * lui appartient.
 */
return new class extends Migration
{
    /** forest 600, dans src/design/tokens.js. */
    private const FOREST = '#14603C';

    private const ANCIEN = '#6366f1';

    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('color', 7)->default(self::FOREST)->change();
        });

        DB::table('services')
            ->whereRaw('LOWER(color) = ?', [self::ANCIEN])
            ->update(['color' => self::FOREST]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('color', 7)->default(self::ANCIEN)->change();
        });
    }
};
