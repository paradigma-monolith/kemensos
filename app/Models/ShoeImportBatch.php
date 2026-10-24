<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoeImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'file_name',
        'file_path',
        'total_siswa',
        'total_guru',
        'total_tendik',
        'total_sepatu',
        'imported_by',
        'notes',
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
        return $this->hasMany(ShoeRecipient::class, 'batch_id');
    }
}
