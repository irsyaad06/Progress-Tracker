<script setup lang="ts">
import type { TargetItem, TargetMetrics } from '@/types';

type Props = {
    target: TargetItem;
    metrics: TargetMetrics;
    size?: 'sm' | 'lg';
};

withDefaults(defineProps<Props>(), {
    size: 'sm',
});
</script>

<template>
    <div
        class="rounded-2xl bg-[#F1EFF8]"
        :class="size === 'lg' ? 'p-4' : 'p-3.5'"
    >
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <span
                :class="
                    size === 'lg'
                        ? 'text-xs font-bold tracking-wider text-[#494B97] uppercase'
                        : 'text-xs font-semibold text-[#211D37]/80'
                "
            >
                {{ size === 'lg' ? 'Total Capaian Saat Ini' : 'Capaian' }}
            </span>
            <span
                class="font-extrabold text-[#29294E]"
                :class="size === 'lg' ? 'text-lg' : 'text-xs'"
            >
                {{ metrics.currentValue }} / {{ metrics.targetValue }}
                {{ target.unit }}
                <span class="text-[#494B97]">({{ metrics.percentage }}%)</span>
            </span>
        </div>
        <div
            class="mt-2 w-full overflow-hidden rounded-full bg-white"
            :class="size === 'lg' ? 'h-3' : 'h-2.5'"
            role="progressbar"
            :aria-valuenow="metrics.visualWidthPercent"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-label="`Capaian ${target.title}`"
        >
            <div
                class="h-full rounded-full transition-all duration-300"
                :class="
                    metrics.status === 'Selesai'
                        ? 'bg-emerald-600'
                        : 'bg-[#494B97]'
                "
                :style="{ width: `${metrics.visualWidthPercent}%` }"
            />
        </div>
    </div>
</template>
