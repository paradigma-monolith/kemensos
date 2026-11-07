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
        Schema::create('guru_tendik_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('pic_nama')->nullable();
            $table->unsignedInteger('total_laki')->default(0);
            $table->unsignedInteger('total_perempuan')->default(0);
            $table->unsignedInteger('total_data')->default(0);
            $table->unsignedInteger('total_sekolah')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_tendik_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('guru_tendik_batches')->cascadeOnDelete();
            $table->unsignedInteger('no')->nullable();
            $table->string('nama_lengkap')->index();
            $table->string('jenis_kelamin')->index();
            $table->string('status')->index();
            $table->string('jabatan')->index();
            $table->string('sekolah')->index();
            $table->string('sheet_name')->nullable();
            $table->string('pic_nama')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_tendik_records');
        Schema::dropIfExists('guru_tendik_batches');
    }
};

