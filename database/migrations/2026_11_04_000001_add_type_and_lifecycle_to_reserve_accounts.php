<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reserve_accounts', function (Blueprint $table): void {
            $table->string('type', 20)->default('reserve')->after('name');
            $table->boolean('archive_status_needs_review')->default(false)->after('active');
            $table->index(['user_id', 'type', 'active']);
        });

        DB::table('reserve_accounts')
            ->where('active', false)
            ->update(['archive_status_needs_review' => true]);

        Schema::create('reserve_account_status_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserve_account_id')->constrained()->cascadeOnDelete();
            $table->char('competency', 7);
            $table->boolean('active');
            $table->timestamps();

            $table->unique(['reserve_account_id', 'competency'], 'reserve_status_competency_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('reserve_accounts')->where('type', 'investment')->exists()) {
            throw new RuntimeException('Não é possível reverter a estrutura enquanto houver itens de investimento.');
        }

        if (DB::table('reserve_account_status_entries')->exists()) {
            throw new RuntimeException('Não é possível reverter a estrutura enquanto houver histórico de arquivamento.');
        }

        Schema::dropIfExists('reserve_account_status_entries');

        Schema::table('reserve_accounts', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'type', 'active']);
            $table->dropColumn(['type', 'archive_status_needs_review']);
        });
    }
};
