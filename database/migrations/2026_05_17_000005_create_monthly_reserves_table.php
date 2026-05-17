<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_reserves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('competency', 7);
            $table->decimal('reserva_anterior', 12, 2)->default(0);
            $table->decimal('investimento', 12, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'competency']);
            $table->index(['user_id', 'competency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reserves');
    }
};
