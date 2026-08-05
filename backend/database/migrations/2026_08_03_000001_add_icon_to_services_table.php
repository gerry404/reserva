<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'icône choisie pour une prestation.
 *
 * Nullable et sans valeur par défaut : l'absence de choix n'est pas un choix.
 * Le client déduit alors l'icône du nom du service, et un défaut écrit en base
 * empêcherait cette déduction de s'appliquer aux services déjà créés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('icon', 32)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
