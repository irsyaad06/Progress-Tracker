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
        Schema::create('tracker_targets', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('period', 32)->default('Mingguan');
            $table->date('start_date');
            $table->date('due_date');
            $table->string('type', 32)->default('numeric');
            $table->decimal('target_value', 10, 2)->default(1);
            $table->string('unit', 64)->default('sesi');
            $table->json('steps')->nullable();
            $table->decimal('current_value', 10, 2)->default(0);
            $table->string('status', 32)->default('Belum Mulai');
            $table->boolean('has_active_obstacle')->default(false);
            $table->text('manual_obstacle_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_targets');
    }
};
