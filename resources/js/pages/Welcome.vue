<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import BottomNav from '@/components/tracker/BottomNav.vue';
import ConfirmDeleteDialog from '@/components/tracker/ConfirmDeleteDialog.vue';
import FeedbackToast from '@/components/tracker/FeedbackToast.vue';
import HistoryView from '@/components/tracker/HistoryView.vue';
import HomeView from '@/components/tracker/HomeView.vue';
import PhotoLightboxDialog from '@/components/tracker/PhotoLightboxDialog.vue';
import ProgressFormDialog from '@/components/tracker/ProgressFormDialog.vue';
import QuickAddDialog from '@/components/tracker/QuickAddDialog.vue';
import TargetDetailView from '@/components/tracker/TargetDetailView.vue';
import TargetFormDialog from '@/components/tracker/TargetFormDialog.vue';
import { useProgressTracker } from '@/composables/useProgressTracker';
import { formatIndonesianDate } from '@/lib/progressTracker';
import type {
    LightboxPhoto,
    PendingDelete,
    ProgressNote,
    SaveNotePayload,
    TargetItem,
    TrackerScreen,
    TrackerSnapshot,
} from '@/types';

type Props = {
    snapshot?: TrackerSnapshot;
};

const props = defineProps<Props>();

const activeScreen = ref<TrackerScreen>('home');
const selectedTargetId = ref<string | null>(null);

const {
    targets,
    notes,
    photoUrls,
    isLoading,
    feedback,
    notesByTarget,
    metricsByTarget,
    load,
    dispose,
    findTarget,
    notesForTarget,
    orderedPhotosForNote,
    saveTarget,
    setTargetObstacle,
    saveNote,
    deleteNote,
    deleteTarget,
    showFeedback,
    dismissFeedback,
} = useProgressTracker();

const isSavingTarget = ref(false);
const isSavingProgress = ref(false);
const isDeleting = ref(false);

// Dialog component refs
const quickAddDialogRef = ref<InstanceType<typeof QuickAddDialog> | null>(null);
const targetFormDialogRef = ref<InstanceType<typeof TargetFormDialog> | null>(
    null,
);
const progressFormDialogRef = ref<InstanceType<
    typeof ProgressFormDialog
> | null>(null);
const lightboxDialogRef = ref<InstanceType<typeof PhotoLightboxDialog> | null>(
    null,
);
const confirmDeleteDialogRef = ref<InstanceType<
    typeof ConfirmDeleteDialog
> | null>(null);

const selectedTarget = computed<TargetItem | null>(() => {
    return findTarget(selectedTargetId.value);
});

const selectedTargetMetrics = computed(() => {
    return selectedTarget.value
        ? (metricsByTarget.value.get(selectedTarget.value.id) ?? null)
        : null;
});

const selectedTargetNotes = computed<ProgressNote[]>(() => {
    return selectedTarget.value ? notesForTarget(selectedTarget.value.id) : [];
});

onMounted(async () => {
    await load(props.snapshot);
});

onBeforeUnmount(() => {
    dispose();
});

