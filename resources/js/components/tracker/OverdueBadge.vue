<script setup lang="ts">
import { Clock } from '@lucide/vue';

type Props = {
    label?: string;
};

withDefaults(defineProps<Props>(), {
    label: 'Lewat Tenggat',
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full border border-rose-300 bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800"
    >
        <Clock class="h-3 w-3 shrink-0" aria-hidden="true" />
        <span>{{ label }}</span>
    </span>
</template>
