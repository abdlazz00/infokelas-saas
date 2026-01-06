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
        // 1. Tabel Mata Kuliah (Master Data per Kelas)
        Schema::create('subjects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('lecturer');
            $table->integer('semester')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Jadwal
        Schema::create('schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Materi
        Schema::create('materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignUlid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('wa_group_id')->nullable();

            $table->timestamps();
        });

        // 4. Tabel Tugas
        Schema::create('assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignUlid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url')->nullable();
            $table->dateTime('deadline');
            $table->boolean('is_active')->default(true);
            $table->string('wa_group_id')->nullable();
            $table->timestamps();
        });

        // 5. Tabel WA Group
        Schema::create('wa_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name');
            $table->string('jid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('wa_groups');
    }
};
