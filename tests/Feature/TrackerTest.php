<?php

use App\Models\TrackerTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('tracker home screen renders successfully with seed data', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('snapshot.targets')
        ->has('snapshot.notes')
        ->has('snapshot.photos')
    );
});

test('can create a numeric target and validate due date', function () {
    $invalidResponse = $this->postJson(route('tracker.targets.store'), [
        'title' => 'Target Error',
        'period' => 'Mingguan',
        'start_date' => '2026-11-20',
        'due_date' => '2026-11-10',
        'type' => 'numeric',
        'target_value' => 3,
        'unit' => 'sesi',
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['due_date']);

    $validResponse = $this->postJson(route('tracker.targets.store'), [
        'title' => 'Latihan Gym 3 Sesi',
        'period' => 'Mingguan',
        'start_date' => '2026-11-10',
        'due_date' => '2026-11-17',
        'type' => 'numeric',
        'target_value' => 3,
        'unit' => 'sesi',
    ]);

    $validResponse->assertCreated();
    $this->assertDatabaseHas('tracker_targets', [
        'title' => 'Latihan Gym 3 Sesi',
        'status' => 'Belum Mulai',
    ]);
});

test('can record progress note with uploaded photos and recalculate target', function () {
    Storage::fake('public');

    $target = TrackerTarget::factory()->create([
        'target_value' => 3,
        'unit' => 'sesi',
        'type' => 'numeric',
        'current_value' => 0,
        'status' => 'Belum Mulai',
    ]);

    $photo1 = UploadedFile::fake()->image('bukti1.jpg');
    $photo2 = UploadedFile::fake()->image('bukti2.png');

    $response = $this->post(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'added_value' => 1,
        'work_result' => 'Sesi pertama gym selesai',
        'next_step' => 'Lanjut sesi kedua lusa',
        'new_photos' => [$photo1, $photo2],
        'new_photo_captions' => ['Foto alat', 'Foto treadmill'],
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated();

    $target->refresh();
    expect((float) $target->current_value)->toBe(1.0)
        ->and($target->status)->toBe('Berjalan');

    $this->assertDatabaseCount('progress_photos', 2);
});

test('checklist target calculates unique completed steps', function () {
    $target = TrackerTarget::factory()->checklist([
        ['id' => 's1', 'text' => 'Langkah A'],
        ['id' => 's2', 'text' => 'Langkah B'],
    ])->create([
        'current_value' => 0,
        'status' => 'Belum Mulai',
    ]);

    // Note 1: completes s1
    $this->postJson(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'completed_step_ids' => ['s1'],
        'work_result' => 'Selesai langkah A',
    ])->assertCreated();

    $target->refresh();
    expect((float) $target->current_value)->toBe(1.0)
        ->and($target->status)->toBe('Berjalan');

    // Note 2: ticks s1 again (duplicate) -> should remain 1 unique
    $this->postJson(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'completed_step_ids' => ['s1'],
        'work_result' => 'Review langkah A',
    ])->assertCreated();

    $target->refresh();
    expect((float) $target->current_value)->toBe(1.0);

    // Note 3: completes s2 -> total 2/2 -> Selesai
    $this->postJson(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'completed_step_ids' => ['s2'],
        'work_result' => 'Selesai langkah B',
    ])->assertCreated();

    $target->refresh();
    expect((float) $target->current_value)->toBe(2.0)
        ->and($target->status)->toBe('Selesai');
});

test('can toggle active obstacle on target', function () {
    $target = TrackerTarget::factory()->create([
        'target_value' => 3,
        'unit' => 'sesi',
        'type' => 'numeric',
    ]);

    // Tambah 1 progress note sehingga target berstatus 'Berjalan'
    $this->postJson(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'added_value' => 1,
        'work_result' => 'Sesi pertama selesai',
    ])->assertCreated();

    $target->refresh();
    expect($target->status)->toBe('Berjalan');

    $this->patchJson(route('tracker.targets.obstacle', $target), [
        'has_active_obstacle' => true,
        'manual_obstacle_note' => 'Cedera ringan',
    ])->assertOk();

    $target->refresh();
    expect($target->has_active_obstacle)->toBeTrue()
        ->and($target->status)->toBe('Terkendala');

    $this->patchJson(route('tracker.targets.obstacle', $target), [
        'has_active_obstacle' => false,
    ])->assertOk();

    $target->refresh();
    expect($target->has_active_obstacle)->toBeFalse()
        ->and($target->status)->toBe('Berjalan');
});

test('deleting note recalculates target', function () {
    $target = TrackerTarget::factory()->create([
        'target_value' => 5,
        'unit' => 'sesi',
        'type' => 'numeric',
    ]);

    $response = $this->postJson(route('tracker.notes.store'), [
        'tracker_target_id' => $target->id,
        'note_date' => now()->toDateString(),
        'added_value' => 3,
        'work_result' => 'Sesi 1-3',
    ]);

    $noteId = $response->json('savedNoteId');
    $target->refresh();
    expect((float) $target->current_value)->toBe(3.0);

    $this->deleteJson(route('tracker.notes.destroy', $noteId))->assertOk();

    $target->refresh();
    expect((float) $target->current_value)->toBe(0.0)
        ->and($target->status)->toBe('Belum Mulai');
});
