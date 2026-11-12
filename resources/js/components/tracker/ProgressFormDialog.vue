<script setup lang="ts">
import { computed, ref } from 'vue';
import { closeDialogSafely } from '@/lib/dialogHelpers';
import { generateId, getLocalTodayYMD } from '@/lib/progressTracker';
import type {
    DraftPhoto,
    ObstacleAction,
    PhotoRecord,
    ProgressNote,
    SaveNotePayload,
    TargetItem,
} from '@/types';

type Props = {
    targets: TargetItem[];
    notesByTarget: Map<string, ProgressNote[]>;
    photoUrls: Record<string, string>;
    orderedPhotosForNote: (note: ProgressNote) => PhotoRecord[];
    isSaving: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    save: [payload: SaveNotePayload];
    errorMessage: [message: string];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);
const isEditing = ref(false);
const errors = ref<Record<string, string>>({});

const form = ref<{
    id: string;
    targetId: string;
    date: string;
    addedValue: number | string;
    completedStepIds: string[];
    workResult: string;
    obstacle: string;
    obstacleAction: ObstacleAction;
    nextStep: string;
}>({
    id: '',
    targetId: '',
    date: getLocalTodayYMD(),
    addedValue: 1,
    completedStepIds: [],
    workResult: '',
    obstacle: '',
    obstacleAction: 'keep',
    nextStep: '',
});

const draftPhotos = ref<DraftPhoto[]>([]);

const currentTarget = computed<TargetItem | null>(() => {
    return props.targets.find((t) => t.id === form.value.targetId) ?? null;
});

const stepsCompletedInOtherNotes = computed(() => {
    const target = currentTarget.value;
    if (!target || target.type !== 'checklist') {
        return new Set<string>();
    }

    const notes = props.notesByTarget.get(target.id) ?? [];
    const completed = new Set<string>();

    for (const note of notes) {
        if (isEditing.value && note.id === form.value.id) {
            continue;
        }
        for (const stepId of note.completedStepIds ?? []) {
            completed.add(stepId);
        }
    }

    return completed;
});

function cleanupNewPhotoUrls(): void {
    for (const photo of draftPhotos.value) {
        if (!photo.isExisting && photo.previewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(photo.previewUrl);
        }
    }
}

function openCreate(preselectedTargetId?: string): void {
    if (props.targets.length === 0) {
        emit(
            'errorMessage',
            'Buat target terlebih dahulu sebelum mencatat progress.',
        );
        return;
    }

    cleanupNewPhotoUrls();
    draftPhotos.value = [];
    errors.value = {};
    isEditing.value = false;

    const chosenTargetId = preselectedTargetId ?? props.targets[0]?.id ?? '';

    form.value = {
        id: generateId('note'),
        targetId: chosenTargetId,
        date: getLocalTodayYMD(),
        addedValue: 1,
        completedStepIds: [],
        workResult: '',
        obstacle: '',
        obstacleAction: 'keep',
        nextStep: '',
    };

    dialogRef.value?.showModal();
}

function openEdit(note: ProgressNote): void {
    cleanupNewPhotoUrls();
    errors.value = {};
    isEditing.value = true;

    const existingPhotos = props.orderedPhotosForNote(note);
    draftPhotos.value = existingPhotos.map((photo) => ({
        id: photo.id,
        blob: photo.blob,
        mimeType: photo.mimeType,
        fileName: photo.fileName,
        caption: photo.caption ?? '',
        previewUrl: props.photoUrls[photo.id] || photo.url || '',
        isExisting: true,
        createdAt: photo.createdAt,
    }));

    form.value = {
        id: note.id,
        targetId: note.targetId,
        date: note.date,
        addedValue: note.addedValue,
        completedStepIds: [...(note.completedStepIds ?? [])],
        workResult: note.workResult ?? '',
        obstacle: note.obstacle ?? '',
        obstacleAction: note.markAsObstacle
            ? 'mark'
            : note.resolveObstacle
              ? 'resolve'
              : 'keep',
        nextStep: note.nextStep ?? '',
    };

    dialogRef.value?.showModal();
}

function close(): void {
    cleanupNewPhotoUrls();
    closeDialogSafely(dialogRef.value);
}

function handlePhotoFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = input.files;
    if (!files || files.length === 0) {
        return;
    }

    for (const file of Array.from(files)) {
        const previewUrl = URL.createObjectURL(file);
        draftPhotos.value.push({
            id: generateId('pho'),
            file,
            mimeType: file.type || 'image/jpeg',
            fileName: file.name,
            caption: '',
            previewUrl,
            isExisting: false,
            createdAt: new Date().toISOString(),
        });
    }

    input.value = '';
}

function removeDraftPhoto(index: number): void {
    const item = draftPhotos.value[index];
    if (!item) {
        return;
    }

    if (!item.isExisting && item.previewUrl.startsWith('blob:')) {
        URL.revokeObjectURL(item.previewUrl);
    }

    draftPhotos.value.splice(index, 1);
}

function validateForm(): boolean {
    const errs: Record<string, string> = {};
    const target = currentTarget.value;

    if (!target) {
        errs.targetId = 'Pilih target terlebih dahulu.';
    }

    if (!form.value.date) {
        errs.date = 'Tanggal catatan wajib diisi.';
    }

    const hasWork = form.value.workResult.trim().length > 0;
    const hasObstacle = form.value.obstacle.trim().length > 0;
    const addedVal = Number(form.value.addedValue);

    if (target?.type === 'numeric') {
        if (!Number.isFinite(addedVal) || addedVal < 0) {
            errs.addedValue =
                'Tambahan capaian harus berupa angka 0 atau lebih besar.';
        } else if (addedVal === 0 && !hasObstacle && !hasWork) {
            errs.workResult =
                'Untuk capaian 0, tuliskan kendala yang dihadapi atau hasil pekerjaan.';
        }
    } else if (target?.type === 'checklist') {
        if (
            form.value.completedStepIds.length === 0 &&
            !hasObstacle &&
            !hasWork
        ) {
            errs.workResult =
                'Pilih minimal satu langkah selesai, atau isi hasil pekerjaan / laporan kendala.';
        }
    }

    const hasAchievement =
        target?.type === 'checklist'
            ? form.value.completedStepIds.length > 0
            : addedVal > 0;

    if (!hasWork && !hasObstacle && !hasAchievement && !errs.workResult) {
        errs.workResult =
            'Isi minimal Hasil Pekerjaan, Kendala, atau tambahan capaian.';
    }

    errors.value = errs;
    return Object.keys(errs).length === 0;
}

function handleSubmit(): void {
    if (!validateForm()) {
        return;
    }

    const target = currentTarget.value;
    if (!target) {
        return;
    }

    const payload: SaveNotePayload = {
        id: isEditing.value ? form.value.id : undefined,
        isEditing: isEditing.value,
        targetId: form.value.targetId,
        date: form.value.date,
        addedValue:
            target.type === 'numeric'
                ? Math.round(Number(form.value.addedValue) * 100) / 100
                : form.value.completedStepIds.length,
        completedStepIds:
            target.type === 'checklist' ? [...form.value.completedStepIds] : [],
        workResult: form.value.workResult.trim(),
        obstacle: form.value.obstacle.trim(),
        obstacleAction: form.value.obstacleAction,
        nextStep: form.value.nextStep.trim(),
        draftPhotos: draftPhotos.value,
    };

    emit('save', payload);
}

defineExpose({ openCreate, openEdit, close });
</script>

