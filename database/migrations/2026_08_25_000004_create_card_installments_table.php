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
        Schema::create('card_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_purchase_id')->constrained('card_purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->foreignId('card_category_id')->constrained('card_categories')->cascadeOnDelete();
            $table->enum('payment_type', ['cash', 'installment']);
            $table->unsignedSmallInteger('installment_number');
            $table->char('competency', 7);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['card_purchase_id', 'installment_number']);
            $table->index(['user_id', 'competency']);
            $table->index(['user_id', 'competency', 'card_id']);
            $table->index(['user_id', 'competency', 'card_category_id']);
            $table->index(['card_id', 'competency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_installments');
    }
};
