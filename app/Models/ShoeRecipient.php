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
              ->orWhereNull('ukuran_sepatu')
              ->orWhere('panjang_kaki', '>', 50)
              ->orWhere(function ($sub) {
                  $sub->where('panjang_kaki', '>', 30)->where('ukuran_sepatu', '<', 30);
              });
        });
    }

    public function getIsAnomalyAttribute(): bool
    {
        if ($this->ukuran_sepatu === null) return true;
        if ($this->ukuran_sepatu > 46 || $this->ukuran_sepatu < 26) return true;
        if ($this->panjang_kaki !== null && $this->panjang_kaki > 50) return true;
        if ($this->panjang_kaki !== null && $this->panjang_kaki > 30 && $this->ukuran_sepatu < 30) return true;
        return false;
    }

    public function getAnomalyReasonAttribute(): ?string
    {
        if ($this->ukuran_sepatu === null) {
            return 'Ukuran sepatu kosong / belum terisi';
        }
        if ($this->ukuran_sepatu > 100) {
            return "Ukuran sepatu ekstrem ({$this->ukuran_sepatu} > 100): kemungkinan tertukar dengan panjang kaki dalam milimeter (mm) atau kode";
        }
        if ($this->ukuran_sepatu > 46) {
            return "Ukuran sepatu {$this->ukuran_sepatu} > 46: melebihi batas standar maksimal (26–46)";
        }
        if ($this->ukuran_sepatu < 26) {
            return "Ukuran sepatu {$this->ukuran_sepatu} < 26: di bawah batas standar minimal (26–46)";
        }
        if ($this->panjang_kaki !== null && $this->panjang_kaki > 50) {
            return "Panjang kaki {$this->panjang_kaki} cm > 50 cm: tidak wajar (kemungkinan satuan milimeter)";
        }
        if ($this->panjang_kaki !== null && $this->panjang_kaki > 30 && $this->ukuran_sepatu < 30) {
            return 'Nilai panjang kaki dan ukuran sepatu terindikasi tertukar';
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
