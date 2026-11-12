<?php

namespace App\Models;

use Database\Factories\ProgressPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $progress_note_id
 * @property int $tracker_target_id
 * @property string $file_path
 * @property string $mime_type
 * @property string $file_name
 * @property string|null $caption
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgressNote $note
 * @property-read TrackerTarget $target
 */
#[Fillable([
    'progress_note_id',
    'tracker_target_id',
    'file_path',
    'mime_type',
    'file_name',
    'caption',
    'sort_order',
])]
class ProgressPhoto extends Model
{
    /** @use HasFactory<ProgressPhotoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProgressNote, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(ProgressNote::class, 'progress_note_id');
    }

    /**
     * @return BelongsTo<TrackerTarget, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(TrackerTarget::class, 'tracker_target_id');
    }

    /**
     * Resolve the public URL for this photo.
     */
    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
