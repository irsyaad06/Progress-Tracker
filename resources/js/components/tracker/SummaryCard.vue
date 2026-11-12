<script setup lang="ts">
import ProgressRing from '@/components/tracker/ProgressRing.vue';
import type { PeriodFilter, StatusFilter, SummaryStats } from '@/types';

type Props = {
    stats: SummaryStats;
    period: PeriodFilter;
};

defineProps<Props>();

const statusFilter = defineModel<StatusFilter>('status', { required: true });

const STATUS_OPTIONS: StatusFilter[] = [
    'Semua',
    'Belum Mulai',
    'Berjalan',
    'Terkendala',
    'Selesai',
    'Lewat Tenggat',
];

const COUNT_TILES = [
    {
        key: 'berjalan',
        label: '● Berjalan',
        classes: 'bg-[#F1EFF8] text-[#29294E]',
    },
    {
        key: 'terkendala',
        label: '⚠ Terkendala',
        classes: 'bg-amber-50 text-amber-900',
    },
    {
        key: 'belumMulai',
        label: '○ Belum Mulai',
        classes: 'bg-slate-100 text-slate-800',
    },
    {
        key: 'selesai',
        label: '✓ Selesai',
        classes: 'bg-emerald-50 text-emerald-900',
    },
] as const;
</script>

<template>
    <section
        aria-label="Ringkasan capaian target"
        class="rounded-[20px] bg-white p-5 shadow-sm ring-1 ring-[#29294E]/5"
    >
        <div
            class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <ProgressRing :percent="stats.percentComplete" />
                <div>
                    <h2 class="text-base font-bold text-[#211D37] sm:text-lg">
                        Ringkasan Target ({{ period }})
                    </h2>
                    <p class="mt-0.5 text-xs text-[#211D37]/75">
                        Menampilkan
                        <strong class="font-bold text-[#29294E]">
                            {{ stats.total }}
                        </strong>
                        target sesuai filter aktif ({{ stats.selesai }}
                        selesai).
                    </p>
                    <p
                        v-if="stats.lewatTenggat > 0"
                        class="mt-2 inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800"
                    >
                        ⏰ {{ stats.lewatTenggat }} Lewat Tenggat
                    </p>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                <div
                    v-for="tile in COUNT_TILES"
                    :key="tile.key"
                    class="flex flex-col-reverse rounded-2xl px-3.5 py-2.5 text-center"
                    :class="tile.classes"
                >
                    <dt class="text-[11px] font-semibold">{{ tile.label }}</dt>
                    <dd class="text-lg font-extrabold">
                        {{ stats[tile.key] }}
                    </dd>
                </div>
            </dl>
        </div>

        <div
            role="group"
            aria-label="Filter status target"
            class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-[#F1EFF8] pt-3.5"
        >
            <span class="mr-1 text-xs font-semibold text-[#211D37]/70">
                Status:
            </span>
            <button
                v-for="option in STATUS_OPTIONS"
                :key="option"
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    statusFilter === option
                        ? 'bg-[#494B97] text-white shadow-xs'
                        : 'bg-[#F1EFF8] text-[#211D37] hover:bg-[#E4E0F3]'
                "
                :aria-pressed="statusFilter === option"
                @click="statusFilter = option"
            >
                {{ option === 'Semua' ? 'Semua Status' : option }}
            </button>
        </div>
    </section>
</template>
