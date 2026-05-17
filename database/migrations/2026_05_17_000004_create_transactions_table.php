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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['income', 'expense']);
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->char('competency', 7);
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'competency']);
            $table->index(['user_id', 'competency', 'type']);
            $table->index(['user_id', 'competency', 'status']);
            $table->index(['category_id', 'competency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
