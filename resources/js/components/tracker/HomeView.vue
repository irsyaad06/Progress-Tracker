<script setup lang="ts">
import { computed, ref } from 'vue';
import { Database, Plus, Search } from '@lucide/vue';
import EmptyState from '@/components/tracker/EmptyState.vue';
import PeriodTabs from '@/components/tracker/PeriodTabs.vue';
import SummaryCard from '@/components/tracker/SummaryCard.vue';
import TargetCard from '@/components/tracker/TargetCard.vue';
import TrackerScreen from '@/components/tracker/TrackerScreen.vue';
import {
    computeTargetMetrics,
    matchesStatusFilter,
    sortTargetsForHome,
    summarizeTargets,
} from '@/lib/progressTracker';
import type {
    PeriodFilter,
    ProgressNote,
    StatusFilter,
    TargetItem,
    TargetMetrics,
} from '@/types';

type Props = {
    targets: TargetItem[];
    metricsByTarget: Map<string, TargetMetrics>;
    notesByTarget: Map<string, ProgressNote[]>;
    isLoading: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    createTarget: [];
    editTarget: [target: TargetItem];
    openTarget: [targetId: string];
    addProgress: [targetId?: string];
}>();

const periodFilter = ref<PeriodFilter>('Semua');
const statusFilter = ref<StatusFilter>('Semua');
const searchQuery = ref('');

function metricsFor(target: TargetItem): TargetMetrics {
    return (
        props.metricsByTarget.get(target.id) ?? computeTargetMetrics(target, [])
    );
}

const filteredTargets = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    const matched = props.targets.filter((target) => {
        if (
            periodFilter.value !== 'Semua' &&
            target.period !== periodFilter.value
        ) {
            return false;
        }

        if (!matchesStatusFilter(metricsFor(target), statusFilter.value)) {
            return false;
        }

        if (query && !target.title.toLowerCase().includes(query)) {
            return false;
        }

        return true;
    });

    return sortTargetsForHome(matched);
});

const summaryStats = computed(() =>
    summarizeTargets(filteredTargets.value.map((target) => metricsFor(target))),
);

function resetFilters(): void {
    periodFilter.value = 'Semua';
    statusFilter.value = 'Semua';
    searchQuery.value = '';
}
</script>

<template>
    <TrackerScreen width="wide">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-[#F1EFF8]"
                    >
                        <Database class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Terhubung ke Database MySQL</span>
                    </span>
                    <h1
                        class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl"
                    >
                        Tracker Progress Personal
                    </h1>
                    <p class="mt-0.5 text-xs text-white/85 sm:text-sm">
                        Kelola target harian, mingguan, dan bulanan beserta
                        catatan progress dan foto bukti.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-2.5 text-xs font-semibold text-white backdrop-blur hover:bg-white/25 sm:text-sm"
                        @click="emit('createTarget')"
                    >
                        <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        <span>Buat Target</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#F8DF75] px-4 py-2.5 text-xs font-bold text-[#211D37] shadow-sm hover:brightness-95 sm:text-sm"
                        @click="emit('addProgress')"
                    >
                        <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                        <span>Tambah Progress</span>
                    </button>
                </div>
            </div>

            <div class="mt-5">
                <label for="search-target-input" class="sr-only">
                    Cari judul target
                </label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-white/75"
                        aria-hidden="true"
                    >
                        <Search class="h-4 w-4" aria-hidden="true" />
                    </span>
                    <input
                        id="search-target-input"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Cari judul target harian, mingguan, atau bulanan..."
                        class="w-full rounded-full border border-white/25 bg-white/15 py-2.5 pr-16 pl-10 text-sm text-white placeholder-white/70 backdrop-blur focus:border-[#F8DF75] focus:bg-white/20 focus:outline-none"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        class="absolute inset-y-1.5 right-2 rounded-full bg-white/20 px-3 text-xs font-semibold text-white hover:bg-white/30"
                        @click="searchQuery = ''"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <PeriodTabs v-model="periodFilter" class="mt-4" />
        </template>

        <SummaryCard
            v-model:status="statusFilter"
            :stats="summaryStats"
            :period="periodFilter"
        />

        <section aria-label="Daftar target personal">
            <EmptyState
                v-if="isLoading"
                title="Memuat data target dari database MySQL..."
            />

            <EmptyState
                v-else-if="filteredTargets.length === 0"
                title="Tidak ada target yang cocok dengan filter"
                description="Ubah kata kunci pencarian/filter status, atau buat target baru untuk mulai mencatat progress."
            >
                <button
                    v-if="
                        periodFilter !== 'Semua' ||
                        statusFilter !== 'Semua' ||
                        searchQuery !== ''
                    "
                    type="button"
                    class="rounded-full bg-[#F1EFF8] px-4 py-2 text-xs font-bold text-[#29294E] hover:bg-[#E2DDF4]"
                    @click="resetFilters"
                >
                    Reset Semua Filter
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#F8DF75] px-4 py-2 text-xs font-bold text-[#211D37] shadow-xs hover:brightness-95"
                    @click="emit('createTarget')"
                >
                    <Plus class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
                    <span>Buat Target Baru</span>
                </button>
            </EmptyState>

            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <TargetCard
                    v-for="target in filteredTargets"
                    :key="target.id"
                    :target="target"
                    :metrics="metricsFor(target)"
                    :note-count="(notesByTarget.get(target.id) ?? []).length"
                    @open="(id) => emit('openTarget', id)"
                    @edit="(item) => emit('editTarget', item)"
                    @add-progress="(id) => emit('addProgress', id)"
                />
            </div>
        </section>
    </TrackerScreen>
</template>
