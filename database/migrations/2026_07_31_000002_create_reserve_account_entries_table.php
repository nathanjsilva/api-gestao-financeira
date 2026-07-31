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
        Schema::create('reserve_account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserve_account_id')->constrained()->cascadeOnDelete();
            $table->char('competency', 7);
            $table->decimal('balance', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['reserve_account_id', 'competency']);
            $table->index(['reserve_account_id', 'competency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserve_account_entries');
    }
};
