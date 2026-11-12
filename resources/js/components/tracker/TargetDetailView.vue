<script setup lang="ts">
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Check,
    CheckCircle2,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/tracker/EmptyState.vue';
import NoteCard from '@/components/tracker/NoteCard.vue';
import OverdueBadge from '@/components/tracker/OverdueBadge.vue';
import StatusBadge from '@/components/tracker/StatusBadge.vue';
import TargetProgressBar from '@/components/tracker/TargetProgressBar.vue';
import TrackerScreen from '@/components/tracker/TrackerScreen.vue';
import { formatIndonesianDate } from '@/lib/progressTracker';
import type {
    PhotoRecord,
    ProgressNote,
    TargetItem,
    TargetMetrics,
} from '@/types';

type Props = {
    target: TargetItem;
    metrics: TargetMetrics;
    notes: ProgressNote[];
    photoUrls: Record<string, string>;
    getNotePhotos: (note: ProgressNote) => PhotoRecord[];
};

defineProps<Props>();

const emit = defineEmits<{
    back: [];
    editTarget: [target: TargetItem];
    deleteTarget: [target: TargetItem];
    toggleObstacle: [target: TargetItem, markObstacle: boolean];
    addProgress: [targetId: string];
    editNote: [note: ProgressNote];
    deleteNote: [note: ProgressNote];
    openPhoto: [note: ProgressNote, photoId: string];
}>();
</script>

