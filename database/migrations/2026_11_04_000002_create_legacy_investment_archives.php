<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('monthly_reserves', 'investimento') || ! Schema::hasColumn('monthly_reserve_entries', 'amount')) {
            throw new RuntimeException('As colunas legadas de investimento esperadas não foram encontradas.');
        }

        Schema::create('legacy_monthly_reserve_archives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->char('competency', 7);
            $table->decimal('investimento', 12, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        Schema::create('legacy_monthly_reserve_entry_archives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->unique();
            $table->unsignedBigInteger('monthly_reserve_id');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        Schema::create('legacy_investment_migration_state', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->timestamp('completed_at');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_investment_migration_state');
        Schema::dropIfExists('legacy_monthly_reserve_entry_archives');
        Schema::dropIfExists('legacy_monthly_reserve_archives');
    }
};
