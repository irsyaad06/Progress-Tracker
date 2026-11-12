<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('progress_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracker_target_id')
                ->constrained('tracker_targets')
                ->cascadeOnDelete();
            $table->date('note_date');
            $table->decimal('added_value', 10, 2)->default(0);
            $table->json('completed_step_ids')->nullable();
            $table->json('step_snapshots')->nullable();
            $table->text('work_result')->nullable();
            $table->text('obstacle')->nullable();
            $table->boolean('mark_as_obstacle')->default(false);
            $table->boolean('resolve_obstacle')->default(false);
            $table->text('next_step')->nullable();
            $table->timestamps();

            $table->index(['tracker_target_id', 'note_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};
