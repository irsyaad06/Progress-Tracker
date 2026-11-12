<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgressNoteRequest;
use App\Models\ProgressNote;
use App\Models\TrackerTarget;
use App\Services\TrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProgressNoteController extends Controller
{
    public function __construct(public TrackerService $trackerService) {}

    /**
     * Store a new progress note and its uploaded photos in one MySQL transaction.
     */
    public function store(StoreProgressNoteRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $target = TrackerTarget::query()->findOrFail((int) $validated['tracker_target_id']);
        $uploadedPhotos = $request->file('new_photos', []);

        $note = $this->trackerService->saveNoteWithPhotos(
            $target,
            null,
            $validated,
            is_array($uploadedPhotos) ? $uploadedPhotos : [$uploadedPhotos],
        );

        $message = 'Catatan progress dan foto berhasil disimpan ke database MySQL.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'savedNoteId' => (string) $note->id,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ], 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Update an existing progress note and its photos in one MySQL transaction.
     */
    public function update(
        StoreProgressNoteRequest $request,
        ProgressNote $note,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $target = TrackerTarget::query()->findOrFail((int) $note->tracker_target_id);
        $uploadedPhotos = $request->file('new_photos', []);

        $updated = $this->trackerService->saveNoteWithPhotos(
            $target,
            $note,
            $validated,
            is_array($uploadedPhotos) ? $uploadedPhotos : [$uploadedPhotos],
        );

        $message = 'Catatan progress dan foto berhasil diperbarui.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'savedNoteId' => (string) $updated->id,
                'snapshot' => $this->trackerService->getSnapshotPayload(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Delete a progress note and its photos, then recalculate the parent target.
     */
    public function destroy(
        Request $request,
        ProgressNote $note,
    ): RedirectResponse|JsonResponse {
        DB::transaction(function () use ($note): void {
            $target = $note->target;
            $photoPaths = $note->photos()->pluck('file_path')->all();

            $note->delete();
            $this->trackerService->recalculateTarget($target);

            foreach ($photoPaths as $path) {
                if (is_string($path) && ! str_starts_with($path, 'tracker-photos/seed-')) {
                    Storage::disk('public')->delete($path);
                }
            }
        });

        $message = 'Catatan progress dihapus dan capaian target telah dihitung ulang.';

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
