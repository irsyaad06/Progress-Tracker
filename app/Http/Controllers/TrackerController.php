<?php

namespace App\Http\Controllers;

use App\Services\TrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackerController extends Controller
{
    public function __construct(public TrackerService $trackerService) {}

    /**
     * Display the Personal Progress Tracker screen backed by MySQL.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $this->trackerService->ensureInitialSeed();
        $snapshot = $this->trackerService->getSnapshotPayload();

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'snapshot' => $snapshot,
            ]);
        }

        return Inertia::render('Welcome', [
            'snapshot' => $snapshot,
        ]);
    }
}
