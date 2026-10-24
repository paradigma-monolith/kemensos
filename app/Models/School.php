<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_sekolah',
        'kota',
        'pic_nama',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'tanggal_konfirmasi',
        'status_konfirmasi',
    ];

    public function importBatches(): HasMany
    {
        return $this->hasMany(ShoeImportBatch::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ShoeRecipient::class);
    }
}
