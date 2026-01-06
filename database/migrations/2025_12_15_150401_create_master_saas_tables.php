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
        // 1. Tabel Kelas
        Schema::create('classrooms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('university')->nullable();
            $table->string('major')->nullable();
            $table->string('semester')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'inactive'])->default('inactive');
            $table->dateTime('expired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Paket Langganan
        Schema::create('packages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->integer('duration_days');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Tabel Voucher (Sistem Diskon)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('amount', 12, 2);
            $table->dateTime('expired_at');
            $table->integer('limit_per_user')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Tabel Akun Bank (Tujuan Transfer)
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Tabel Transaksi
        Schema::create('transactions', function (Blueprint $table) {
            $table->Ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users');
            $table->foreignUlid('package_id')->constrained('packages');
            $table->foreignUlid('classroom_id')->constrained('classrooms');

            // Info Pembayaran
            $table->string('voucher_code')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('final_amount', 12, 2);

            $table->string('proof_of_payment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('transactions');
    }
};
