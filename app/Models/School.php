<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Check whether the PIC filter sentinel represents schools without an assigned PIC.
     */
    public static function isEmptyPicFilter(?string $pic): bool
    {
        if ($pic === null) {
            return false;
        }
        return in_array(mb_strtoupper(trim($pic)), ['__NONE__', 'TANPA PIC', 'TANPA_PIC'], true);
    }

    /**
     * Scope schools matching a normalized PIC name (or unassigned PIC sentinel).
     */
    public function scopeByPic(Builder $query, ?string $pic): Builder
    {
        if ($pic === null || trim($pic) === '') {
            return $query;
        }

        if (self::isEmptyPicFilter($pic)) {
            return $query->where(function (Builder $q) {
                $q->whereNull('pic_nama')
                    ->orWhereRaw("TRIM(pic_nama) = ''");
            });
        }

        $normPic = self::normalizePicName($pic);
        if ($normPic === null || $normPic === '') {
            return $query;
        }

        $upperNorm = mb_strtoupper($normPic);

        return $query->where(function (Builder $q) use ($normPic, $upperNorm) {
            $q->where('pic_nama', $normPic)
                ->orWhere('pic_nama', 'PIC ' . $normPic)
                ->orWhereRaw('UPPER(TRIM(pic_nama)) = ?', [$upperNorm])
                ->orWhereRaw('UPPER(TRIM(pic_nama)) = ?', ['PIC ' . $upperNorm]);
        });
    }

    /**
     * Return sorted unique PIC names merging official defaults with database entries.
     *
     * @return array<int, string>
     */
    public static function allPicOptions(): array
    {
        $existingPics = self::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn ($p) => self::normalizePicName($p))
            ->filter()
            ->toArray();

        $allPics = array_values(array_unique(array_merge(self::DEFAULT_PICS, $existingPics)));
        sort($allPics);

        return $allPics;
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
