<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrackerTargetRequest;
use App\Models\TrackerTarget;
use App\Services\TrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TrackerTargetController extends Controller
{
    public function __construct(public TrackerService $trackerService) {}

    /**
     * Store a newly created target in MySQL.
     */
    public function store(StoreTrackerTargetRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $isChecklist = $validated['type'] === 'checklist';
        $steps = $isChecklist ? array_values($validated['steps'] ?? []) : [];

        $target = DB::transaction(function () use ($validated, $isChecklist, $steps): TrackerTarget {
            $created = TrackerTarget::query()->create([
                'title' => trim((string) $validated['title']),
                'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
                'period' => $validated['period'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'type' => $validated['type'],
                'target_value' => $isChecklist ? count($steps) : (float) $validated['target_value'],
                'unit' => $isChecklist ? 'langkah' : trim((string) $validated['unit']),
                'steps' => $steps,
                'current_value' => 0,
                'status' => 'Belum Mulai',
                'has_active_obstacle' => false,
                'manual_obstacle_note' => null,
            ]);

            return $this->trackerService->recalculateTarget($created);
        });

        $message = 'Target baru berhasil dibuat dan disimpan ke database MySQL.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'savedTargetId' => (string) $target->id,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ], 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Update an existing target in MySQL and recalculate its metrics.
     */
    public function update(
        StoreTrackerTargetRequest $request,
        TrackerTarget $target,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $isChecklist = $validated['type'] === 'checklist';
        $steps = $isChecklist ? array_values($validated['steps'] ?? []) : [];

        $updated = DB::transaction(function () use ($target, $validated, $isChecklist, $steps): TrackerTarget {
            $target->fill([
                'title' => trim((string) $validated['title']),
                'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
                'period' => $validated['period'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'type' => $validated['type'],
                'target_value' => $isChecklist ? count($steps) : (float) $validated['target_value'],
                'unit' => $isChecklist ? 'langkah' : trim((string) $validated['unit']),
                'steps' => $steps,
            ]);
            $target->save();

            return $this->trackerService->recalculateTarget($target);
        });

        $message = 'Perubahan target berhasil disimpan ke database MySQL.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'savedTargetId' => (string) $updated->id,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Toggle or resolve active obstacle status on a target.
     */
    public function toggleObstacle(
        Request $request,
        TrackerTarget $target,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'has_active_obstacle' => ['required', 'boolean'],
            'manual_obstacle_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $hasObstacle = (bool) $validated['has_active_obstacle'];

        DB::transaction(function () use ($target, $hasObstacle, $validated): void {
            $target->has_active_obstacle = $hasObstacle;
            $target->manual_obstacle_note = $hasObstacle
                ? ($validated['manual_obstacle_note'] ?? $target->manual_obstacle_note ?? 'Ditandai terkendala secara manual.')
                : null;
            $target->save();

            $this->trackerService->recalculateTarget($target);
        });

        $message = $hasObstacle
            ? 'Status target diperbarui menjadi Terkendala.'
            : 'Kendala ditandai teratasi. Status target telah diperbarui.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Delete a target along with all of its progress notes and photos.
     */
    public function destroy(
        Request $request,
        TrackerTarget $target,
    ): RedirectResponse|JsonResponse {
        DB::transaction(function () use ($target): void {
            $photoPaths = $target->photos()->pluck('file_path')->all();
            $target->delete();

            foreach ($photoPaths as $path) {
                if (is_string($path) && ! str_starts_with($path, 'tracker-photos/seed-')) {
                    Storage::disk('public')->delete($path);
                }
            }
        });

        $message = 'Target beserta seluruh riwayat dan fotonya berhasil dihapus.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
