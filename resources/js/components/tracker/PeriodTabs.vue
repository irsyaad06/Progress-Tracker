<script setup lang="ts">
import type { PeriodFilter } from '@/types';

const PERIOD_OPTIONS: PeriodFilter[] = [
    'Semua',
    'Harian',
    'Mingguan',
    'Bulanan',
];

const period = defineModel<PeriodFilter>({ required: true });
</script>

<template>
    <div
        role="group"
        aria-label="Filter periode target"
        class="grid grid-cols-4 gap-1.5 rounded-full bg-[#29294E]/55 p-1.5"
    >
        <button
            v-for="option in PERIOD_OPTIONS"
            :key="option"
            type="button"
            class="rounded-full py-2 text-center text-xs font-bold transition sm:text-sm"
            :class="
                period === option
                    ? 'bg-[#F8DF75] text-[#211D37] shadow-sm'
                    : 'text-white/90 hover:bg-white/10'
            "
            :aria-pressed="period === option"
            @click="period = option"
        >
            {{ option }}
        </button>
    </div>
</template>
