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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah')->unique();
            $table->string('kota')->nullable();
            $table->string('pic_nama')->nullable();
            $table->string('kepala_sekolah_nama')->nullable();
            $table->string('kepala_sekolah_nip')->nullable();
            $table->string('tanggal_konfirmasi')->nullable();
            $table->string('status_konfirmasi')->default('terkonfirmasi');
            $table->timestamps();
        });

        Schema::create('shoe_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->unsignedInteger('total_siswa')->default(0);
            $table->unsignedInteger('total_guru')->default(0);
            $table->unsignedInteger('total_tendik')->default(0);
            $table->unsignedInteger('total_sepatu')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shoe_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('shoe_import_batches')->cascadeOnDelete();
            $table->enum('kategori', ['siswa', 'guru', 'tendik'])->index();
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P'])->index();
            $table->string('jabatan')->default('Siswa');
            $table->decimal('panjang_kaki', 8, 2)->nullable();
            $table->unsignedInteger('ukuran_sepatu')->index();
            $table->string('sheet_source')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shoe_recipients');
        Schema::dropIfExists('shoe_import_batches');
        Schema::dropIfExists('schools');
    }
};
