<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle, CheckCircle2, Circle, CircleDot } from '@lucide/vue';
import type { TargetStatus } from '@/types';

type Props = {
    status: TargetStatus;
};

const props = defineProps<Props>();

const STATUS_CONFIG = computed(() => {
    switch (props.status) {
        case 'Belum Mulai':
            return {
                label: 'Belum Mulai',
                classes: 'border-slate-300 bg-slate-100 text-slate-700',
                icon: Circle,
            };
        case 'Berjalan':
            return {
                label: 'Berjalan',
                classes: 'border-[#C5BBE8] bg-[#E6E2F5] text-[#29294E]',
                icon: CircleDot,
            };
        case 'Terkendala':
            return {
                label: 'Terkendala',
                classes: 'border-amber-300 bg-amber-100 text-amber-900',
                icon: AlertTriangle,
            };
        case 'Selesai':
            return {
                label: 'Selesai',
                classes: 'border-emerald-300 bg-emerald-100 text-emerald-900',
                icon: CheckCircle2,
            };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
        :class="STATUS_CONFIG.classes"
    >
        <component
            :is="STATUS_CONFIG.icon"
            class="h-3 w-3 stroke-[2.5]"
            aria-hidden="true"
        />
        <span>{{ STATUS_CONFIG.label }}</span>
    </span>
</template>
