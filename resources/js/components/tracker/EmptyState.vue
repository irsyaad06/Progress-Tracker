<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
};

defineProps<Props>();
</script>

<template>
    <div class="rounded-[20px] bg-white p-8 text-center shadow-sm">
        <p class="text-base font-bold text-[#211D37]">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs text-[#211D37]/70">
            {{ description }}
        </p>
        <div
            v-if="$slots.default"
            class="mt-4 flex flex-wrap justify-center gap-2"
        >
            <slot />
        </div>
    </div>
</template>
