<script setup lang="ts">
import { ref } from 'vue';
import { AlertCircle, Hash, ListChecks, Pin, Plus, X } from '@lucide/vue';
import { closeDialogSafely } from '@/lib/dialogHelpers';
import {
    addDaysLocalYMD,
    defaultDueDateForPeriod,
    generateId,
    getLocalTodayYMD,
} from '@/lib/progressTracker';
import type {
    ChecklistStep,
    ProgressNote,
    TargetItem,
    TargetPeriod,
    TargetType,
} from '@/types';

type Props = {
    notesByTarget: Map<string, ProgressNote[]>;
    isSaving: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    save: [target: TargetItem, isEditing: boolean];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);
const isEditing = ref(false);
const stepDeleteConfirmId = ref<string | null>(null);
const errors = ref<Record<string, string>>({});

const form = ref<{
    id: string;
    title: string;
    description: string;
    period: TargetPeriod;
    startDate: string;
    dueDate: string;
    type: TargetType;
    targetValue: number | string;
    unit: string;
    steps: ChecklistStep[];
    currentValue: number;
    status: TargetItem['status'];
    hasActiveObstacle: boolean;
    manualObstacleNote: string;
    createdAt: string;
}>({
    id: '',
    title: '',
    description: '',
    period: 'Mingguan',
    startDate: getLocalTodayYMD(),
    dueDate: addDaysLocalYMD(getLocalTodayYMD(), 7),
    type: 'numeric',
    targetValue: 3,
    unit: 'sesi',
    steps: [{ id: generateId('step'), text: '' }],
    currentValue: 0,
    status: 'Belum Mulai',
    hasActiveObstacle: false,
    manualObstacleNote: '',
    createdAt: '',
});

function countNotesUsingStep(targetId: string, stepId: string): number {
    const notes = props.notesByTarget.get(targetId) ?? [];
    return notes.filter((n) => (n.completedStepIds ?? []).includes(stepId))
        .length;
}

function handlePeriodChange(newPeriod: TargetPeriod): void {
    form.value.period = newPeriod;
    if (!isEditing.value) {
        form.value.dueDate = defaultDueDateForPeriod(
            form.value.startDate,
            newPeriod,
        );
    }
    validateDates();
}

function validateDates(): boolean {
    const start = form.value.startDate;
    const due = form.value.dueDate;

    if (!start) {
        errors.value.startDate = 'Tanggal mulai wajib diisi.';
        return false;
    }
    delete errors.value.startDate;

    if (!due) {
        errors.value.dueDate = 'Tenggat wajib diisi.';
        return false;
    }

    if (due < start) {
        errors.value.dueDate =
            'Tenggat tidak boleh lebih awal dari tanggal mulai.';
        return false;
    }

    delete errors.value.dueDate;
    return true;
}

function addStepRow(): void {
    form.value.steps.push({
        id: generateId('step'),
        text: '',
    });
    delete errors.value.steps;
}

function requestRemoveStep(index: number): void {
    const step = form.value.steps[index];
    if (!step) {
        return;
    }

    if (form.value.steps.length <= 1) {
        errors.value.steps =
            'Target checklist wajib memiliki minimal satu langkah.';
        return;
    }

    const usageCount = isEditing.value
        ? countNotesUsingStep(form.value.id, step.id)
        : 0;

    if (usageCount > 0 && stepDeleteConfirmId.value !== step.id) {
        stepDeleteConfirmId.value = step.id;
        return;
    }

    form.value.steps.splice(index, 1);
    stepDeleteConfirmId.value = null;
}

function validateForm(): boolean {
    const errs: Record<string, string> = {};

    if (!form.value.title.trim()) {
        errs.title = 'Judul target wajib diisi.';
    }

    if (!form.value.startDate) {
        errs.startDate = 'Tanggal mulai wajib diisi.';
    }

    if (!form.value.dueDate) {
        errs.dueDate = 'Tenggat wajib diisi.';
    } else if (
        form.value.startDate &&
        form.value.dueDate < form.value.startDate
    ) {
        errs.dueDate = 'Tenggat tidak boleh lebih awal dari tanggal mulai.';
    }

    if (form.value.type === 'numeric') {
        const val = Number(form.value.targetValue);
        if (!Number.isFinite(val) || val <= 0) {
            errs.targetValue = 'Nilai target harus berupa angka positif (> 0).';
        }
        if (!form.value.unit.trim()) {
            errs.unit = 'Satuan target wajib diisi (misal: sesi, halaman, km).';
        }
    } else {
        const cleanedSteps = form.value.steps.filter(
            (s) => s.text.trim().length > 0,
        );
        if (cleanedSteps.length === 0) {
            errs.steps =
                'Target checklist wajib memiliki minimal satu langkah yang terisi.';
        }
    }

    errors.value = errs;
    return Object.keys(errs).length === 0;
}

