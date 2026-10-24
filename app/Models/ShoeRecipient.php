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
    ];

    public function scopeAnomaly($query)
    {
        return $query->where(function ($q) {
            $q->where('ukuran_sepatu', '>', 46)
              ->orWhere('ukuran_sepatu', '<', 26)
              ->orWhereNull('ukuran_sepatu');
        });
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