// Navigation handlers
function navigateToHome(): void {
    activeScreen.value = 'home';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function navigateToHistory(): void {
    activeScreen.value = 'history';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openTargetDetail(targetId: string): void {
    selectedTargetId.value = targetId;
    activeScreen.value = 'detail';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Dialog trigger handlers
function handleBottomAdd(): void {
    quickAddDialogRef.value?.open();
}

function openCreateTargetModal(): void {
    quickAddDialogRef.value?.close();
    targetFormDialogRef.value?.openCreate();
}

function openEditTargetModal(target: TargetItem): void {
    quickAddDialogRef.value?.close();
    targetFormDialogRef.value?.openEdit(target);
}

function openCreateProgressModal(targetId?: string): void {
    quickAddDialogRef.value?.close();
    const chosenTargetId =
        targetId ??
        (activeScreen.value === 'detail' && selectedTargetId.value
            ? selectedTargetId.value
            : undefined);

    progressFormDialogRef.value?.openCreate(chosenTargetId);
}

function openEditProgressModal(note: ProgressNote): void {
    quickAddDialogRef.value?.close();
    progressFormDialogRef.value?.openEdit(note);
}

function openPhotoLightbox(note: ProgressNote, photoId: string): void {
    const notePhotos = orderedPhotosForNote(note);
    if (notePhotos.length === 0) {
        return;
    }

    const parentTarget = findTarget(note.targetId);
    const lightboxItems: LightboxPhoto[] = notePhotos.map((photo) => ({
        id: photo.id,
        url: photoUrls.value[photo.id] || photo.url || '',
        caption: photo.caption ?? '',
        fileName: photo.fileName,
        noteDate: formatIndonesianDate(note.date),
        targetTitle: parentTarget?.title ?? 'Target',
    }));

    lightboxDialogRef.value?.open(lightboxItems, photoId);
}

function promptDeleteNote(note: ProgressNote): void {
    const item: PendingDelete = {
        kind: 'note',
        id: note.id,
        title: `catatan tanggal ${formatIndonesianDate(note.date)}`,
    };
    confirmDeleteDialogRef.value?.open(item);
}

function promptDeleteTarget(target: TargetItem): void {
    const item: PendingDelete = {
        kind: 'target',
        id: target.id,
        title: `target "${target.title}" beserta seluruh riwayat dan fotonya`,
    };
    confirmDeleteDialogRef.value?.open(item);
}

// Action executor handlers
async function handleSaveTarget(
    targetInput: TargetItem,
    isEditing: boolean,
): Promise<void> {
    isSavingTarget.value = true;
    try {
        const saved = await saveTarget(targetInput, isEditing);
        if (saved) {
            targetFormDialogRef.value?.close();
            if (!isEditing) {
                selectedTargetId.value = saved.id;
                activeScreen.value = 'detail';
            }
        }
    } finally {
        isSavingTarget.value = false;
    }
}

async function handleSaveProgress(payload: SaveNotePayload): Promise<void> {
    isSavingProgress.value = true;
    try {
        const success = await saveNote(payload);
        if (success) {
            progressFormDialogRef.value?.close();
        }
    } finally {
        isSavingProgress.value = false;
    }
}

async function handleConfirmDelete(item: PendingDelete): Promise<void> {
    isDeleting.value = true;
    try {
        if (item.kind === 'note') {
            const success = await deleteNote(item.id);
            if (success) {
                confirmDeleteDialogRef.value?.close();
            }
        } else {
            const success = await deleteTarget(item.id);
            if (success) {
                confirmDeleteDialogRef.value?.close();
                if (selectedTargetId.value === item.id) {
                    selectedTargetId.value = null;
                    activeScreen.value = 'home';
                }
            }
        }
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-[#F1EFF8] pb-28 font-sans text-[#211D37]">
        <Head title="Tracker Progress Personal" />

        <FeedbackToast :message="feedback" @dismiss="dismissFeedback" />

        <!-- Layar 1: Beranda -->
        <HomeView
            v-if="activeScreen === 'home'"
            :targets="targets"
            :metrics-by-target="metricsByTarget"
            :notes-by-target="notesByTarget"
            :is-loading="isLoading"
            @create-target="openCreateTargetModal"
            @edit-target="openEditTargetModal"
            @open-target="openTargetDetail"
            @add-progress="openCreateProgressModal"
        />

        <!-- Layar 2: Detail Target -->
        <TargetDetailView
            v-else-if="
                activeScreen === 'detail' &&
                selectedTarget &&
                selectedTargetMetrics
            "
            :target="selectedTarget"
            :metrics="selectedTargetMetrics"
            :notes="selectedTargetNotes"
            :photo-urls="photoUrls"
            :get-note-photos="orderedPhotosForNote"
            @back="navigateToHome"
            @edit-target="openEditTargetModal"
            @delete-target="promptDeleteTarget"
            @toggle-obstacle="setTargetObstacle"
            @add-progress="openCreateProgressModal"
            @edit-note="openEditProgressModal"
            @delete-note="promptDeleteNote"
            @open-photo="openPhotoLightbox"
        />

        <!-- Layar 3: Riwayat Seluruh Catatan -->
        <HistoryView
            v-else-if="activeScreen === 'history'"
            :targets="targets"
            :notes="notes"
            :photo-urls="photoUrls"
            :get-note-photos="orderedPhotosForNote"
            @open-target="openTargetDetail"
            @add-progress="openCreateProgressModal"
            @edit-note="openEditProgressModal"
            @delete-note="promptDeleteNote"
            @open-photo="openPhotoLightbox"
        />

        <!-- Navigasi Bawah -->
        <BottomNav
            :active-screen="activeScreen"
            @home="navigateToHome"
            @history="navigateToHistory"
            @add="handleBottomAdd"
        />

        <!-- Dialogs -->
        <QuickAddDialog
            ref="quickAddDialogRef"
            :active-target="selectedTarget"
            @create-target="openCreateTargetModal"
            @add-progress="openCreateProgressModal"
        />

        <TargetFormDialog
            ref="targetFormDialogRef"
            :notes-by-target="notesByTarget"
            :is-saving="isSavingTarget"
            @save="handleSaveTarget"
        />

        <ProgressFormDialog
            ref="progressFormDialogRef"
            :targets="targets"
            :notes-by-target="notesByTarget"
            :photo-urls="photoUrls"
            :ordered-photos-for-note="orderedPhotosForNote"
            :is-saving="isSavingProgress"
            @save="handleSaveProgress"
            @error-message="(msg) => showFeedback('error', msg)"
        />

        <PhotoLightboxDialog ref="lightboxDialogRef" />

        <ConfirmDeleteDialog
            ref="confirmDeleteDialogRef"
            :is-deleting="isDeleting"
            @confirm="handleConfirmDelete"
        />
    </div>
</template>

<style scoped>
dialog:modal {
    overscroll-behavior: contain;
}
dialog::backdrop {
    background-color: rgba(33, 29, 55, 0.62);
    backdrop-filter: blur(3px);
    overflow: hidden;
    overscroll-behavior: contain;
}
</style>
