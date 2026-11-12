<script setup lang="ts">
import type { PhotoRecord } from '@/types';

type Props = {
    photos: PhotoRecord[];
    photoUrls: Record<string, string>;
    compact?: boolean;
};

withDefaults(defineProps<Props>(), {
    compact: false,
});

const emit = defineEmits<{
    open: [photoId: string];
}>();
</script>

<template>
    <div
        class="grid grid-cols-2 gap-3"
        :class="compact ? 'sm:grid-cols-4' : 'sm:grid-cols-3'"
    >
        <figure
            v-for="photo in photos"
            :key="photo.id"
            class="group overflow-hidden rounded-2xl border border-[#E5E0F5] bg-[#FAF9FE]"
        >
            <button
                type="button"
                class="relative block aspect-4/3 w-full overflow-hidden bg-[#29294E]/10"
                :aria-label="`Perbesar foto ${photo.caption || photo.fileName}`"
                @click="emit('open', photo.id)"
            >
                <img
                    v-if="photoUrls[photo.id]"
                    :src="photoUrls[photo.id]"
                    :alt="
                        photo.caption || photo.fileName || 'Foto bukti progress'
                    "
                    loading="lazy"
                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                />
                <span
                    v-if="!compact"
                    class="absolute right-2 bottom-2 rounded-full bg-[#29294E]/80 px-2 py-0.5 text-[10px] font-semibold text-white"
                >
                    🔍 Perbesar
                </span>
            </button>
            <figcaption
                v-if="photo.caption"
                class="px-2.5 py-2 text-xs break-words text-[#211D37]"
            >
                {{ photo.caption }}
            </figcaption>
        </figure>
    </div>
</template>
