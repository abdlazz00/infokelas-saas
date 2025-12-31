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
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name'); // Algoritma
            $table->string('lecturer'); // Bu Susi
            $table->timestamps();
        });

        // 2. Tabel Jadwal
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete(); // Relasi ke Subject
            $table->integer('day_of_week'); // 1=Senin, 7=Minggu
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable(); // Opsional
            $table->timestamps();
        });

        // 3. Tabel Materi
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete(); // Opsional link ke matkul
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable(); // PDF/PPT
            $table->string('link_url')->nullable(); // Gdrive/Youtube
            $table->timestamps();
        });

        // 4. Tabel Tugas
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Tabel WA Group
        Schema::create('wa_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name'); // Inputan Dosen
            $table->string('jid')->nullable(); // Inputan Super Admin
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
