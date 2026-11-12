<script setup lang="ts">
import { AlertCircle, CheckCircle2 } from '@lucide/vue';
import type { FeedbackMessage } from '@/types';

type Props = {
    message: FeedbackMessage | null;
};

defineProps<Props>();

const emit = defineEmits<{
    dismiss: [];
}>();
</script>

<template>
    <div role="status" aria-live="polite">
        <div
            v-if="message"
            class="fixed top-4 right-4 left-4 z-50 mx-auto max-w-xl rounded-2xl border px-4 py-3 text-white shadow-lg"
            :class="
                message.type === 'success'
                    ? 'border-emerald-300 bg-emerald-950'
                    : 'border-rose-300 bg-rose-950'
            "
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <CheckCircle2
                        v-if="message.type === 'success'"
                        class="h-5 w-5 shrink-0 text-emerald-400"
                        aria-hidden="true"
                    />
                    <AlertCircle
                        v-else
                        class="h-5 w-5 shrink-0 text-rose-400"
                        aria-hidden="true"
                    />
                    <p class="text-sm font-medium">
                        <span class="mr-1.5 font-bold">
                            {{
                                message.type === 'success'
                                    ? 'Berhasil:'
                                    : 'Gagal:'
                            }}
                        </span>
                        {{ message.text }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold hover:bg-white/25"
                    @click="emit('dismiss')"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
