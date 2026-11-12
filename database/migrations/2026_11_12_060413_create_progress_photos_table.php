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
        Schema::create('progress_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('progress_note_id')
                ->constrained('progress_notes')
                ->cascadeOnDelete();
            $table->foreignId('tracker_target_id')
                ->constrained('tracker_targets')
                ->cascadeOnDelete();
            $table->string('file_path');
            $table->string('mime_type', 128)->default('image/jpeg');
            $table->string('file_name');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_photos');
    }
};
