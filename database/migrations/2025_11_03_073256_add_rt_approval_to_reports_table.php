<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('approved_by_rt')->nullable()->after('assigned_to')->constrained('users')->onDelete('set null');
            $table->timestamp('rt_approved_at')->nullable()->after('approved_by_rt');
            $table->text('rt_notes')->nullable()->after('rt_approved_at');
            $table->boolean('rt_recommended')->default(false)->after('rt_notes'); // RT merekomendasikan ke admin
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['approved_by_rt']);
            $table->dropColumn(['approved_by_rt', 'rt_approved_at', 'rt_notes', 'rt_recommended']);
        });
    }
};