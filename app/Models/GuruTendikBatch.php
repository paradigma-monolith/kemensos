<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuruTendikBatch extends Model
{
    use HasFactory;

    protected $table = 'guru_tendik_batches';

    protected $fillable = [
        'file_name',
        'file_path',
        'pic_nama',
        'total_laki',
        'total_perempuan',
        'total_data',
        'total_sekolah',
        'imported_by',
        'notes',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(GuruTendikRecord::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

