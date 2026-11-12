<?php

namespace Database\Factories;

use App\Models\ProgressNote;
use App\Models\ProgressPhoto;
use App\Models\TrackerTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressPhoto>
 */
class ProgressPhotoFactory extends Factory
{
    protected $model = ProgressPhoto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'progress_note_id' => ProgressNote::factory(),
            'tracker_target_id' => TrackerTarget::factory(),
            'file_path' => 'tracker-photos/sample.svg',
            'mime_type' => 'image/svg+xml',
            'file_name' => 'sample.svg',
            'caption' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
