<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    width?: 'narrow' | 'wide';
};

const props = withDefaults(defineProps<Props>(), {
    width: 'wide',
});

const containerClass = computed(() =>
    props.width === 'wide' ? 'max-w-6xl' : 'max-w-4xl',
);
</script>

<template>
    <div>
        <header
            class="bg-linear-to-br from-[#29294E] via-[#494B97] to-[#7766BC] px-4 pt-6 pb-12 text-white sm:px-6 lg:px-8"
        >
            <div class="mx-auto" :class="containerClass">
                <slot name="header" />
            </div>
        </header>

        <main
            class="-mt-6 rounded-t-[28px] bg-[#F1EFF8] px-4 pt-6 pb-10 sm:px-6 lg:px-8"
        >
            <div class="mx-auto space-y-6" :class="containerClass">
                <slot />
            </div>
        </main>
    </div>
</template>
