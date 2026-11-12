<?php

namespace App\Models;

use Database\Factories\TrackerTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $period
 * @property Carbon $start_date
 * @property Carbon $due_date
 * @property string $type
 * @property float $target_value
 * @property string $unit
 * @property array<int, array{id: string, text: string}>|null $steps
 * @property float $current_value
 * @property string $status
 * @property bool $has_active_obstacle
 * @property string|null $manual_obstacle_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProgressNote> $notes
 * @property-read Collection<int, ProgressPhoto> $photos
 */
#[Fillable([
    'title',
    'description',
    'period',
    'start_date',
    'due_date',
    'type',
    'target_value',
    'unit',
    'steps',
    'current_value',
    'status',
    'has_active_obstacle',
    'manual_obstacle_note',
])]
class TrackerTarget extends Model
{
    /** @use HasFactory<TrackerTargetFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'target_value' => 'float',
            'current_value' => 'float',
            'steps' => 'array',
            'has_active_obstacle' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProgressNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ProgressNote::class, 'tracker_target_id')
            ->orderByDesc('note_date')
            ->orderByDesc('id');
    }

    /**
     * @return HasMany<ProgressPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProgressPhoto::class, 'tracker_target_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
