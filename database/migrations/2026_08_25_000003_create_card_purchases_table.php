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
        Schema::create('card_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained('cards')->restrictOnDelete();
            $table->foreignId('card_category_id')->constrained('card_categories')->restrictOnDelete();
            $table->string('description');
            $table->decimal('total_amount', 12, 2);
            $table->date('purchase_date');
            $table->char('reference_competency', 7);
            $table->enum('payment_type', ['cash', 'installment']);
            $table->unsignedSmallInteger('installments_total');
            $table->unsignedSmallInteger('starting_installment_number');
            $table->timestamps();

            $table->index(['user_id', 'reference_competency']);
            $table->index('card_id');
            $table->index('card_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_purchases');
    }
};
