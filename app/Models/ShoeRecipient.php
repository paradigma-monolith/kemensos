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

    protected $appends = [
        'is_anomaly',
        'anomaly_reason',
        'jenjang',
        'jenjang_label',
    ];

    public function scopeAnomaly($query)
    {
        return $query->where(function ($q) {
            $q->where('ukuran_sepatu', '>', 46)
              ->orWhere('ukuran_sepatu', '<', 26)
              ->orWhereNull('ukuran_sepatu');
        });
    }

    public function scopeJenjang($query, $jenjang)
    {
        if (empty($jenjang)) {
            return $query;
        }

        $j = strtoupper(trim($jenjang));
        return match ($j) {
            'SRD' => $query->where('sheet_source', 'like', '%SRD%'),
            'SRMP' => $query->where('sheet_source', 'like', '%SRMP%'),
            'SRMA' => $query->where('sheet_source', 'like', '%SRMA%'),
            'STAFF', 'GURU_TENDIK' => $query->where(function ($q) {
                $q->where('sheet_source', 'not like', '%SRD%')
                  ->where('sheet_source', 'not like', '%SRMP%')
                  ->where('sheet_source', 'not like', '%SRMA%');
            }),
            default => $query->where('sheet_source', 'like', "%{$jenjang}%"),
        };
    }

    public function getJenjangAttribute(): string
    {
        $sheet = strtoupper((string)($this->sheet_source ?? ''));
        if (str_contains($sheet, 'SRD')) {
            return 'SRD';
        }
        if (str_contains($sheet, 'SRMP')) {
            return 'SRMP';
        }
        if (str_contains($sheet, 'SRMA')) {
            return 'SRMA';
        }
        if (str_contains($sheet, 'GURU') || $this->kategori === 'guru') {
            return 'GURU';
        }
        if (str_contains($sheet, 'TENDIK') || $this->kategori === 'tendik') {
            return 'TENDIK';
        }
        return 'STAFF';
    }

    public function getJenjangLabelAttribute(): string
    {
        return match ($this->jenjang) {
            'SRD' => 'SRD (Sekolah Rakyat Dasar)',
            'SRMP' => 'SRMP (Sekolah Rakyat Menengah Pertama)',
            'SRMA' => 'SRMA (Sekolah Rakyat Menengah Atas)',
            'GURU' => 'Guru',
            'TENDIK' => 'Tenaga Kependidikan',
            default => 'Guru & Tendik',
        };
    }

    public function getIsAnomalyAttribute(): bool
    {
        if ($this->ukuran_sepatu === null) return true;
        if ($this->ukuran_sepatu > 46 || $this->ukuran_sepatu < 26) return true;
        return false;
    }

    public function getAnomalyReasonAttribute(): ?string
    {
        if ($this->ukuran_sepatu === null) {
            return 'Ukuran sepatu kosong / belum terisi';
        }
        if ($this->ukuran_sepatu > 100) {
            return "Ukuran sepatu ekstrem ({$this->ukuran_sepatu} > 100): kemungkinan terisi nilai milimeter atau kode";
        }
        if ($this->ukuran_sepatu > 46) {
            return "Ukuran sepatu {$this->ukuran_sepatu} > 46: melebihi batas standar maksimal (26–46)";
        }
        if ($this->ukuran_sepatu < 26) {
            return "Ukuran sepatu {$this->ukuran_sepatu} < 26: di bawah batas standar minimal (26–46)";
        }
        return null;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ShoeImportBatch::class, 'batch_id');
    }
}
