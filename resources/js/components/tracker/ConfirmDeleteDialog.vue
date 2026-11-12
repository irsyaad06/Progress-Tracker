<script setup lang="ts">
import { ref } from 'vue';
import { closeDialogSafely } from '@/lib/dialogHelpers';
import type { PendingDelete } from '@/types';

type Props = {
    isDeleting: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    confirm: [item: PendingDelete];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);
const pendingItem = ref<PendingDelete | null>(null);

function open(item: PendingDelete): void {
    pendingItem.value = item;
    dialogRef.value?.showModal();
}

function close(): void {
    closeDialogSafely(dialogRef.value);
    pendingItem.value = null;
}

defineExpose({ open, close });
</script>

<template>
    <dialog
        ref="dialogRef"
        aria-labelledby="confirm-delete-title"
        class="m-auto w-[92%] max-w-md rounded-[24px] bg-white p-6 text-[#211D37] shadow-2xl"
    >
        <h2
            id="confirm-delete-title"
            class="text-lg font-extrabold text-rose-800"
        >
            Konfirmasi Penghapusan
        </h2>
        <p class="mt-2 text-xs leading-relaxed text-[#211D37]/85 sm:text-sm">
            Apakah Anda yakin ingin menghapus
            <strong>{{ pendingItem?.title }}</strong
            >? Tindakan ini tidak dapat dibatalkan dan capaian target akan
            dihitung ulang secara otomatis.
        </p>
        <div class="mt-5 flex items-center justify-end gap-2.5">
            <button
                type="button"
                autofocus
                class="rounded-full bg-[#F1EFF8] px-4 py-2.5 text-xs font-bold text-[#29294E] hover:bg-[#E2DDF4]"
                @click="close"
            >
                Batal
            </button>
            <button
                type="button"
                :disabled="isDeleting || !pendingItem"
                class="rounded-full bg-rose-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50"
                @click="pendingItem && emit('confirm', pendingItem)"
            >
                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Sekarang' }}
            </button>
        </div>
    </dialog>
</template>
