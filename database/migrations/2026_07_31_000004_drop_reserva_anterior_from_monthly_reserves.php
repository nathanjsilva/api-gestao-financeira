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
        Schema::table('monthly_reserves', function (Blueprint $table) {
            $table->dropColumn('reserva_anterior');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_reserves', function (Blueprint $table) {
            $table->decimal('reserva_anterior', 12, 2)->default(0)->after('competency');
        });
    }
};
