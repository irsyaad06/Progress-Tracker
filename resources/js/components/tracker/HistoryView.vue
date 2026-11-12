<script setup lang="ts">
import { computed, ref } from 'vue';
import EmptyState from '@/components/tracker/EmptyState.vue';
import NoteCard from '@/components/tracker/NoteCard.vue';
import TrackerScreen from '@/components/tracker/TrackerScreen.vue';
import { sortNotesNewestFirst } from '@/lib/progressTracker';
import type { PhotoRecord, ProgressNote, TargetItem } from '@/types';

type Props = {
    targets: TargetItem[];
    notes: ProgressNote[];
    photoUrls: Record<string, string>;
    getNotePhotos: (note: ProgressNote) => PhotoRecord[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    openTarget: [targetId: string];
    addProgress: [];
    editNote: [note: ProgressNote];
    deleteNote: [note: ProgressNote];
    openPhoto: [note: ProgressNote, photoId: string];
}>();

const searchQuery = ref('');

const notesWithTargets = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    const targetById = new Map(
        props.targets.map((target) => [target.id, target]),
    );

    return sortNotesNewestFirst(props.notes)
        .map((note) => ({
            note,
            target: targetById.get(note.targetId) ?? null,
        }))
        .filter((item): item is { note: ProgressNote; target: TargetItem } => {
            if (!item.target) {
                return false;
            }

            if (!query) {
                return true;
            }

            return (
                item.target.title.toLowerCase().includes(query) ||
                item.note.workResult.toLowerCase().includes(query) ||
                item.note.obstacle.toLowerCase().includes(query) ||
                item.note.nextStep.toLowerCase().includes(query)
            );
        });
});
</script>

<template>
    <TrackerScreen width="narrow">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span
                        class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold"
                    >
                        🗂 Jurnal Lintas Target
                    </span>
                    <h1 class="mt-2 text-2xl font-bold sm:text-3xl">
                        Riwayat Seluruh Catatan Progress
                    </h1>
                    <p class="mt-0.5 text-xs text-white/80 sm:text-sm">
                        Menampilkan seluruh catatan hasil pekerjaan, kendala,
                        dan foto bukti dari yang terbaru.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-full bg-[#F8DF75] px-4 py-2.5 text-xs font-extrabold text-[#211D37] shadow-xs hover:brightness-95 sm:text-sm"
                    @click="emit('addProgress')"
                >
                    + Tambah Progress
                </button>
            </div>

            <div class="mt-4">
                <label for="history-search-input" class="sr-only">
                    Cari catatan riwayat
                </label>
                <input
                    id="history-search-input"
                    v-model="searchQuery"
                    type="search"
                    placeholder="Cari berdasarkan judul target, hasil, atau kendala..."
                    class="w-full rounded-full border border-white/25 bg-white/15 px-4 py-2.5 text-sm text-white placeholder-white/70 focus:border-[#F8DF75] focus:outline-none"
                />
            </div>
        </template>

        <EmptyState
            v-if="notesWithTargets.length === 0"
            title="Belum ada riwayat catatan yang cocok"
            description="Mulai tambahkan catatan progress pada target Anda."
        />

        <div v-else class="space-y-4">
            <NoteCard
                v-for="{ note, target } in notesWithTargets"
                :key="note.id"
                :note="note"
                :target="target"
                :photos="getNotePhotos(note)"
                :photo-urls="photoUrls"
                show-target-link
                @open-target="(id) => emit('openTarget', id)"
                @edit="(item) => emit('editNote', item)"
                @delete="(item) => emit('deleteNote', item)"
                @open-photo="
                    (noteItem, photoId) => emit('openPhoto', noteItem, photoId)
                "
            />
        </div>
    </TrackerScreen>
</template>
