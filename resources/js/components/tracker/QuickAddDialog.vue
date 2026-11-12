<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ArrowRight, PlusCircle, Target, X } from '@lucide/vue';
import {
    attachLightDismissFallback,
    closeDialogSafely,
} from '@/lib/dialogHelpers';
import type { TargetItem } from '@/types';

type Props = {
    activeTarget?: TargetItem | null;
};

defineProps<Props>();

const emit = defineEmits<{
    createTarget: [];
    addProgress: [targetId?: string];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);

function open(): void {
    dialogRef.value?.showModal();
}

function close(): void {
    closeDialogSafely(dialogRef.value);
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
        aria-labelledby="quick-add-dialog-title"
        class="m-auto w-[92%] max-w-sm rounded-[24px] bg-white p-6 text-[#211D37] shadow-2xl"
    >
        <div class="flex items-center justify-between">
            <h2
                id="quick-add-dialog-title"
                class="text-lg font-extrabold text-[#29294E]"
            >
                Pilih Tindakan Baru
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-full bg-[#F1EFF8] px-3 py-1 text-xs font-bold text-[#29294E] hover:bg-[#E2DDF4]"
                @click="close"
            >
                <X class="h-4 w-4" aria-hidden="true" />
                <span>Tutup</span>
            </button>
        </div>
        <p class="mt-1 text-xs text-[#211D37]/75">
            Buat target baru atau catat perkembangan hasil kerja hari ini.
        </p>

        <div class="mt-4 space-y-2.5">
            <button
                type="button"
                autofocus
                class="flex w-full items-center justify-between gap-3 rounded-2xl bg-[#F8DF75] px-4 py-3.5 text-left font-bold text-[#211D37] shadow-xs hover:brightness-95"
                @click="
                    close();
                    emit('addProgress', activeTarget?.id);
                "
            >
                <div>
                    <div
                        class="flex items-center gap-1.5 text-sm font-extrabold"
                    >
                        <PlusCircle
                            class="h-4 w-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span>Tambah Catatan Progress</span>
                    </div>
                    <div class="mt-0.5 text-xs font-medium text-[#211D37]/80">
                        {{
                            activeTarget
                                ? `Otomatis untuk: ${activeTarget.title}`
                                : 'Catat capaian, kendala, dan unggah foto bukti'
                        }}
                    </div>
                </div>
                <ArrowRight class="h-5 w-5 shrink-0" aria-hidden="true" />
            </button>

            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 rounded-2xl bg-[#494B97] px-4 py-3.5 text-left font-bold text-white shadow-xs hover:bg-[#29294E]"
                @click="
                    close();
                    emit('createTarget');
                "
            >
                <div>
                    <div
                        class="flex items-center gap-1.5 text-sm font-extrabold"
                    >
                        <Target class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>Buat Target Baru</span>
                    </div>
                    <div class="mt-0.5 text-xs font-normal text-white/85">
                        Target Harian, Mingguan, atau Bulanan (Angka /
                        Checklist)
                    </div>
                </div>
                <ArrowRight class="h-5 w-5 shrink-0" aria-hidden="true" />
            </button>
        </div>
    </dialog>
</template>