function openCreate(): void {
    isEditing.value = false;
    stepDeleteConfirmId.value = null;
    errors.value = {};
    const today = getLocalTodayYMD();

    form.value = {
        id: generateId('tgt'),
        title: '',
        description: '',
        period: 'Mingguan',
        startDate: today,
        dueDate: addDaysLocalYMD(today, 7),
        type: 'numeric',
        targetValue: 3,
        unit: 'sesi',
        steps: [
            { id: generateId('step'), text: '' },
            { id: generateId('step'), text: '' },
        ],
        currentValue: 0,
        status: 'Belum Mulai',
        hasActiveObstacle: false,
        manualObstacleNote: '',
        createdAt: new Date().toISOString(),
    };

    dialogRef.value?.showModal();
}

function openEdit(target: TargetItem): void {
    isEditing.value = true;
    stepDeleteConfirmId.value = null;
    errors.value = {};

    form.value = {
        id: target.id,
        title: target.title,
        description: target.description || '',
        period: target.period,
        startDate: target.startDate,
        dueDate: target.dueDate,
        type: target.type,
        targetValue: target.type === 'numeric' ? target.targetValue : 3,
        unit: target.type === 'numeric' ? target.unit : 'sesi',
        steps:
            target.steps.length > 0
                ? target.steps.map((s) => ({ id: s.id, text: s.text }))
                : [{ id: generateId('step'), text: '' }],
        currentValue: target.currentValue,
        status: target.status,
        hasActiveObstacle: target.hasActiveObstacle,
        manualObstacleNote: target.manualObstacleNote,
        createdAt: target.createdAt,
    };

    dialogRef.value?.showModal();
}

function close(): void {
    closeDialogSafely(dialogRef.value);
}

function handleSubmit(): void {
    if (!validateForm()) {
        return;
    }

    const cleanedSteps: ChecklistStep[] =
        form.value.type === 'checklist'
            ? form.value.steps
                  .map((s) => ({ id: s.id, text: s.text.trim() }))
                  .filter((s) => s.text.length > 0)
            : [];

    const nowIso = new Date().toISOString();
    const targetPayload: TargetItem = {
        id: form.value.id,
        title: form.value.title.trim(),
        description: form.value.description.trim(),
        period: form.value.period,
        startDate: form.value.startDate,
        dueDate: form.value.dueDate,
        type: form.value.type,
        targetValue:
            form.value.type === 'numeric'
                ? Number(form.value.targetValue)
                : cleanedSteps.length,
        unit:
            form.value.type === 'numeric' ? form.value.unit.trim() : 'langkah',
        steps: cleanedSteps,
        currentValue: form.value.currentValue,
        status: form.value.status,
        hasActiveObstacle: form.value.hasActiveObstacle,
        manualObstacleNote: form.value.manualObstacleNote,
        createdAt: form.value.createdAt || nowIso,
        updatedAt: nowIso,
    };

    emit('save', targetPayload, isEditing.value);
}

defineExpose({ openCreate, openEdit, close });
</script>

