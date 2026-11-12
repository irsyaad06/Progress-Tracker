<script setup lang="ts">
import { computed } from 'vue';
import type { TargetStatus } from '@/types';

type Props = {
    status: TargetStatus;
};

const props = defineProps<Props>();

const STATUS_STYLES: Record<TargetStatus, { label: string; classes: string }> =
    {
        'Belum Mulai': {
            label: '○ Belum Mulai',
            classes: 'border-slate-300 bg-slate-100 text-slate-700',
        },
        Berjalan: {
            label: '● Berjalan',
            classes: 'border-[#C5BBE8] bg-[#E6E2F5] text-[#29294E]',
        },
        Terkendala: {
            label: '⚠ Terkendala',
            classes: 'border-amber-300 bg-amber-100 text-amber-900',
        },
        Selesai: {
            label: '✓ Selesai',
            classes: 'border-emerald-300 bg-emerald-100 text-emerald-900',
        },
    };

const style = computed(() => STATUS_STYLES[props.status]);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
        :class="style.classes"
    >
        {{ style.label }}
    </span>
</template>