<template>
    <dialog
        ref="dialogRef"
        aria-labelledby="progress-dialog-title"
        class="m-auto max-h-[92vh] w-[94%] max-w-lg overflow-y-auto rounded-[28px] bg-[#F1EFF8] p-5 text-[#211D37] shadow-2xl sm:p-6"
    >
        <div class="flex items-center justify-between gap-2">
            <h2
                id="progress-dialog-title"
                class="text-xl font-extrabold text-[#29294E]"
            >
                {{
                    isEditing
                        ? 'Edit Catatan Progress'
                        : 'Tambah Catatan Progress'
                }}
            </h2>
            <button
                type="button"
                class="rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-[#29294E] shadow-2xs hover:bg-[#FAF9FE]"
                @click="close"
            >
                ✕ Batal
            </button>
        </div>

        <form class="mt-4 space-y-4" novalidate @submit.prevent="handleSubmit">
            <!-- Target & Tanggal -->
            <div class="space-y-3 rounded-2xl bg-white p-4 shadow-2xs">
                <div>
                    <label
                        for="progress-target-select"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Target <span class="text-rose-600">*</span>
                    </label>
                    <select
                        id="progress-target-select"
                        v-model="form.targetId"
                        :disabled="isEditing"
                        class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2.5 text-sm font-semibold text-[#211D37] disabled:opacity-70"
                    >
                        <option v-for="t in targets" :key="t.id" :value="t.id">
                            [{{ t.period }}] {{ t.title }} ({{
                                t.currentValue
                            }}/{{ t.targetValue }} {{ t.unit }})
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="progress-date-input"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Tanggal Catatan (Kalender Lokal)
                        <span class="text-rose-600">*</span>
                    </label>
                    <input
                        id="progress-date-input"
                        v-model="form.date"
                        type="date"
                        required
                        class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                    />
                    <p
                        v-if="errors.date"
                        class="mt-1 text-xs font-bold text-rose-700"
                    >
                        ⚠ {{ errors.date }}
                    </p>
                </div>
            </div>

            <!-- Capaian: Angka vs Checklist -->
            <div
                v-if="currentTarget"
                class="rounded-2xl bg-white p-4 shadow-2xs"
            >
                <template v-if="currentTarget.type === 'numeric'">
                    <label
                        for="progress-added-value"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Tambahan Capaian ({{ currentTarget.unit }})
                        <span class="text-rose-600">*</span>
                    </label>
                    <p class="mt-0.5 text-[11px] text-[#211D37]/70">
                        Isi <strong>0</strong> jika Anda sedang melaporkan
                        kendala tanpa tambahan capaian angka.
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <input
                            id="progress-added-value"
                            v-model="form.addedValue"
                            type="number"
                            min="0"
                            step="any"
                            required
                            class="w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm font-bold text-[#211D37]"
                            @input="delete errors.addedValue"
                        />
                        <span
                            class="shrink-0 rounded-xl bg-[#F1EFF8] px-3.5 py-2 text-xs font-bold text-[#494B97]"
                        >
                            {{ currentTarget.unit }}
                        </span>
                    </div>
                    <p
                        v-if="errors.addedValue"
                        class="mt-1.5 text-xs font-bold text-rose-700"
                    >
                        ⚠ {{ errors.addedValue }}
                    </p>
                </template>

                <template v-else>
                    <div class="text-xs font-bold text-[#29294E]">
                        Pilih Langkah Checklist yang Diselesaikan
                    </div>
                    <p class="mt-0.5 text-[11px] text-[#211D37]/70">
                        Boleh dikosongkan jika hanya melaporkan kendala. Langkah
                        yang dicentang ulang tetap dihitung 1 kali (unik) pada
                        total capaian.
                    </p>
                    <div class="mt-2.5 space-y-2">
                        <label
                            v-for="step in currentTarget.steps"
                            :key="step.id"
                            class="flex cursor-pointer items-start justify-between gap-2.5 rounded-xl border border-[#E5E0F5] bg-[#FAF9FE] p-3 text-xs hover:bg-[#F1EFF8]"
                        >
                            <div class="flex items-start gap-2.5">
                                <input
                                    v-model="form.completedStepIds"
                                    type="checkbox"
                                    :value="step.id"
                                    class="mt-0.5 h-4 w-4 rounded border-[#494B97] text-[#494B97]"
                                />
                                <span
                                    class="font-semibold break-words text-[#211D37]"
                                >
                                    {{ step.text }}
                                </span>
                            </div>
                            <span
                                v-if="stepsCompletedInOtherNotes.has(step.id)"
                                class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-900"
                            >
                                ✓ Sudah di catatan lain
                            </span>
                        </label>
                    </div>
                </template>
            </div>

            <!-- Hasil, Kendala, Langkah Berikutnya -->
            <div class="space-y-3 rounded-2xl bg-white p-4 shadow-2xs">
                <div>
                    <label
                        for="progress-work-result"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Hasil Pekerjaan
                    </label>
                    <textarea
                        id="progress-work-result"
                        v-model="form.workResult"
                        rows="2"
                        placeholder="Apa hasil yang dikerjakan pada sesi ini?"
                        class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                        @input="delete errors.workResult"
                    />
                    <p
                        v-if="errors.workResult"
                        class="mt-1 text-xs font-bold text-rose-700"
                    >
                        ⚠ {{ errors.workResult }}
                    </p>
                </div>

                <div>
                    <label
                        for="progress-obstacle"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Kendala (Opsional / Isi jika ada hambatan)
                    </label>
                    <textarea
                        id="progress-obstacle"
                        v-model="form.obstacle"
                        rows="2"
                        placeholder="Tuliskan kendala jika ada hambatan..."
                        class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                    />

                    <!-- Pilihan dampak kendala terhadap status target -->
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-semibold text-[#211D37]/80">
                            Status Kendala Target:
                        </span>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 font-bold transition"
                            :class="
                                form.obstacleAction === 'keep'
                                    ? 'bg-[#494B97] text-white'
                                    : 'bg-[#F1EFF8] text-[#211D37]'
                            "
                            @click="form.obstacleAction = 'keep'"
                        >
                            Otomatis
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 font-bold transition"
                            :class="
                                form.obstacleAction === 'mark'
                                    ? 'bg-amber-600 text-white'
                                    : 'bg-amber-50 text-amber-900'
                            "
                            @click="form.obstacleAction = 'mark'"
                        >
                            ⚠ Tandai Terkendala
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 font-bold transition"
                            :class="
                                form.obstacleAction === 'resolve'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-emerald-50 text-emerald-900'
                            "
                            @click="form.obstacleAction = 'resolve'"
                        >
                            ✓ Kendala Teratasi
                        </button>
                    </div>
                </div>

                <div>
                    <label
                        for="progress-next-step"
                        class="block text-xs font-bold text-[#29294E]"
                    >
                        Langkah Berikutnya
                    </label>
                    <input
                        id="progress-next-step"
                        v-model="form.nextStep"
                        type="text"
                        placeholder="Apa rencana tindak lanjut berikutnya?"
                        class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                    />
                </div>
            </div>

            <!-- Upload Foto Bukti -->
            <div class="rounded-2xl bg-white p-4 shadow-2xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <label
                            for="progress-photos-input"
                            class="block text-xs font-bold text-[#29294E]"
                        >
                            📷 Foto Bukti (Bisa beberapa foto)
                        </label>
                        <p class="text-[11px] text-[#211D37]/70">
                            Foto bukti disimpan ke penyimpanan server MySQL
                            secara langsung.
                        </p>
                    </div>

                    <label
                        for="progress-photos-input"
                        class="cursor-pointer rounded-full bg-[#F8DF75] px-3.5 py-2 text-xs font-extrabold text-[#211D37] shadow-2xs hover:brightness-95"
                    >
                        + Pilih Foto
                    </label>
                    <input
                        id="progress-photos-input"
                        type="file"
                        accept="image/*"
                        multiple
                        class="sr-only"
                        @change="handlePhotoFilesSelected"
                    />
                </div>

                <!-- Grid Pratinjau Foto -->
                <div
                    v-if="draftPhotos.length > 0"
                    class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2"
                >
                    <div
                        v-for="(item, idx) in draftPhotos"
                        :key="item.id"
                        class="overflow-hidden rounded-2xl border border-[#C5BBE8] bg-[#FAF9FE] p-2.5"
                    >
                        <div
                            class="relative aspect-4/3 w-full overflow-hidden rounded-xl bg-[#29294E]/10"
                        >
                            <img
                                :src="item.previewUrl"
                                :alt="item.caption || item.fileName"
                                class="h-full w-full object-cover"
                            />
                            <button
                                type="button"
                                class="absolute top-2 right-2 rounded-full bg-rose-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm hover:bg-rose-700"
                                @click="removeDraftPhoto(idx)"
                            >
                                ✕ Hapus Foto
                            </button>
                        </div>
                        <div class="mt-2">
                            <label
                                :for="`photo-caption-${item.id}`"
                                class="block text-[11px] font-bold text-[#29294E]"
                            >
                                Keterangan Foto {{ idx + 1 }} (Opsional)
                            </label>
                            <input
                                :id="`photo-caption-${item.id}`"
                                v-model="item.caption"
                                type="text"
                                placeholder="Tulis keterangan singkat foto ini..."
                                class="mt-1 w-full rounded-lg border border-[#C5BBE8] bg-white px-2.5 py-1.5 text-xs text-[#211D37]"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan Ungu Selebar Panel -->
            <button
                type="submit"
                :disabled="isSaving"
                class="w-full rounded-full bg-[#494B97] py-3.5 text-sm font-extrabold text-white shadow-md transition hover:bg-[#29294E] disabled:opacity-50"
            >
                {{
                    isSaving
                        ? 'Menyimpan Catatan & Foto...'
                        : isEditing
                          ? 'Simpan Perubahan Catatan'
                          : 'Simpan Catatan Progress'
                }}
            </button>
        </form>
    </dialog>
</template>
