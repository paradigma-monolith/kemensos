<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniformImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'school_name',
        'file_name',
        'file_path',
        'pic_nama',
        'total_siswa',
        'total_kepsek',
        'total_guru_lama',
        'total_guru_baru',
        'total_tendik_lama',
        'total_tendik_baru',
        'total_satpam',
        'total_kebersihan',
        'total_dapur',
        'total_rows',
        'recap_data',
        'imported_by',
        'notes',
    ];

    protected $casts = [
        'recap_data' => 'array',
        'total_siswa' => 'integer',
        'total_kepsek' => 'integer',
        'total_guru_lama' => 'integer',
        'total_guru_baru' => 'integer',
        'total_tendik_lama' => 'integer',
        'total_tendik_baru' => 'integer',
        'total_satpam' => 'integer',
        'total_kebersihan' => 'integer',
        'total_dapur' => 'integer',
        'total_rows' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(UniformRecipient::class, 'batch_id');
    }
}
