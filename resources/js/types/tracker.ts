export type TargetPeriod = 'Harian' | 'Mingguan' | 'Bulanan';

export type TargetType = 'numeric' | 'checklist';

export type TargetStatus =
    | 'Belum Mulai'
    | 'Berjalan'
    | 'Terkendala'
    | 'Selesai';

export type PeriodFilter = 'Semua' | TargetPeriod;

export type StatusFilter = 'Semua' | TargetStatus | 'Lewat Tenggat';

export type TrackerScreen = 'home' | 'detail' | 'history';

export type ObstacleAction = 'keep' | 'mark' | 'resolve';

export type ChecklistStep = {
    id: string;
    text: string;
};

export type TargetItem = {
    id: string;
    title: string;
    description: string;
    period: TargetPeriod;
    /** Tanggal lokal dalam format YYYY-MM-DD. */
    startDate: string;
    /** Tanggal lokal dalam format YYYY-MM-DD. */
    dueDate: string;
    type: TargetType;
    targetValue: number;
    unit: string;
    steps: ChecklistStep[];
    currentValue: number;
    status: TargetStatus;
    hasActiveObstacle: boolean;
    manualObstacleNote: string;
    createdAt: string;
    updatedAt: string;
};

export type ProgressNote = {
    id: string;
    targetId: string;
    /** Tanggal lokal dalam format YYYY-MM-DD. */
    date: string;
    addedValue: number;
    completedStepIds: string[];
    /** Teks langkah saat dicatat, agar riwayat tetap terbaca jika langkah dihapus dari target. */
    stepSnapshots: Record<string, string>;
    workResult: string;
    obstacle: string;
    markAsObstacle: boolean;
    resolveObstacle: boolean;
    nextStep: string;
    photoOrder: string[];
    createdAt: string;
    updatedAt: string;
};

export type PhotoRecord = {
    id: string;
    noteId: string;
    targetId: string;
    url?: string;
    blob?: Blob;
    mimeType: string;
    fileName: string;
    caption: string;
    createdAt: string;
};

export type TargetMetrics = {
    currentValue: number;
    targetValue: number;
    percentage: number;
    visualWidthPercent: number;
    completedStepIds: Set<string>;
    status: TargetStatus;
    hasActiveObstacle: boolean;
    lastObstacle: string;
    nextStepText: string;
    isOverdue: boolean;
};

export type SummaryStats = {
    total: number;
    selesai: number;
    berjalan: number;
    terkendala: number;
    belumMulai: number;
    lewatTenggat: number;
    percentComplete: number;
};

export type TrackerSnapshot = {
    targets: TargetItem[];
    notes: ProgressNote[];
    photos: PhotoRecord[];
};

export type LightboxPhoto = {
    id: string;
    url: string;
    caption: string;
    fileName: string;
    noteDate: string;
    targetTitle: string;
};

export type DraftPhoto = {
    id: string;
    file?: File;
    blob?: Blob;
    mimeType: string;
    fileName: string;
    caption: string;
    previewUrl: string;
    isExisting: boolean;
    createdAt: string;
};

export type FeedbackMessage = {
    type: 'success' | 'error';
    text: string;
};

export type PendingDelete = {
    kind: 'note' | 'target';
    id: string;
    title: string;
};

export type SaveNotePayload = {
    id?: string;
    isEditing: boolean;
    targetId: string;
    date: string;
    addedValue: number;
    completedStepIds: string[];
    workResult: string;
    obstacle: string;
    obstacleAction: ObstacleAction;
    nextStep: string;
    draftPhotos: DraftPhoto[];
};
