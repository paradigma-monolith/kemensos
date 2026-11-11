<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruTendikRecord extends Model
{
    use HasFactory;

    protected $table = 'guru_tendik_records';

    protected $fillable = [
        'batch_id',
        'no',
        'nama_lengkap',
        'jenis_kelamin',
        'status',
        'jabatan',
        'sekolah',
        'sheet_name',
        'pic_nama',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GuruTendikBatch::class, 'batch_id');
    }

    /**
     * Normalize gender values to standard "Laki-laki" or "Perempuan".
     */
    public static function normalizeGender(?string $value, string $default = 'Laki-laki'): string
    {
        $raw = strtoupper(trim((string) $value));
        if ($raw === '') {
            return $default;
        }

        if (
            in_array($raw, ['P', 'PR', 'PEREMPUAN', 'WANITA', 'FEMALE', 'W'], true) ||
            str_contains($raw, 'PEREMPUAN') ||
            str_contains($raw, 'WANITA')
        ) {
            return 'Perempuan';
        }

        if (
            in_array($raw, ['L', 'LK', 'LAKI-LAKI', 'LAKI LAKI', 'PRIA', 'MALE'], true) ||
            str_contains($raw, 'LAKI') ||
            str_contains($raw, 'PRIA')
        ) {
            return 'Laki-laki';
        }

        return $default;
    }
}

