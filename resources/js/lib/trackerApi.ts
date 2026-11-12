import {
    destroy as destroyNoteAction,
    store as storeNoteAction,
    update as updateNoteAction,
} from '@/actions/App/Http/Controllers/ProgressNoteController';
import { index as trackerIndexAction } from '@/actions/App/Http/Controllers/TrackerController';
import {
    destroy as destroyTargetAction,
    store as storeTargetAction,
    toggleObstacle as toggleObstacleAction,
    update as updateTargetAction,
} from '@/actions/App/Http/Controllers/TrackerTargetController';
import type { SaveNotePayload, TargetItem, TrackerSnapshot } from '@/types';

type ApiMutationResponse = {
    message: string;
    savedTargetId?: string;
    savedNoteId?: string;
    snapshot: TrackerSnapshot;
};

function readXsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=')[1] ?? '') : '';
}

function baseHeaders(isJson = true): Record<string, string> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const xsrf = readXsrfToken();

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
    }

    if (isJson) {
        headers['Content-Type'] = 'application/json';
    }

    return headers;
}

async function parseJsonResponse<T>(
    response: Response,
    fallbackError: string,
): Promise<T> {
    const data = (await response.json().catch(() => null)) as
        | (T & { message?: string; errors?: Record<string, string[]> })
        | null;

    if (!response.ok || !data) {
        const firstValidationError = data?.errors
            ? Object.values(data.errors).flat()[0]
            : undefined;

        throw new Error(firstValidationError || data?.message || fallbackError);
    }

    return data;
}

export async function fetchTrackerSnapshot(): Promise<TrackerSnapshot> {
    const response = await fetch(trackerIndexAction.url(), {
        method: 'GET',
        headers: baseHeaders(false),
        credentials: 'same-origin',
    });

    const data = await parseJsonResponse<{ snapshot: TrackerSnapshot }>(
        response,
        'Gagal memuat data dari server MySQL.',
    );

    return data.snapshot;
}

export async function apiSaveTarget(
    target: TargetItem,
    isEditing: boolean,
): Promise<ApiMutationResponse> {
    const url = isEditing
        ? updateTargetAction.url(Number(target.id))
        : storeTargetAction.url();
    const method = isEditing ? 'PUT' : 'POST';

    const response = await fetch(url, {
        method,
        headers: baseHeaders(true),
        credentials: 'same-origin',
        body: JSON.stringify({
            title: target.title,
            description: target.description,
            period: target.period,
            start_date: target.startDate,
            due_date: target.dueDate,
            type: target.type,
            target_value: target.targetValue,
            unit: target.unit,
            steps: target.steps,
        }),
    });

    return parseJsonResponse<ApiMutationResponse>(
        response,
        'Gagal menyimpan target ke database MySQL.',
    );
}

export async function apiToggleTargetObstacle(
    targetId: string,
    hasActiveObstacle: boolean,
    manualObstacleNote?: string,
): Promise<ApiMutationResponse> {
    const response = await fetch(toggleObstacleAction.url(Number(targetId)), {
        method: 'PATCH',
        headers: baseHeaders(true),
        credentials: 'same-origin',
        body: JSON.stringify({
            has_active_obstacle: hasActiveObstacle,
            manual_obstacle_note: manualObstacleNote ?? null,
        }),
    });

    return parseJsonResponse<ApiMutationResponse>(
        response,
        'Gagal memperbarui status kendala target.',
    );
}

export async function apiDeleteTarget(
    targetId: string,
): Promise<ApiMutationResponse> {
    const response = await fetch(destroyTargetAction.url(Number(targetId)), {
        method: 'DELETE',
        headers: baseHeaders(false),
        credentials: 'same-origin',
    });

    return parseJsonResponse<ApiMutationResponse>(
        response,
        'Gagal menghapus target dari database MySQL.',
    );
}

export async function apiSaveNoteWithPhotos(
    payload: SaveNotePayload,
): Promise<ApiMutationResponse> {
    const formData = new FormData();

    if (payload.isEditing) {
        formData.append('_method', 'PUT');
    }

    formData.append('tracker_target_id', payload.targetId);
    formData.append('note_date', payload.date);
    formData.append('added_value', String(payload.addedValue));
    formData.append(
        'completed_step_ids',
        JSON.stringify(payload.completedStepIds),
    );
    formData.append('work_result', payload.workResult);
    formData.append('obstacle', payload.obstacle);
    formData.append('obstacle_action', payload.obstacleAction);
    formData.append('next_step', payload.nextStep);

    const existingPhotos = payload.draftPhotos
        .filter((photo) => photo.isExisting)
        .map((photo) => ({
            id: Number(photo.id),
            caption: photo.caption,
        }));
    formData.append('existing_photos', JSON.stringify(existingPhotos));

    const newPhotos = payload.draftPhotos.filter((photo) => !photo.isExisting);
    for (const photo of newPhotos) {
        const binary = photo.file ?? photo.blob;

        if (binary) {
            formData.append('new_photos[]', binary, photo.fileName);
            formData.append('new_photo_captions[]', photo.caption);
        }
    }

    const url =
        payload.isEditing && payload.id
            ? updateNoteAction.url(Number(payload.id))
            : storeNoteAction.url();

    const response = await fetch(url, {
        method: 'POST',
        headers: baseHeaders(false),
        credentials: 'same-origin',
        body: formData,
    });

    return parseJsonResponse<ApiMutationResponse>(
        response,
        'Gagal menyimpan catatan progress dan foto ke database MySQL.',
    );
}

export async function apiDeleteNote(
    noteId: string,
): Promise<ApiMutationResponse> {
    const response = await fetch(destroyNoteAction.url(Number(noteId)), {
        method: 'DELETE',
        headers: baseHeaders(false),
        credentials: 'same-origin',
    });

    return parseJsonResponse<ApiMutationResponse>(
        response,
        'Gagal menghapus catatan progress dari database MySQL.',
    );
}
