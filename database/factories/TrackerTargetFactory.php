<?php

namespace Database\Factories;

use App\Models\TrackerTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackerTarget>
 */
class TrackerTargetFactory extends Factory
{
    protected $model = TrackerTarget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->toDateString();
        $due = now()->addDays(7)->toDateString();

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(8),
            'period' => 'Mingguan',
            'start_date' => $start,
            'due_date' => $due,
            'type' => 'numeric',
            'target_value' => 3,
            'unit' => 'sesi',
            'steps' => [],
            'current_value' => 0,
            'status' => 'Belum Mulai',
            'has_active_obstacle' => false,
            'manual_obstacle_note' => null,
        ];
    }

    /**
     * Configure a checklist target.
     *
     * @param  array<int, array{id: string, text: string}>|null  $steps
     */
    public function checklist(?array $steps = null): static
    {
        $resolvedSteps = $steps ?? [
            ['id' => 'step_1', 'text' => 'Langkah pertama'],
            ['id' => 'step_2', 'text' => 'Langkah kedua'],
        ];

        return $this->state(fn (): array => [
            'type' => 'checklist',
            'target_value' => count($resolvedSteps),
            'unit' => 'langkah',
            'steps' => $resolvedSteps,
        ]);
    }
}
