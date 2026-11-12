<script setup lang="ts">
import { ClipboardList, Home, Plus } from '@lucide/vue';
import type { TrackerScreen } from '@/types';

type Props = {
    activeScreen: TrackerScreen;
};

defineProps<Props>();

const emit = defineEmits<{
    home: [];
    history: [];
    add: [];
}>();
</script>

<template>
    <nav
        aria-label="Navigasi utama"
        class="fixed right-0 bottom-0 left-0 z-40 border-t border-[#29294E]/10 bg-white/95 px-6 py-2 shadow-lg backdrop-blur"
    >
        <div class="mx-auto flex max-w-md items-center justify-around">
            <button
                type="button"
                class="flex flex-col items-center gap-1 rounded-2xl px-4 py-1.5 text-xs font-bold transition"
                :class="
                    activeScreen !== 'history'
                        ? 'text-[#494B97]'
                        : 'text-[#211D37]/60 hover:text-[#211D37]'
                "
                :aria-current="activeScreen !== 'history' ? 'page' : undefined"
                @click="emit('home')"
            >
                <Home class="h-5 w-5" aria-hidden="true" />
                <span>Beranda</span>
            </button>

            <button
                type="button"
                aria-label="Tambah target atau progress"
                class="-mt-6 flex h-14 w-14 items-center justify-center rounded-full border-4 border-[#F1EFF8] bg-[#F8DF75] text-[#211D37] shadow-md transition hover:scale-105"
                @click="emit('add')"
            >
                <Plus class="h-7 w-7 stroke-[3]" aria-hidden="true" />
            </button>

            <button
                type="button"
                class="flex flex-col items-center gap-1 rounded-2xl px-4 py-1.5 text-xs font-bold transition"
                :class="
                    activeScreen === 'history'
                        ? 'text-[#494B97]'
                        : 'text-[#211D37]/60 hover:text-[#211D37]'
                "
                :aria-current="activeScreen === 'history' ? 'page' : undefined"
                @click="emit('history')"
            >
                <ClipboardList class="h-5 w-5" aria-hidden="true" />
                <span>Riwayat</span>
            </button>
        </div>
    </nav>
</template>
