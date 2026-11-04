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
        Schema::create('uniform_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('school_name')->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('pic_nama')->nullable();
            $table->unsignedInteger('total_siswa')->default(0);
            $table->unsignedInteger('total_kepsek')->default(0);
            $table->unsignedInteger('total_guru_lama')->default(0);
            $table->unsignedInteger('total_guru_baru')->default(0);
            $table->unsignedInteger('total_tendik_lama')->default(0);
            $table->unsignedInteger('total_tendik_baru')->default(0);
            $table->unsignedInteger('total_satpam')->default(0);
            $table->unsignedInteger('total_kebersihan')->default(0);
            $table->unsignedInteger('total_dapur')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->json('recap_data')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('uniform_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('batch_id')->constrained('uniform_import_batches')->cascadeOnDelete();
            $table->string('sheet_name', 50)->index();
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->string('nama_lengkap');
            $table->string('jenis_kelamin', 10)->nullable()->index();
            $table->string('jenjang', 50)->nullable();
            $table->string('status', 100)->nullable();
            $table->decimal('lingkar_badan_cm', 8, 2)->nullable();
            $table->decimal('panjang_badan_cm', 8, 2)->nullable();
            $table->decimal('lingkar_pinggang_cm', 8, 2)->nullable();
            $table->decimal('panjang_celana_cm', 8, 2)->nullable();
            $table->decimal('panjang_pesak_cm', 8, 2)->nullable();
            $table->unsignedInteger('ukuran_sepatu_pdh')->nullable();
            $table->string('konversi_vendor', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_recipients');
        Schema::dropIfExists('uniform_import_batches');
    }
};
