<script setup lang="ts">
import OverdueBadge from '@/components/tracker/OverdueBadge.vue';
import StatusBadge from '@/components/tracker/StatusBadge.vue';
import TargetProgressBar from '@/components/tracker/TargetProgressBar.vue';
import { formatIndonesianDate } from '@/lib/progressTracker';
import type { TargetItem, TargetMetrics } from '@/types';

type Props = {
    target: TargetItem;
    metrics: TargetMetrics;
    noteCount: number;
};

defineProps<Props>();

const emit = defineEmits<{
    open: [targetId: string];
    edit: [target: TargetItem];
    addProgress: [targetId: string];
}>();
</script>

<template>
    <article
        class="flex flex-col justify-between rounded-[20px] bg-white p-5 shadow-sm ring-1 ring-[#29294E]/6 transition hover:shadow-md"
    >
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span
                    class="inline-flex items-center rounded-full bg-[#F1EFF8] px-3 py-1 text-xs font-bold text-[#494B97]"
                >
                    {{ target.period }} ·
                    {{
                        target.type === 'numeric' ? 'Target Angka' : 'Checklist'
                    }}
                </span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <OverdueBadge v-if="metrics.isOverdue" />
                    <StatusBadge :status="metrics.status" />
                </div>
            </div>

            <h3
                class="mt-3 text-lg leading-snug font-bold break-words text-[#211D37]"
            >
                <button
                    type="button"
                    class="text-left hover:text-[#494B97] focus:underline focus:outline-none"
                    @click="emit('open', target.id)"
                >
                    {{ target.title }}
                </button>
            </h3>
            <p class="mt-1 text-xs font-medium text-[#211D37]/70">
                Mulai: {{ formatIndonesianDate(target.startDate) }} ·
                <span
                    :class="
                        metrics.isOverdue
                            ? 'font-bold text-rose-700'
                            : 'font-semibold text-[#29294E]'
                    "
                >
                    Tenggat: {{ formatIndonesianDate(target.dueDate) }}
                </span>
            </p>

            <TargetProgressBar
                class="mt-4"
                :target="target"
                :metrics="metrics"
            />

            <div class="mt-3.5 space-y-2 text-xs">
                <p
                    v-if="metrics.hasActiveObstacle && metrics.lastObstacle"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-amber-950"
                >
                    <span class="font-bold">⚠ Kendala:</span>
                    {{ metrics.lastObstacle }}
                </p>
                <p class="rounded-xl bg-[#F1EFF8]/70 px-3 py-2 text-[#211D37]">
                    <span class="font-bold text-[#494B97]">
                        ➔ Langkah Berikutnya:
                    </span>
                    {{ metrics.nextStepText }}
                </p>
            </div>
        </div>

        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-[#F1EFF8] pt-3.5"
        >
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="rounded-full bg-[#F1EFF8] px-3.5 py-2 text-xs font-bold text-[#29294E] hover:bg-[#E2DDF4]"
                    @click="emit('open', target.id)"
                >
                    Detail ({{ noteCount }} catatan)
                </button>
                <button
                    type="button"
                    class="rounded-full bg-[#F1EFF8] px-3 py-2 text-xs font-semibold text-[#211D37] hover:bg-[#E2DDF4]"
                    @click="emit('edit', target)"
                >
                    Edit
                </button>
            </div>
            <button
                type="button"
                class="rounded-full bg-[#F8DF75] px-4 py-2 text-xs font-extrabold text-[#211D37] shadow-xs hover:brightness-95"
                @click="emit('addProgress', target.id)"
            >
                + Tambah Progress
            </button>
        </div>
    </article>
</template>