<template>
    <TrackerScreen width="narrow">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-2 text-xs font-bold text-white hover:bg-white/25 sm:text-sm"
                    @click="emit('back')"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Kembali ke Beranda</span>
                </button>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-2 text-xs font-semibold text-white hover:bg-white/25"
                        @click="emit('editTarget', target)"
                    >
                        <Pencil class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Edit Target</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/80 px-3.5 py-2 text-xs font-semibold text-white hover:bg-rose-600"
                        @click="emit('deleteTarget', target)"
                    >
                        <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Hapus Target</span>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span
                    class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white"
                >
                    {{ target.period }}
                </span>
                <span
                    class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white"
                >
                    {{
                        target.type === 'numeric'
                            ? 'Target Angka'
                            : 'Target Checklist'
                    }}
                </span>
            </div>

            <h1
                class="mt-2.5 text-2xl font-bold tracking-tight break-words sm:text-3xl"
            >
                {{ target.title }}
            </h1>
            <p v-if="target.description" class="mt-1.5 text-sm text-white/85">
                {{ target.description }}
            </p>
            <p class="mt-2 text-xs text-white/80">
                Tanggal Mulai:
                <strong>{{ formatIndonesianDate(target.startDate) }}</strong>
                · Tenggat:
                <strong>{{ formatIndonesianDate(target.dueDate) }}</strong>
            </p>
        </template>

        <section
            aria-label="Status dan capaian target"
            class="rounded-[20px] bg-white p-5 shadow-sm ring-1 ring-[#29294E]/6"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :status="metrics.status" />
                    <OverdueBadge
                        v-if="metrics.isOverdue"
                        :label="`Lewat Tenggat (${formatIndonesianDate(target.dueDate)})`"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="
                            metrics.status === 'Terkendala' ||
                            target.hasActiveObstacle
                        "
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-900 hover:bg-emerald-100"
                        @click="emit('toggleObstacle', target, false)"
                    >
                        <CheckCircle2
                            class="h-3.5 w-3.5 text-emerald-700"
                            aria-hidden="true"
                        />
                        <span>Tandai Kendala Teratasi</span>
                    </button>
                    <button
                        v-else-if="metrics.status !== 'Selesai'"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3.5 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100"
                        @click="emit('toggleObstacle', target, true)"
                    >
                        <AlertTriangle
                            class="h-3.5 w-3.5 text-amber-600"
                            aria-hidden="true"
                        />
                        <span>Tandai Sedang Terkendala</span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#F8DF75] px-4 py-2 text-xs font-extrabold text-[#211D37] shadow-xs hover:brightness-95 sm:text-sm"
                        @click="emit('addProgress', target.id)"
                    >
                        <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        <span>Tambah Progress</span>
                    </button>
                </div>
            </div>

            <TargetProgressBar
                class="mt-4"
                :target="target"
                :metrics="metrics"
                size="lg"
            />

            <div
                v-if="target.type === 'checklist'"
                class="mt-4 rounded-2xl border border-[#E5E0F5] bg-[#FAF9FE] p-4"
            >
                <h3
                    class="text-xs font-bold tracking-wider text-[#29294E] uppercase"
                >
                    Daftar Langkah Checklist ({{ metrics.currentValue }}/{{
                        target.steps.length
                    }}
                    Selesai Unik)
                </h3>
                <ul class="mt-2.5 space-y-2">
                    <li
                        v-for="(step, idx) in target.steps"
                        :key="step.id"
                        class="flex items-start justify-between gap-3 rounded-xl bg-white px-3.5 py-2.5 text-xs shadow-2xs"
                    >
                        <div class="flex items-start gap-2.5">
                            <span
                                class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                :class="
                                    metrics.completedStepIds.has(step.id)
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-[#F1EFF8] text-[#494B97]'
                                "
                            >
                                <Check
                                    v-if="metrics.completedStepIds.has(step.id)"
                                    class="h-3.5 w-3.5 stroke-[3]"
                                    aria-hidden="true"
                                />
                                <span v-else>{{ idx + 1 }}</span>
                            </span>
                            <span
                                class="font-medium break-words"
                                :class="
                                    metrics.completedStepIds.has(step.id)
                                        ? 'text-emerald-950 line-through decoration-emerald-500/60'
                                        : 'text-[#211D37]'
                                "
                            >
                                {{ step.text }}
                            </span>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                            :class="
                                metrics.completedStepIds.has(step.id)
                                    ? 'bg-emerald-100 text-emerald-900'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{
                                metrics.completedStepIds.has(step.id)
                                    ? 'Selesai'
                                    : 'Belum'
                            }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div
                    class="rounded-2xl border p-3.5"
                    :class="
                        metrics.lastObstacle
                            ? 'border-amber-200 bg-amber-50/80 text-amber-950'
                            : 'border-[#F1EFF8] bg-[#F1EFF8]/50 text-[#211D37]'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-bold"
                        >
                            <AlertTriangle
                                class="h-3.5 w-3.5 text-amber-600"
                                aria-hidden="true"
                            />
                            <span>Kendala Terakhir</span>
                        </span>
                        <span
                            v-if="metrics.lastObstacle"
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :class="
                                target.hasActiveObstacle
                                    ? 'bg-amber-200 text-amber-950'
                                    : 'bg-emerald-100 text-emerald-900'
                            "
                        >
                            {{
                                target.hasActiveObstacle ? 'Aktif' : 'Teratasi'
                            }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed">
                        {{
                            metrics.lastObstacle ||
                            'Belum ada kendala yang dilaporkan pada target ini.'
                        }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-[#D9D2F0] bg-[#F1EFF8] p-3.5 text-[#211D37]"
                >
                    <div
                        class="flex items-center gap-1.5 text-xs font-bold text-[#29294E]"
                    >
                        <ArrowRight
                            class="h-3.5 w-3.5 text-[#494B97]"
                            aria-hidden="true"
                        />
                        <span>Langkah Berikutnya</span>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed">
                        {{ metrics.nextStepText }}
                    </p>
                </div>
            </div>
        </section>

        <section aria-label="Riwayat catatan progress">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-[#211D37]">
                        Riwayat Catatan Progress ({{ notes.length }})
                    </h2>
                    <p class="text-xs text-[#211D37]/70">
                        Diurutkan dari catatan terbaru. Klik foto untuk
                        memperbesar tampilan.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#F8DF75] px-4 py-2 text-xs font-extrabold text-[#211D37] shadow-xs hover:brightness-95"
                    @click="emit('addProgress', target.id)"
                >
                    <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                    <span>Catat Progress Baru</span>
                </button>
            </div>

            <EmptyState
                v-if="notes.length === 0"
                title="Belum ada catatan progress untuk target ini"
                description="Catat hasil pekerjaan, langkah yang selesai, kendala, serta unggah foto bukti perkembangan Anda."
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#F8DF75] px-5 py-2.5 text-xs font-extrabold text-[#211D37] shadow-xs hover:brightness-95"
                    @click="emit('addProgress', target.id)"
                >
                    <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                    <span>Tambah Catatan Pertama</span>
                </button>
            </EmptyState>

            <div v-else class="space-y-4">
                <NoteCard
                    v-for="note in notes"
                    :key="note.id"
                    :note="note"
                    :target="target"
                    :photos="getNotePhotos(note)"
                    :photo-urls="photoUrls"
                    @edit="(item) => emit('editNote', item)"
                    @delete="(item) => emit('deleteNote', item)"
                    @open-photo="
                        (noteItem, photoId) =>
                            emit('openPhoto', noteItem, photoId)
                    "
                />
            </div>
        </section>
    </TrackerScreen>
</template>
