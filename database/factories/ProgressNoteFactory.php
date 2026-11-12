<?php

namespace Database\Factories;

use App\Models\ProgressNote;
use App\Models\TrackerTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressNote>
 */
class ProgressNoteFactory extends Factory
{
    protected $model = ProgressNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracker_target_id' => TrackerTarget::factory(),
            'note_date' => now()->toDateString(),
            'added_value' => 1,
            'completed_step_ids' => [],
            'step_snapshots' => [],
            'work_result' => fake()->sentence(6),
            'obstacle' => null,
            'mark_as_obstacle' => false,
            'resolve_obstacle' => false,
            'next_step' => fake()->sentence(5),
        ];
    }
}
