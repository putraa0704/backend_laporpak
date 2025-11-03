<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Petugas yang ditugaskan
            $table->string('title');
            $table->text('complaint_description'); // Deskripsi Keluhan
            $table->text('location_description'); // Deskripsi Lokasi
            $table->date('report_date');
            $table->time('report_time');
            $table->string('photo')->nullable();
            $table->enum('status', ['pending', 'on_hold', 'in_progress', 'done'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};