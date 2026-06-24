<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LotImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'original_path',
        'optimized_path',
        'thumbnail_path',
        'order',
        'original_size',
        'optimized_size',
        'thumbnail_size',
        'is_primary',
        'scheduled_deletion_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'scheduled_deletion_at' => 'datetime',
        ];
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function getOptimizedUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->optimized_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumbnail_path);
    }

    public function delete()
    {
        // Delete physical files from public disk
        if ($this->original_path) {
            Storage::disk('public')->delete($this->original_path);
        }
        if ($this->optimized_path) {
            Storage::disk('public')->delete($this->optimized_path);
        }
        if ($this->thumbnail_path) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }

        return parent::delete();
    }

    public function scopeScheduledForDeletion($query)
    {
        return $query->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now());
    }
}
