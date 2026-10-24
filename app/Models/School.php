<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    public const DEFAULT_PICS = [
        'AZRIEL',
        'WENDY',
        'ADIL',
        'AYU',
        'DWI',
        'IKHSAN dan TRI',
        'INDRA',
        'RAHAYU',
        'MIFTAH',
        'RIVALDI',
        'YUPITA',
    ];

    public static function normalizePicName(?string $pic): ?string
    {
        if ($pic === null || trim($pic) === '') {
            return null;
        }
        return trim(preg_replace('/^PIC\s*[:\-]?\s*/i', '', trim($pic)));
    }

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
