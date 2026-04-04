<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tx_ref')->unique();
            $table->string('flw_ref')->nullable();
            $table->string('plan');                     // pro, business
            $table->string('billing_cycle');             // monthly, yearly
            $table->integer('amount');
            $table->string('currency', 10)->default('XAF');
            $table->string('payment_method')->nullable(); // mobilemoneyghana, mobilemoneycm, card...
            $table->string('status')->default('pending'); // pending, successful, failed, cancelled
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
