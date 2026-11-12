import type {
    ProgressNote,
    StatusFilter,
    SummaryStats,
    TargetItem,
    TargetMetrics,
    TargetPeriod,
    TargetStatus,
} from '@/types';

const MONTH_LABELS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
];

export function generateId(prefix: string): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return `${prefix}_${crypto.randomUUID()}`;
    }

    return `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
}

export function getLocalTodayYMD(dateObj: Date = new Date()): string {
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function addDaysLocalYMD(baseYMD: string, daysToAdd: number): string {
    const [year, month, day] = baseYMD.split('-').map(Number);
    const date = new Date(year || 2026, (month || 1) - 1, day || 1);
    date.setDate(date.getDate() + daysToAdd);

    return getLocalTodayYMD(date);
}

export function formatIndonesianDate(ymd: string): string {
    if (!ymd) {
        return '-';
    }

    const [year, month, day] = ymd.split('-').map(Number);

    if (!year || !month || !day) {
        return ymd;
    }

    return `${day} ${MONTH_LABELS[month - 1] ?? ''} ${year}`;
}

export function defaultDueDateForPeriod(
    startDate: string,
    period: TargetPeriod,
): string {
    if (period === 'Harian') {
        return startDate;
    }

    return addDaysLocalYMD(startDate, period === 'Mingguan' ? 7 : 30);
}

export function isTargetOverdue(
    target: Pick<TargetItem, 'status' | 'dueDate'>,
    todayYMD: string = getLocalTodayYMD(),
): boolean {
    if (target.status === 'Selesai' || !target.dueDate) {
        return false;
    }

    return todayYMD > target.dueDate;
}

export function sortNotesNewestFirst(notes: ProgressNote[]): ProgressNote[] {
    return [...notes].sort((a, b) => {
        if (a.date !== b.date) {
            return b.date.localeCompare(a.date);
        }

        return b.createdAt.localeCompare(a.createdAt);
    });
}

/**
 * Target aktif diurutkan menurut tenggat terdekat, target selesai diletakkan paling bawah.
 */
export function sortTargetsForHome(targets: TargetItem[]): TargetItem[] {
    return [...targets].sort((a, b) => {
        const aDone = a.status === 'Selesai' ? 1 : 0;
        const bDone = b.status === 'Selesai' ? 1 : 0;

        if (aDone !== bDone) {
            return aDone - bDone;
        }

        if (a.dueDate !== b.dueDate) {
            return a.dueDate.localeCompare(b.dueDate);
        }

        return b.updatedAt.localeCompare(a.updatedAt);
    });
}

function roundTwoDecimals(value: number): number {
    return Math.round(value * 100) / 100;
}

function resolveNextStepText(
    target: TargetItem,
    sortedNotes: ProgressNote[],
    completedStepIds: Set<string>,
    currentValue: number,
    targetValue: number,
    isCompleted: boolean,
): string {
    const latestNextStep = sortedNotes
        .find((note) => note.nextStep?.trim())
        ?.nextStep.trim();

    if (latestNextStep) {
        return latestNextStep;
    }

    if (isCompleted) {
        return 'Target telah tercapai sepenuhnya.';
    }

    if (target.type === 'checklist') {
        const nextUnfinished = target.steps.find(
            (step) => !completedStepIds.has(step.id),
        );

        return nextUnfinished
            ? `Selesaikan langkah: ${nextUnfinished.text}`
            : 'Catat perkembangan langkah checklist.';
    }

    const remaining = Math.max(0, roundTwoDecimals(targetValue - currentValue));

    return `Lanjutkan ${remaining} ${target.unit} lagi hingga mencapai target.`;
}

/**
 * Menghitung capaian dan status target dari seluruh catatannya.
 * Target angka menjumlahkan tambahan; checklist menghitung langkah selesai yang unik.
 */
export function computeTargetMetrics(
    target: TargetItem,
    targetNotes: ProgressNote[],
    todayYMD: string = getLocalTodayYMD(),
): TargetMetrics {
    const sortedNotes = sortNotesNewestFirst(targetNotes);
    const completedStepIds = new Set<string>();
    let currentValue: number;
    let targetValue: number;

    if (target.type === 'checklist') {
        const validStepIds = new Set(target.steps.map((step) => step.id));

        for (const note of sortedNotes) {
            for (const stepId of note.completedStepIds ?? []) {
                if (validStepIds.has(stepId)) {
                    completedStepIds.add(stepId);
                }
            }
        }

        currentValue = completedStepIds.size;
        targetValue = target.steps.length;
    } else {
        currentValue = roundTwoDecimals(
            sortedNotes.reduce(
                (sum, note) => sum + (Number(note.addedValue) || 0),
                0,
            ),
        );
        targetValue = Math.max(0.01, Number(target.targetValue) || 1);
    }

    const percentage =
        targetValue > 0 ? Math.round((currentValue / targetValue) * 100) : 0;
    const isCompleted = targetValue > 0 && currentValue >= targetValue;
    const hasActiveObstacle = !isCompleted && Boolean(target.hasActiveObstacle);

    let status: TargetStatus = 'Belum Mulai';

    if (isCompleted) {
        status = 'Selesai';
    } else if (hasActiveObstacle) {
        status = 'Terkendala';
    } else if (sortedNotes.length > 0 || currentValue > 0) {
        status = 'Berjalan';
    }

    const latestObstacle =
        sortedNotes.find((note) => note.obstacle?.trim())?.obstacle.trim() ??
        '';
    const lastObstacle = hasActiveObstacle
        ? latestObstacle ||
          target.manualObstacleNote?.trim() ||
          'Terdapat kendala yang perlu ditindaklanjuti.'
        : latestObstacle;

    return {
        currentValue,
        targetValue,
        percentage,
        visualWidthPercent: Math.min(100, Math.max(0, percentage)),
        completedStepIds,
        status,
        hasActiveObstacle,
        lastObstacle,
        nextStepText: resolveNextStepText(
            target,
            sortedNotes,
            completedStepIds,
            currentValue,
            targetValue,
            isCompleted,
        ),
        isOverdue: isTargetOverdue(
            { status, dueDate: target.dueDate },
            todayYMD,
        ),
    };
}

/**
 * Menyalin hasil perhitungan ke target agar nilai tersimpan selalu konsisten dengan catatan.
 */
export function applyMetricsToTarget(
    target: TargetItem,
    targetNotes: ProgressNote[],
): TargetItem {
    const metrics = computeTargetMetrics(target, targetNotes);

    return {
        ...target,
        currentValue: metrics.currentValue,
        targetValue: metrics.targetValue,
        status: metrics.status,
        hasActiveObstacle: metrics.hasActiveObstacle,
    };
}

export function matchesStatusFilter(
    metrics: Pick<TargetMetrics, 'status' | 'isOverdue'>,
    filter: StatusFilter,
): boolean {
    if (filter === 'Semua') {
        return true;
    }

    if (filter === 'Lewat Tenggat') {
        return metrics.isOverdue;
    }

    return metrics.status === filter;
}

export function summarizeTargets(metricsList: TargetMetrics[]): SummaryStats {
    const stats: SummaryStats = {
        total: metricsList.length,
        selesai: 0,
        berjalan: 0,
        terkendala: 0,
        belumMulai: 0,
        lewatTenggat: 0,
        percentComplete: 0,
    };

    for (const metrics of metricsList) {
        if (metrics.status === 'Selesai') {
            stats.selesai++;
        } else if (metrics.status === 'Berjalan') {
            stats.berjalan++;
        } else if (metrics.status === 'Terkendala') {
            stats.terkendala++;
        } else {
            stats.belumMulai++;
        }

        if (metrics.isOverdue) {
            stats.lewatTenggat++;
        }
    }

    stats.percentComplete =
        stats.total > 0 ? Math.round((stats.selesai / stats.total) * 100) : 0;

    return stats;
}

export function groupNotesByTarget(
    notes: ProgressNote[],
): Map<string, ProgressNote[]> {
    const grouped = new Map<string, ProgressNote[]>();

    for (const note of notes) {
        const list = grouped.get(note.targetId) ?? [];
        list.push(note);
        grouped.set(note.targetId, list);
    }

    for (const [targetId, list] of grouped) {
        grouped.set(targetId, sortNotesNewestFirst(list));
    }

    return grouped;
}
