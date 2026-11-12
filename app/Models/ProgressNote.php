<?php

namespace App\Models;

use Database\Factories\ProgressNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tracker_target_id
 * @property Carbon $note_date
 * @property float $added_value
 * @property array<int, string>|null $completed_step_ids
 * @property array<string, string>|null $step_snapshots
 * @property string|null $work_result
 * @property string|null $obstacle
 * @property bool $mark_as_obstacle
 * @property bool $resolve_obstacle
 * @property string|null $next_step
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TrackerTarget $target
 * @property-read Collection<int, ProgressPhoto> $photos
 */
#[Fillable([
    'tracker_target_id',
    'note_date',
    'added_value',
    'completed_step_ids',
    'step_snapshots',
    'work_result',
    'obstacle',
    'mark_as_obstacle',
    'resolve_obstacle',
    'next_step',
])]
class ProgressNote extends Model
{
    /** @use HasFactory<ProgressNoteFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'note_date' => 'date:Y-m-d',
            'added_value' => 'float',
            'completed_step_ids' => 'array',
            'step_snapshots' => 'array',
            'mark_as_obstacle' => 'boolean',
            'resolve_obstacle' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TrackerTarget, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(TrackerTarget::class, 'tracker_target_id');
    }

    /**
     * @return HasMany<ProgressPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProgressPhoto::class, 'progress_note_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
