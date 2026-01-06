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
        Schema::create('class_students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['classroom_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_student_pivot');
    }
};
