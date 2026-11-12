<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    percent: number;
    label?: string;
};

const props = withDefaults(defineProps<Props>(), {
    label: 'Selesai',
});

const RADIUS = 38;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const dashOffset = computed(() => {
    const clamped = Math.min(100, Math.max(0, props.percent));

    return CIRCUMFERENCE - (clamped / 100) * CIRCUMFERENCE;
});
</script>

<template>
    <div class="relative flex h-24 w-24 shrink-0 items-center justify-center">
        <svg
            class="h-24 w-24 -rotate-90"
            viewBox="0 0 96 96"
            aria-hidden="true"
        >
            <circle
                cx="48"
                cy="48"
                :r="RADIUS"
                stroke="#F1EFF8"
                stroke-width="10"
                fill="transparent"
            />
            <circle
                cx="48"
                cy="48"
                :r="RADIUS"
                stroke="#494B97"
                stroke-width="10"
                stroke-linecap="round"
                fill="transparent"
                :stroke-dasharray="CIRCUMFERENCE"
                :stroke-dashoffset="dashOffset"
                class="transition-all duration-500"
            />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xl font-extrabold text-[#211D37]">
                {{ percent }}%
            </span>
            <span class="text-[10px] font-semibold text-[#494B97]">
                {{ label }}
            </span>
        </div>
    </div>
</template>
