<script setup lang="ts">
import {
    AlertTriangle,
    ArrowRight,
    Calendar,
    Camera,
    Check,
    CheckCircle2,
    Pencil,
    Target,
    Trash2,
} from '@lucide/vue';
import PhotoGallery from '@/components/tracker/PhotoGallery.vue';
import { formatIndonesianDate } from '@/lib/progressTracker';
import type { PhotoRecord, ProgressNote, TargetItem } from '@/types';

type Props = {
    note: ProgressNote;
    target: TargetItem;
    photos: PhotoRecord[];
    photoUrls: Record<string, string>;
    showTargetLink?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showTargetLink: false,
});

const emit = defineEmits<{
    openTarget: [targetId: string];
    edit: [note: ProgressNote];
    delete: [note: ProgressNote];
    openPhoto: [note: ProgressNote, photoId: string];
}>();

function resolveStepLabel(stepId: string): string {
    const step = props.target.steps.find((item) => item.id === stepId);

    if (step) {
        return step.text;
    }

    if (props.note.stepSnapshots?.[stepId]) {
        return `${props.note.stepSnapshots[stepId]} (Langkah dihapus dari target)`;
    }

    return 'Langkah selesai';
}
</script>

<template>
    <article
        class="rounded-[20px] bg-white p-5 shadow-sm ring-1 ring-[#29294E]/6"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F1EFF8] pb-3"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#29294E] px-3 py-1 text-xs font-bold text-white"
                >
                    <Calendar class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span>{{ formatIndonesianDate(note.date) }}</span>
                </span>

                <button
                    v-if="showTargetLink"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#F1EFF8] px-3 py-1 text-xs font-bold text-[#494B97] hover:bg-[#E2DDF4]"
                    @click="emit('openTarget', target.id)"
                >
                    <Target class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span>{{ target.title }}</span>
                    <ArrowRight
                        class="h-3.5 w-3.5 shrink-0"
                        aria-hidden="true"
                    />
                </button>

                <span
                    v-if="target.type === 'numeric'"
                    class="rounded-full px-3 py-1 text-xs font-extrabold"
                    :class="
                        note.addedValue > 0
                            ? 'bg-[#E6E2F5] text-[#29294E]'
                            : 'bg-amber-100 text-amber-900'
                    "
                >
                    +{{ note.addedValue }} {{ target.unit }}
                    {{
                        note.addedValue === 0
                            ? '(Laporan Kendala / Catatan)'
                            : ''
                    }}
                </span>

                <span
                    v-else
                    class="rounded-full px-3 py-1 text-xs font-extrabold"
                    :class="
                        note.completedStepIds.length > 0
                            ? 'bg-[#E6E2F5] text-[#29294E]'
                            : 'bg-amber-100 text-amber-900'
                    "
                >
                    {{ note.completedStepIds.length }} langkah dicatat
                </span>

                <span
                    v-if="note.markAsObstacle"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-900"
                >
                    <AlertTriangle
                        class="h-3 w-3 shrink-0"
                        aria-hidden="true"
                    />
                    <span>Menandai Kendala</span>
                </span>
                <span
                    v-else-if="note.resolveObstacle"
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-900"
                >
                    <CheckCircle2 class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <span>Menyelesaikan Kendala</span>
                </span>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-[#F1EFF8] px-3 py-1.5 text-xs font-semibold text-[#29294E] hover:bg-[#E2DDF4]"
                    @click="emit('edit', note)"
                >
                    <Pencil class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <span>Edit</span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                    @click="emit('delete', note)"
                >
                    <Trash2 class="h-3 w-3 shrink-0" aria-hidden="true" />
                    <span>Hapus</span>
                </button>
            </div>
        </div>

        <div
            v-if="
                target.type === 'checklist' && note.completedStepIds.length > 0
            "
            class="mt-3 rounded-xl bg-[#F1EFF8]/70 p-3"
        >
            <div class="text-[11px] font-bold text-[#494B97] uppercase">
                Langkah yang Diselesaikan:
            </div>
            <ul class="mt-1.5 flex flex-wrap gap-1.5">
                <li
                    v-for="stepId in note.completedStepIds"
                    :key="stepId"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#211D37] shadow-2xs"
                >
                    <Check
                        class="h-3 w-3 shrink-0 stroke-[3] text-emerald-600"
                        aria-hidden="true"
                    />
                    <span>{{ resolveStepLabel(stepId) }}</span>
                </li>
            </ul>
        </div>

        <div class="mt-3.5 space-y-2.5 text-xs sm:text-sm">
            <div v-if="note.workResult">
                <span class="font-bold text-[#29294E]">Hasil Pekerjaan:</span>
                <p class="mt-0.5 leading-relaxed break-words text-[#211D37]">
                    {{ note.workResult }}
                </p>
            </div>

            <div
                v-if="note.obstacle"
                class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-950"
            >
                <span class="inline-flex items-center gap-1 font-bold">
                    <AlertTriangle
                        class="h-3.5 w-3.5 shrink-0 text-amber-600"
                        aria-hidden="true"
                    />
                    <span>Kendala:</span>
                </span>
                <p class="mt-0.5 leading-relaxed break-words">
                    {{ note.obstacle }}
                </p>
            </div>

            <div
                v-if="note.nextStep"
                class="rounded-xl bg-[#F1EFF8] p-3 text-[#211D37]"
            >
                <span
                    class="inline-flex items-center gap-1 font-bold text-[#494B97]"
                >
                    <ArrowRight
                        class="h-3.5 w-3.5 shrink-0 text-[#494B97]"
                        aria-hidden="true"
                    />
                    <span>Langkah Berikutnya:</span>
                </span>
                <p class="mt-0.5 leading-relaxed break-words">
                    {{ note.nextStep }}
                </p>
            </div>
        </div>

        <div
            v-if="photos.length > 0"
            class="mt-4 border-t border-[#F1EFF8] pt-3.5"
        >
            <div
                class="mb-2 flex items-center gap-1.5 text-xs font-bold text-[#494B97]"
            >
                <Camera class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                <span
                    >Foto Bukti ({{ photos.length }}) — Klik untuk
                    memperbesar</span
                >
            </div>
            <PhotoGallery
                :photos="photos"
                :photo-urls="photoUrls"
                :compact="showTargetLink"
                @open="(photoId) => emit('openPhoto', note, photoId)"
            />
        </div>
    </article>
</template>
