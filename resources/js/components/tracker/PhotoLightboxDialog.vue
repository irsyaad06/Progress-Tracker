<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import {
    attachLightDismissFallback,
    closeDialogSafely,
} from '@/lib/dialogHelpers';
import type { LightboxPhoto } from '@/types';

const dialogRef = ref<HTMLDialogElement | null>(null);
const photos = ref<LightboxPhoto[]>([]);
const currentIndex = ref(0);

function open(photoList: LightboxPhoto[], startPhotoId: string): void {
    if (photoList.length === 0) {
        return;
    }

    photos.value = photoList;
    const foundIdx = photoList.findIndex((item) => item.id === startPhotoId);
    currentIndex.value = foundIdx >= 0 ? foundIdx : 0;
    dialogRef.value?.showModal();
}

function close(): void {
    closeDialogSafely(dialogRef.value);
}

function prev(): void {
    if (photos.value.length > 1) {
        currentIndex.value =
            (currentIndex.value - 1 + photos.value.length) %
            photos.value.length;
    }
}

function next(): void {
    if (photos.value.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % photos.value.length;
    }
}

onMounted(() => {
    attachLightDismissFallback(dialogRef.value);
});

defineExpose({ open, close });
</script>

<template>
    <dialog
        ref="dialogRef"
        closedby="any"
        aria-labelledby="lightbox-dialog-title"
        class="m-auto w-[95%] max-w-3xl rounded-[24px] bg-[#29294E] p-4 text-white shadow-2xl sm:p-6"
    >
        <div v-if="photos[currentIndex]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2
                        id="lightbox-dialog-title"
                        class="text-sm font-bold text-[#F8DF75] sm:text-base"
                    >
                        {{ photos[currentIndex].targetTitle }}
                    </h2>
                    <p class="text-xs text-white/80">
                        Catatan {{ photos[currentIndex].noteDate }} · Foto
                        {{ currentIndex + 1 }} dari {{ photos.length }}
                    </p>
                </div>
                <button
                    type="button"
                    autofocus
                    class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-white/25"
                    @click="close"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                    <span>Tutup</span>
                </button>
            </div>

            <div
                class="mt-3 flex max-h-[68vh] items-center justify-center overflow-hidden rounded-2xl bg-black/40"
            >
                <img
                    :src="photos[currentIndex].url"
                    :alt="
                        photos[currentIndex].caption ||
                        photos[currentIndex].fileName
                    "
                    class="max-h-[66vh] w-auto max-w-full object-contain"
                />
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-medium text-white/95 sm:text-sm">
                    {{
                        photos[currentIndex].caption ||
                        photos[currentIndex].fileName
                    }}
                </p>

                <div v-if="photos.length > 1" class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-bold hover:bg-white/25"
                        @click="prev"
                    >
                        <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                        <span>Sebelumnya</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full bg-[#F8DF75] px-3.5 py-1.5 text-xs font-bold text-[#211D37]"
                        @click="next"
                    >
                        <span>Berikutnya</span>
                        <ChevronRight class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </dialog>
</template>
