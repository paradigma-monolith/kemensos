<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoeRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'batch_id',
        'kategori',
        'nomor_urut',
        'nama_lengkap',
        'jenis_kelamin',
        'jabatan',
        'panjang_kaki',
        'ukuran_sepatu',
        'sheet_source',
        'catatan',
    ];

    protected $casts = [
        'nomor_urut' => 'integer',
        'panjang_kaki' => 'float',
        'ukuran_sepatu' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ShoeImportBatch::class, 'batch_id');
    }
}