<template>
    <dialog
        ref="dialogRef"
        aria-labelledby="target-dialog-title"
        class="m-auto max-h-[92vh] w-[94%] max-w-lg overflow-y-auto rounded-[28px] bg-[#F1EFF8] p-5 text-[#211D37] shadow-2xl sm:p-6"
    >
        <div class="flex items-center justify-between gap-2">
            <h2
                id="target-dialog-title"
                class="text-xl font-extrabold text-[#29294E]"
            >
                {{ isEditing ? 'Edit Target Personal' : 'Buat Target Baru' }}
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-[#29294E] shadow-2xs hover:bg-[#FAF9FE]"
                @click="close"
            >
                <X class="h-4 w-4" aria-hidden="true" />
                <span>Batal</span>
            </button>
        </div>

        <form class="mt-4 space-y-4" novalidate @submit.prevent="handleSubmit">
            <!-- Judul Target -->
            <div class="rounded-2xl bg-white p-4 shadow-2xs">
                <label
                    for="target-title-input"
                    class="block text-xs font-bold text-[#29294E]"
                >
                    Judul Target <span class="text-rose-600">*</span>
                </label>
                <input
                    id="target-title-input"
                    v-model="form.title"
                    type="text"
                    required
                    placeholder="Contoh: Latihan Lari Pagi, Membaca Buku, Riset UI..."
                    class="mt-1.5 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2.5 text-sm text-[#211D37] focus:border-[#494B97] focus:outline-none"
                    @input="delete errors.title"
                />
                <p
                    v-if="errors.title"
                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                >
                    <AlertCircle
                        class="h-3.5 w-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    <span>{{ errors.title }}</span>
                </p>

                <label
                    for="target-desc-input"
                    class="mt-3 block text-xs font-bold text-[#29294E]"
                >
                    Deskripsi (Opsional)
                </label>
                <textarea
                    id="target-desc-input"
                    v-model="form.description"
                    rows="2"
                    placeholder="Catatan tambahan mengenai tujuan atau kriteria selesai..."
                    class="mt-1.5 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37] focus:border-[#494B97] focus:outline-none"
                />
            </div>

            <!-- Periode & Rentang Tanggal -->
            <div class="rounded-2xl bg-white p-4 shadow-2xs">
                <span class="block text-xs font-bold text-[#29294E]">
                    Periode Target <span class="text-rose-600">*</span>
                </span>
                <div class="mt-1.5 grid grid-cols-3 gap-2">
                    <button
                        v-for="p in ['Harian', 'Mingguan', 'Bulanan'] as const"
                        :key="p"
                        type="button"
                        class="rounded-full py-2 text-xs font-bold transition"
                        :class="
                            form.period === p
                                ? 'bg-[#494B97] text-white shadow-xs'
                                : 'bg-[#F1EFF8] text-[#211D37] hover:bg-[#E2DDF4]'
                        "
                        @click="handlePeriodChange(p)"
                    >
                        {{ p }}
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label
                            for="target-start-date"
                            class="block text-xs font-bold text-[#29294E]"
                        >
                            Tanggal Mulai <span class="text-rose-600">*</span>
                        </label>
                        <input
                            id="target-start-date"
                            v-model="form.startDate"
                            type="date"
                            required
                            class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3 py-2 text-sm text-[#211D37]"
                            @change="validateDates"
                        />
                        <p
                            v-if="errors.startDate"
                            class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{{ errors.startDate }}</span>
                        </p>
                    </div>

                    <div>
                        <label
                            for="target-due-date"
                            class="block text-xs font-bold text-[#29294E]"
                        >
                            Tenggat <span class="text-rose-600">*</span>
                        </label>
                        <input
                            id="target-due-date"
                            v-model="form.dueDate"
                            type="date"
                            required
                            :min="form.startDate"
                            class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3 py-2 text-sm text-[#211D37]"
                            @change="validateDates"
                        />
                        <p
                            v-if="errors.dueDate"
                            class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{{ errors.dueDate }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Jenis Target: Angka vs Checklist -->
            <div class="rounded-2xl bg-white p-4 shadow-2xs">
                <span class="block text-xs font-bold text-[#29294E]">
                    Metode Pengukuran Capaian
                    <span class="text-rose-600">*</span>
                </span>
                <div class="mt-1.5 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-full py-2 text-xs font-bold transition"
                        :class="
                            form.type === 'numeric'
                                ? 'bg-[#494B97] text-white'
                                : 'bg-[#F1EFF8] text-[#211D37]'
                        "
                        @click="form.type = 'numeric'"
                    >
                        <Hash class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Target Angka</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-full py-2 text-xs font-bold transition"
                        :class="
                            form.type === 'checklist'
                                ? 'bg-[#494B97] text-white'
                                : 'bg-[#F1EFF8] text-[#211D37]'
                        "
                        @click="form.type = 'checklist'"
                    >
                        <ListChecks class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Checklist Langkah</span>
                    </button>
                </div>

                <!-- Kolom Target Angka -->
                <div
                    v-if="form.type === 'numeric'"
                    class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2"
                >
                    <div>
                        <label
                            for="target-numeric-value"
                            class="block text-xs font-bold text-[#29294E]"
                        >
                            Nilai Target Positif
                            <span class="text-rose-600">*</span>
                        </label>
                        <input
                            id="target-numeric-value"
                            v-model="form.targetValue"
                            type="number"
                            min="0.1"
                            step="any"
                            required
                            class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                            @input="delete errors.targetValue"
                        />
                        <p
                            v-if="errors.targetValue"
                            class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{{ errors.targetValue }}</span>
                        </p>
                    </div>

                    <div>
                        <label
                            for="target-numeric-unit"
                            class="block text-xs font-bold text-[#29294E]"
                        >
                            Satuan <span class="text-rose-600">*</span>
                        </label>
                        <input
                            id="target-numeric-unit"
                            v-model="form.unit"
                            type="text"
                            required
                            placeholder="sesi, halaman, km, jam..."
                            class="mt-1 w-full rounded-xl border border-[#C5BBE8] bg-[#FAF9FE] px-3.5 py-2 text-sm text-[#211D37]"
                            @input="delete errors.unit"
                        />
                        <p
                            v-if="errors.unit"
                            class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{{ errors.unit }}</span>
                        </p>
                    </div>
                </div>

                <!-- Kolom Target Checklist -->
                <div v-else class="mt-4 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#29294E]">
                            Daftar Langkah Checklist (Minimal 1 langkah)
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full bg-[#F8DF75] px-3 py-1 text-xs font-extrabold text-[#211D37]"
                            @click="addStepRow"
                        >
                            <Plus
                                class="h-3.5 w-3.5 stroke-[2.5]"
                                aria-hidden="true"
                            />
                            <span>Tambah Langkah</span>
                        </button>
                    </div>

                    <p v-if="isEditing" class="text-[11px] text-[#211D37]/70">
                        Identitas langkah yang sudah dipakai catatan
                        dipertahankan agar riwayat tetap akurat.
                    </p>

                    <div
                        v-for="(step, idx) in form.steps"
                        :key="step.id"
                        class="rounded-xl border border-[#E5E0F5] bg-[#FAF9FE] p-2.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#E6E2F5] text-xs font-bold text-[#29294E]"
                            >
                                {{ idx + 1 }}
                            </span>
                            <input
                                v-model="step.text"
                                type="text"
                                :placeholder="`Langkah ke-${idx + 1}...`"
                                class="w-full rounded-lg border border-[#C5BBE8] bg-white px-3 py-1.5 text-xs text-[#211D37] sm:text-sm"
                                @input="delete errors.steps"
                            />
                            <button
                                type="button"
                                class="shrink-0 rounded-full bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100"
                                @click="requestRemoveStep(idx)"
                            >
                                Hapus
                            </button>
                        </div>

                        <!-- Peringatan jika langkah sudah dipakai di riwayat -->
                        <div
                            v-if="
                                isEditing &&
                                countNotesUsingStep(form.id, step.id) > 0
                            "
                            class="mt-1.5 flex flex-wrap items-center justify-between gap-2 pl-8 text-[11px]"
                        >
                            <span
                                class="inline-flex items-center gap-1 font-semibold text-[#494B97]"
                            >
                                <Pin
                                    class="h-3 w-3 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>
                                    Sudah tercatat di
                                    {{ countNotesUsingStep(form.id, step.id) }}
                                    catatan riwayat
                                </span>
                            </span>

                            <span
                                v-if="stepDeleteConfirmId === step.id"
                                class="font-bold text-rose-700"
                            >
                                Klik "Hapus" sekali lagi untuk konfirmasi hapus
                                langkah ini.
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="errors.steps"
                        class="inline-flex items-center gap-1 text-xs font-bold text-rose-700"
                    >
                        <AlertCircle
                            class="h-3.5 w-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        <span>{{ errors.steps }}</span>
                    </p>
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
                        ? 'Menyimpan...'
                        : isEditing
                          ? 'Simpan Perubahan Target'
                          : 'Simpan Target Baru'
                }}
            </button>
        </form>
    </dialog>
</template>
