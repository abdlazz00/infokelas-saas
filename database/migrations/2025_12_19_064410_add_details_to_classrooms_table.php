<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('university')->nullable()->after('name');
            $table->string('major')->nullable()->after('university');
            $table->string('semester')->nullable()->after('major');

            $table->string('subscription_status')->default('inactive')->change();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['university', 'major', 'semester']);
            $table->string('subscription_status')->default('active')->change();
        });
    }
};
