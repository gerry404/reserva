<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 10)->default('CM');
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('working_hours')->nullable();
            $table->unsignedSmallInteger('slot_duration')->default(30)->comment('minutes');
            $table->unsignedSmallInteger('booking_notice')->default(60)->comment('minutes before booking');
            $table->boolean('notifications_whatsapp')->default(true);
            $table->boolean('notifications_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('accent_color', 7)->default('#6366f1');
            $table->timestamps();

            $table->index('slug');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
