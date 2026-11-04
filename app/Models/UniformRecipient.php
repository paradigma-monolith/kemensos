<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniformRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'batch_id',
        'sheet_name',
        'nomor_urut',
        'nama_lengkap',
        'jenis_kelamin',
        'jenjang',
        'status',
        'lingkar_badan_cm',
        'panjang_badan_cm',
        'lingkar_pinggang_cm',
        'panjang_celana_cm',
        'panjang_pesak_cm',
        'ukuran_sepatu_pdh',
        'konversi_vendor',
    ];

    protected $casts = [
        'nomor_urut' => 'integer',
        'lingkar_badan_cm' => 'float',
        'panjang_badan_cm' => 'float',
        'lingkar_pinggang_cm' => 'float',
        'panjang_celana_cm' => 'float',
        'panjang_pesak_cm' => 'float',
        'ukuran_sepatu_pdh' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UniformImportBatch::class, 'batch_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}

