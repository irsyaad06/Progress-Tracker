<?php

namespace App\Services;

use App\Models\ProgressNote;
use App\Models\ProgressPhoto;
use App\Models\TrackerSetting;
use App\Models\TrackerTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrackerService
{
    public const SEED_SETTING_KEY = 'initial_seed_completed_v1';

    /**
     * Ensure sample data is seeded into the database only once on first use.
     */
    public function ensureInitialSeed(): void
    {
        if (TrackerSetting::query()->where('key', self::SEED_SETTING_KEY)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            if (TrackerSetting::query()->where('key', self::SEED_SETTING_KEY)->exists()) {
                return;
            }

            $today = now()->toDateString();
            $yesterday = now()->subDay()->toDateString();
            $twoDaysAgo = now()->subDays(2)->toDateString();
            $fiveDaysAgo = now()->subDays(5)->toDateString();

            $readingTarget = TrackerTarget::query()->create([
                'title' => 'Membaca Buku Atomic Habits',
                'description' => 'Membaca minimal 30 halaman setiap hari untuk membangun kebiasaan belajar konsisten.',
                'period' => 'Harian',
                'start_date' => $today,
                'due_date' => $today,
                'type' => 'numeric',
                'target_value' => 30,
                'unit' => 'halaman',
                'steps' => [],
                'current_value' => 20,
                'status' => 'Berjalan',
                'has_active_obstacle' => false,
                'manual_obstacle_note' => null,
            ]);

            $readingNote = ProgressNote::query()->create([
                'tracker_target_id' => $readingTarget->id,
                'note_date' => $today,
                'added_value' => 20,
                'completed_step_ids' => [],
                'step_snapshots' => [],
                'work_result' => 'Menyelesaikan pembacaan 20 halaman pertama pada pagi hari dan mencatat 3 ide utama.',
                'obstacle' => null,
                'mark_as_obstacle' => false,
                'resolve_obstacle' => false,
                'next_step' => 'Membaca 10 halaman terakhir sebelum pukul 21.00 malam ini.',
            ]);

            $readingSvgPath = 'tracker-photos/seed-reading.svg';
            Storage::disk('public')->put(
                $readingSvgPath,
                $this->buildSampleSvg(
                    'Bab 1-2 Selesai Dibaca',
                    'Catatan ringkas 20 halaman sesi pagi',
                    '#29294E',
                    '#7766BC',
                ),
            );

            ProgressPhoto::query()->create([
                'progress_note_id' => $readingNote->id,
                'tracker_target_id' => $readingTarget->id,
                'file_path' => $readingSvgPath,
                'mime_type' => 'image/svg+xml',
                'file_name' => 'catatan-baca-pagi.svg',
                'caption' => 'Ringkasan poin penting halaman 1 sampai 20',
                'sort_order' => 0,
            ]);

            $portfolioSteps = [
                ['id' => 'step_seed_1', 'text' => 'Riset 3 referensi desain antarmuka mobile'],
                ['id' => 'step_seed_2', 'text' => 'Susun kerangka komponen dan palet warna'],
                ['id' => 'step_seed_3', 'text' => 'Integrasi penyimpanan database MySQL'],
                ['id' => 'step_seed_4', 'text' => 'Uji responsivitas pada layar 360px'],
            ];

            $portfolioTarget = TrackerTarget::query()->create([
                'title' => 'Rilis Prototipe Portofolio Pribadi',
                'description' => 'Menyelesaikan empat tahapan utama pembuatan halaman portofolio interaktif minggu ini.',
                'period' => 'Mingguan',
                'start_date' => $fiveDaysAgo,
                'due_date' => $yesterday,
                'type' => 'checklist',
                'target_value' => 4,
                'unit' => 'langkah',
                'steps' => $portfolioSteps,
                'current_value' => 2,
                'status' => 'Terkendala',
                'has_active_obstacle' => true,
                'manual_obstacle_note' => 'Ukuran gambar mentah terlalu besar sehingga pratinjau sempat lambat dimuat.',
            ]);

            $portfolioNoteOne = ProgressNote::query()->create([
                'tracker_target_id' => $portfolioTarget->id,
                'note_date' => $twoDaysAgo,
                'added_value' => 2,
                'completed_step_ids' => ['step_seed_1', 'step_seed_2'],
                'step_snapshots' => [
                    'step_seed_1' => 'Riset 3 referensi desain antarmuka mobile',
                    'step_seed_2' => 'Susun kerangka komponen dan palet warna',
                ],
                'work_result' => 'Berhasil merampungkan riset tiga referensi UI dan menetapkan sistem warna indigo-lavender.',
                'obstacle' => null,
                'mark_as_obstacle' => false,
                'resolve_obstacle' => false,
                'next_step' => 'Mulai menguji penyimpanan foto dan database.',
            ]);

            $portfolioSvgPath = 'tracker-photos/seed-portfolio.svg';
            Storage::disk('public')->put(
                $portfolioSvgPath,
                $this->buildSampleSvg(
                    'Wireframe & Palet Warna',
                    'Indigo #29294E, Lavender #F1EFF8, Aksen #F8DF75',
                    '#494B97',
                    '#29294E',
                ),
            );

            ProgressPhoto::query()->create([
                'progress_note_id' => $portfolioNoteOne->id,
                'tracker_target_id' => $portfolioTarget->id,
                'file_path' => $portfolioSvgPath,
                'mime_type' => 'image/svg+xml',
                'file_name' => 'wireframe-portofolio.svg',
                'caption' => 'Sketsa susunan kartu target dan navigasi bawah ponsel',
                'sort_order' => 0,
            ]);

            ProgressNote::query()->create([
                'tracker_target_id' => $portfolioTarget->id,
                'note_date' => $yesterday,
                'added_value' => 0,
                'completed_step_ids' => [],
                'step_snapshots' => [],
                'work_result' => 'Mencoba mengunggah beberapa sampel foto beresolusi tinggi ke form catatan.',
                'obstacle' => 'Ukuran gambar mentah terlalu besar sehingga pratinjau sempat lambat dimuat pada perangkat uji.',
                'mark_as_obstacle' => true,
                'resolve_obstacle' => false,
                'next_step' => 'Rapikan manajemen pratinjau foto dan lanjutkan langkah integrasi.',
            ]);

            TrackerTarget::query()->create([
                'title' => 'Latihan Lari Pagi 40 Kilometer',
                'description' => 'Akumulasi jarak lari pagi selama satu bulan penuh untuk menjaga kebugaran kardio.',
                'period' => 'Bulanan',
                'start_date' => $today,
                'due_date' => now()->addDays(20)->toDateString(),
                'type' => 'numeric',
                'target_value' => 40,
                'unit' => 'km',
                'steps' => [],
                'current_value' => 0,
                'status' => 'Belum Mulai',
                'has_active_obstacle' => false,
                'manual_obstacle_note' => null,
            ]);

            $routineSteps = [
                ['id' => 'step_seed_setup_1', 'text' => 'Susun 3 prioritas utama hari ini'],
                ['id' => 'step_seed_setup_2', 'text' => 'Siapkan air minum dan rapikan meja kerja'],
            ];

            $routineTarget = TrackerTarget::query()->create([
                'title' => 'Persiapan Fokus & Jurnal Pagi',
                'description' => 'Checklist rutinitas pagi sebelum memulai sesi kerja utama.',
                'period' => 'Harian',
                'start_date' => $today,
                'due_date' => now()->addDays(5)->toDateString(),
                'type' => 'checklist',
                'target_value' => 2,
                'unit' => 'langkah',
                'steps' => $routineSteps,
                'current_value' => 2,
                'status' => 'Selesai',
                'has_active_obstacle' => false,
                'manual_obstacle_note' => null,
            ]);

            ProgressNote::query()->create([
                'tracker_target_id' => $routineTarget->id,
                'note_date' => $today,
                'added_value' => 2,
                'completed_step_ids' => ['step_seed_setup_1', 'step_seed_setup_2'],
                'step_snapshots' => [
                    'step_seed_setup_1' => 'Susun 3 prioritas utama hari ini',
                    'step_seed_setup_2' => 'Siapkan air minum dan rapikan meja kerja',
                ],
                'work_result' => 'Seluruh persiapan pagi selesai tepat pukul 07.30 WIB.',
                'obstacle' => null,
                'mark_as_obstacle' => false,
                'resolve_obstacle' => false,
                'next_step' => 'Target telah tercapai sepenuhnya.',
            ]);

            TrackerSetting::query()->updateOrCreate(
                ['key' => self::SEED_SETTING_KEY],
                ['value' => '1'],
            );
        });
    }

    /**
     * Recalculate a target's achievement and status from its notes.
     */
    public function recalculateTarget(TrackerTarget $target): TrackerTarget
    {
        $notes = $target->notes()->get();
        $steps = is_array($target->steps) ? $target->steps : [];

        if ($target->type === 'checklist') {
            $validStepIds = collect($steps)
                ->pluck('id')
                ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
                ->values()
                ->all();

            $completedStepIds = $notes
                ->flatMap(fn (ProgressNote $note): array => is_array($note->completed_step_ids) ? $note->completed_step_ids : [])
                ->filter(fn (mixed $id): bool => in_array($id, $validStepIds, true))
                ->unique()
                ->values()
                ->all();

            $currentValue = (float) count($completedStepIds);
            $targetValue = (float) count($validStepIds);
        } else {
            $currentValue = round((float) $notes->sum('added_value'), 2);
            $targetValue = max(0.01, (float) $target->target_value);
        }

        $isCompleted = $targetValue > 0 && $currentValue >= $targetValue;
        $hasActiveObstacle = ! $isCompleted && (bool) $target->has_active_obstacle;

        if ($isCompleted) {
            $status = 'Selesai';
        } elseif ($hasActiveObstacle) {
            $status = 'Terkendala';
        } elseif ($notes->isNotEmpty() || $currentValue > 0) {
            $status = 'Berjalan';
        } else {
            $status = 'Belum Mulai';
        }

        $target->fill([
            'current_value' => $currentValue,
            'target_value' => $targetValue,
            'status' => $status,
            'has_active_obstacle' => $hasActiveObstacle,
        ]);
        $target->save();

        return $target;
    }

    /**
     * Save or update a progress note and its photos atomically in one transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $uploadedPhotos
     */
    public function saveNoteWithPhotos(
        TrackerTarget $target,
        ?ProgressNote $existingNote,
        array $data,
        array $uploadedPhotos = [],
    ): ProgressNote {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($target, $existingNote, $data, $uploadedPhotos, &$storedPaths): ProgressNote {
                $completedStepIds = $target->type === 'checklist'
                    ? array_values(array_filter($data['completed_step_ids'] ?? [], 'is_string'))
                    : [];

                $stepSnapshots = is_array($existingNote?->step_snapshots)
                    ? $existingNote->step_snapshots
                    : [];

                if ($target->type === 'checklist') {
                    $stepMap = collect($target->steps ?? [])->pluck('text', 'id')->all();
                    foreach ($completedStepIds as $stepId) {
                        if (isset($stepMap[$stepId])) {
                            $stepSnapshots[$stepId] = (string) $stepMap[$stepId];
                        }
                    }
                }

                $addedValue = $target->type === 'checklist'
                    ? (float) count($completedStepIds)
                    : round((float) ($data['added_value'] ?? 0), 2);

                $obstacleText = trim((string) ($data['obstacle'] ?? ''));
                $obstacleAction = (string) ($data['obstacle_action'] ?? 'keep');

                $markAsObstacle = $obstacleAction === 'mark'
                    || ($obstacleAction === 'keep' && $obstacleText !== '' && $addedValue <= 0);
                $resolveObstacle = $obstacleAction === 'resolve';

                $noteAttributes = [
                    'tracker_target_id' => $target->id,
                    'note_date' => $data['note_date'],
                    'added_value' => $addedValue,
                    'completed_step_ids' => $completedStepIds,
                    'step_snapshots' => $stepSnapshots,
                    'work_result' => trim((string) ($data['work_result'] ?? '')) ?: null,
                    'obstacle' => $obstacleText !== '' ? $obstacleText : null,
                    'mark_as_obstacle' => $markAsObstacle,
                    'resolve_obstacle' => $resolveObstacle,
                    'next_step' => trim((string) ($data['next_step'] ?? '')) ?: null,
                ];

                if ($existingNote) {
                    $existingNote->update($noteAttributes);
                    $note = $existingNote;
                } else {
                    $note = ProgressNote::query()->create($noteAttributes);
                }

                $sortOrder = 0;

                if ($existingNote) {
                    /** @var array<int, array<string, mixed>> $existingPhotos */
                    $existingPhotos = is_array($data['existing_photos'] ?? null)
                        ? $data['existing_photos']
                        : [];
                    $keptPhotosInput = collect($existingPhotos);
                    $keptIds = $keptPhotosInput
                        ->pluck('id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();

                    $currentPhotos = $note->photos()->get();
                    foreach ($currentPhotos as $photo) {
                        if (! in_array($photo->id, $keptIds, true)) {
                            Storage::disk('public')->delete($photo->file_path);
                            $photo->delete();
                        }
                    }

                    foreach ($keptPhotosInput as $item) {
                        $photoId = isset($item['id']) ? (int) $item['id'] : 0;
                        $caption = isset($item['caption']) ? trim((string) $item['caption']) : null;
                        ProgressPhoto::query()
                            ->where('id', $photoId)
                            ->where('progress_note_id', $note->id)
                            ->update([
                                'caption' => $caption !== '' ? $caption : null,
                                'sort_order' => $sortOrder++,
                            ]);
                    }
                }

                $newCaptions = $data['new_photo_captions'] ?? [];
                foreach ($uploadedPhotos as $index => $file) {
                    $path = $file->store('tracker-photos', 'public');
                    if ($path === false) {
                        throw new \RuntimeException('Gagal menyimpan file foto bukti.');
                    }
                    $storedPaths[] = $path;
                    $caption = isset($newCaptions[$index]) ? trim((string) $newCaptions[$index]) : '';

                    ProgressPhoto::query()->create([
                        'progress_note_id' => $note->id,
                        'tracker_target_id' => $target->id,
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType() ?: 'image/jpeg',
                        'file_name' => $file->getClientOriginalName() ?: basename($path),
                        'caption' => $caption !== '' ? $caption : null,
                        'sort_order' => $sortOrder++,
                    ]);
                }

                if ($markAsObstacle) {
                    $target->has_active_obstacle = true;
                    $target->manual_obstacle_note = $obstacleText !== ''
                        ? $obstacleText
                        : $target->manual_obstacle_note;
                } elseif ($resolveObstacle) {
                    $target->has_active_obstacle = false;
                    $target->manual_obstacle_note = null;
                }

                $this->recalculateTarget($target);

                return $note->fresh(['photos']) ?? $note;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Build the complete frontend snapshot payload from MySQL.
     *
     * @return array{
     *     targets: array<int, array<string, mixed>>,
     *     notes: array<int, array<string, mixed>>,
     *     photos: array<int, array<string, mixed>>
     * }
     */
    public function getSnapshotPayload(): array
    {
        $targetModels = TrackerTarget::query()
            ->with(['notes.photos'])
            ->get();

        $targets = [];
        $notes = [];
        $photos = [];

        foreach ($targetModels as $target) {
            $targets[] = [
                'id' => (string) $target->id,
                'title' => $target->title,
                'description' => $target->description ?? '',
                'period' => $target->period,
                'startDate' => $target->start_date->format('Y-m-d'),
                'dueDate' => $target->due_date->format('Y-m-d'),
                'type' => $target->type,
                'targetValue' => (float) $target->target_value,
                'unit' => $target->unit,
                'steps' => is_array($target->steps) ? $target->steps : [],
                'currentValue' => (float) $target->current_value,
                'status' => $target->status,
                'hasActiveObstacle' => (bool) $target->has_active_obstacle,
                'manualObstacleNote' => $target->manual_obstacle_note ?? '',
                'createdAt' => $target->created_at?->toISOString() ?? now()->toISOString(),
                'updatedAt' => $target->updated_at?->toISOString() ?? now()->toISOString(),
            ];

            foreach ($target->notes as $note) {
                $photoIds = [];
                foreach ($note->photos as $photo) {
                    $photoIdStr = (string) $photo->id;
                    $photoIds[] = $photoIdStr;
                    $photos[] = [
                        'id' => $photoIdStr,
                        'noteId' => (string) $note->id,
                        'targetId' => (string) $target->id,
                        'url' => $photo->publicUrl(),
                        'mimeType' => $photo->mime_type,
                        'fileName' => $photo->file_name,
                        'caption' => $photo->caption ?? '',
                        'createdAt' => $photo->created_at?->toISOString() ?? now()->toISOString(),
                    ];
                }

                $notes[] = [
                    'id' => (string) $note->id,
                    'targetId' => (string) $target->id,
                    'date' => $note->note_date->format('Y-m-d'),
                    'addedValue' => (float) $note->added_value,
                    'completedStepIds' => is_array($note->completed_step_ids) ? $note->completed_step_ids : [],
                    'stepSnapshots' => is_array($note->step_snapshots) ? $note->step_snapshots : [],
                    'workResult' => $note->work_result ?? '',
                    'obstacle' => $note->obstacle ?? '',
                    'markAsObstacle' => (bool) $note->mark_as_obstacle,
                    'resolveObstacle' => (bool) $note->resolve_obstacle,
                    'nextStep' => $note->next_step ?? '',
                    'photoOrder' => $photoIds,
                    'createdAt' => $note->created_at?->toISOString() ?? now()->toISOString(),
                    'updatedAt' => $note->updated_at?->toISOString() ?? now()->toISOString(),
                ];
            }
        }

        return [
            'targets' => $targets,
            'notes' => $notes,
            'photos' => $photos,
        ];
    }

    private function buildSampleSvg(
        string $title,
        string $subtitle,
        string $gradientStart,
        string $gradientEnd,
    ): string {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="420" viewBox="0 0 640 420">
    <defs>
        <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="{$gradientStart}" />
            <stop offset="100%" stop-color="{$gradientEnd}" />
        </linearGradient>
    </defs>
    <rect width="640" height="420" rx="24" fill="url(#g)" />
    <rect x="36" y="36" width="568" height="348" rx="18" fill="rgba(255,255,255,0.14)" stroke="rgba(255,255,255,0.3)" stroke-width="2" />
    <circle cx="100" cy="110" r="28" fill="#F8DF75" />
    <path d="M90 110 l7 7 l15 -15" stroke="#211D37" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round" />
    <text x="64" y="210" fill="#FFFFFF" font-family="system-ui, sans-serif" font-size="28" font-weight="700">{$title}</text>
    <text x="64" y="252" fill="#F1EFF8" font-family="system-ui, sans-serif" font-size="19">{$subtitle}</text>
    <text x="64" y="336" fill="#F8DF75" font-family="system-ui, sans-serif" font-size="15" font-weight="600">Bukti Dokumentasi Progress Personal</text>
</svg>
SVG;
    }
}
