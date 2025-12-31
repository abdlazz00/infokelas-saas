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
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete(); // Pemilik Kelas
            $table->string('code')->unique(); // Join Code (e.g., XY7A9)
            $table->string('name'); // Nama Kelas (e.g., 3SIKA)
            $table->string('description')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'inactive'])->default('inactive');
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Paket Langganan
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bulanan, Semesteran
            $table->integer('duration_days'); // 30, 180
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Tabel Voucher (Sistem Diskon)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // MERDEKA45
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('amount', 12, 2); // Bisa nominal (10000) atau persen (10)
            $table->dateTime('expired_at');
            $table->integer('limit_per_user')->default(1); // Default 1x per user
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Tabel Akun Bank (Tujuan Transfer)
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name'); // BCA, Mandiri
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Tabel Transaksi
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Siapa yang bayar (Dosen)
            $table->foreignId('package_id')->constrained('packages');
            $table->foreignId('classroom_id')->constrained('classrooms'); // Untuk kelas mana?

            // Info Pembayaran
            $table->string('voucher_code')->nullable(); // Simpan kode voucher yg dipakai
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2); // Harga asli
            $table->decimal('final_amount', 12, 2); // Harga setelah diskon

            $table->string('proof_of_payment')->nullable(); // Gambar bukti tf
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable(); // Alasan reject dll
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
