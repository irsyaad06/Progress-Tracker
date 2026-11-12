import { computed, ref } from 'vue';
import {
    applyMetricsToTarget,
    computeTargetMetrics,
    groupNotesByTarget,
} from '@/lib/progressTracker';
import {
    apiDeleteNote,
    apiDeleteTarget,
    apiSaveNoteWithPhotos,
    apiSaveTarget,
    apiToggleTargetObstacle,
    fetchTrackerSnapshot,
} from '@/lib/trackerApi';
import type {
    FeedbackMessage,
    PhotoRecord,
    ProgressNote,
    SaveNotePayload,
    TargetItem,
    TargetMetrics,
    TrackerSnapshot,
} from '@/types';

const FEEDBACK_DURATION_MS = 5000;

const targets = ref<TargetItem[]>([]);
const notes = ref<ProgressNote[]>([]);
const photos = ref<PhotoRecord[]>([]);
const photoUrls = ref<Record<string, string>>({});
const isLoading = ref(false);
const feedback = ref<FeedbackMessage | null>(null);
let feedbackTimer: ReturnType<typeof setTimeout> | null = null;

const notesByTarget = computed(() => groupNotesByTarget(notes.value));

const photosByNote = computed(() => {
    const grouped = new Map<string, PhotoRecord[]>();

    for (const photo of photos.value) {
        const list = grouped.get(photo.noteId) ?? [];
        list.push(photo);
        grouped.set(photo.noteId, list);
    }

    return grouped;
});

const metricsByTarget = computed(() => {
    const metrics = new Map<string, TargetMetrics>();

    for (const target of targets.value) {
        metrics.set(
            target.id,
            computeTargetMetrics(
                target,
                notesByTarget.value.get(target.id) ?? [],
            ),
        );
    }

    return metrics;
});

function errorText(error: unknown, fallback: string): string {
    return error instanceof Error ? error.message : fallback;
}

function showFeedback(type: FeedbackMessage['type'], text: string): void {
    feedback.value = { type, text };

    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
    }

    feedbackTimer = setTimeout(() => {
        feedback.value = null;
    }, FEEDBACK_DURATION_MS);
}

function dismissFeedback(): void {
    feedback.value = null;
}

/**
 * Menyinkronkan peta URL foto dari URL storage server atau object URL Blob.
 */
function syncPhotoUrls(): void {
    const nextUrls: Record<string, string> = {};

    for (const photo of photos.value) {
        if (photo.url) {
            nextUrls[photo.id] = photo.url;
        } else if (photo.blob instanceof Blob) {
            nextUrls[photo.id] =
                photoUrls.value[photo.id] ?? URL.createObjectURL(photo.blob);
        }
    }

    for (const [photoId, url] of Object.entries(photoUrls.value)) {
        if (!nextUrls[photoId] && url.startsWith('blob:')) {
            URL.revokeObjectURL(url);
        }
    }

    photoUrls.value = nextUrls;
}

function applySnapshot(snapshot: TrackerSnapshot): void {
    const grouped = groupNotesByTarget(snapshot.notes);

    targets.value = snapshot.targets.map((target) =>
        applyMetricsToTarget(target, grouped.get(target.id) ?? []),
    );
    notes.value = snapshot.notes;
    photos.value = snapshot.photos;
    syncPhotoUrls();
}

function findTarget(targetId: string | null): TargetItem | null {
    return targets.value.find((item) => item.id === targetId) ?? null;
}

function notesForTarget(targetId: string): ProgressNote[] {
    return notesByTarget.value.get(targetId) ?? [];
}

function orderedPhotosForNote(note: ProgressNote): PhotoRecord[] {
    const notePhotos = photosByNote.value.get(note.id) ?? [];
    const position = new Map(note.photoOrder.map((id, index) => [id, index]));

    return [...notePhotos].sort(
        (a, b) =>
            (position.get(a.id) ?? Number.MAX_SAFE_INTEGER) -
            (position.get(b.id) ?? Number.MAX_SAFE_INTEGER),
    );
}

async function load(initialSnapshot?: TrackerSnapshot): Promise<void> {
    if (initialSnapshot) {
        applySnapshot(initialSnapshot);

        return;
    }

    isLoading.value = true;

    try {
        const snapshot = await fetchTrackerSnapshot();
        applySnapshot(snapshot);
    } catch (error) {
        showFeedback(
            'error',
            errorText(error, 'Gagal memuat data dari database MySQL.'),
        );
    } finally {
        isLoading.value = false;
    }
}

async function saveTarget(
    input: TargetItem,
    isEditing: boolean,
): Promise<TargetItem | null> {
    try {
        const result = await apiSaveTarget(input, isEditing);
        applySnapshot(result.snapshot);
        showFeedback('success', result.message);

        return (
            findTarget(result.savedTargetId ?? input.id) ??
            targets.value[0] ??
            null
        );
    } catch (error) {
        showFeedback(
            'error',
            errorText(error, 'Gagal menyimpan target ke database MySQL.'),
        );

        return null;
    }
}

async function setTargetObstacle(
    target: TargetItem,
    hasObstacle: boolean,
): Promise<void> {
    try {
        const result = await apiToggleTargetObstacle(
            target.id,
            hasObstacle,
            hasObstacle
                ? target.manualObstacleNote ||
                      'Ditandai terkendala secara manual.'
                : '',
        );
        applySnapshot(result.snapshot);
        showFeedback('success', result.message);
    } catch (error) {
        showFeedback(
            'error',
            errorText(error, 'Gagal memperbarui status kendala target.'),
        );
    }
}

async function saveNote(payload: SaveNotePayload): Promise<boolean> {
    try {
        const result = await apiSaveNoteWithPhotos(payload);
        applySnapshot(result.snapshot);
        showFeedback('success', result.message);

        return true;
    } catch (error) {
        showFeedback(
            'error',
            errorText(
                error,
                'Gagal menyimpan catatan progress dan foto ke database MySQL.',
            ),
        );

        return false;
    }
}

async function deleteNote(noteId: string): Promise<boolean> {
    try {
        const result = await apiDeleteNote(noteId);
        applySnapshot(result.snapshot);
        showFeedback('success', result.message);

        return true;
    } catch (error) {
        showFeedback(
            'error',
            errorText(error, 'Gagal menghapus catatan progress.'),
        );

        return false;
    }
}

async function deleteTarget(targetId: string): Promise<boolean> {
    try {
        const result = await apiDeleteTarget(targetId);
        applySnapshot(result.snapshot);
        showFeedback('success', result.message);

        return true;
    } catch (error) {
        showFeedback('error', errorText(error, 'Gagal menghapus target.'));

        return false;
    }
}

function dispose(): void {
    for (const url of Object.values(photoUrls.value)) {
        if (url.startsWith('blob:')) {
            URL.revokeObjectURL(url);
        }
    }
    photoUrls.value = {};

    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
    }
}

/**
 * State tracker bersama yang terhubung ke database MySQL.
 */
export function useProgressTracker() {
    return {
        targets,
        notes,
        photos,
        photoUrls,
        isLoading,
        feedback,
        notesByTarget,
        metricsByTarget,
        load,
        dispose,
        findTarget,
        notesForTarget,
        orderedPhotosForNote,
        saveTarget,
        setTargetObstacle,
        saveNote,
        deleteNote,
        deleteTarget,
        showFeedback,
        dismissFeedback,
    };
}
