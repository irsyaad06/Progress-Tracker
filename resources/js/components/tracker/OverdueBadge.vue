<script setup lang="ts">
type Props = {
    label?: string;
};

withDefaults(defineProps<Props>(), {
    label: 'Lewat Tenggat',
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border border-rose-300 bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800"
    >
        ⏰ {{ label }}
    </span>
</template>
