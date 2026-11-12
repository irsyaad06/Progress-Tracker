export function closeDialogSafely(dialog: HTMLDialogElement | null): void {
    if (dialog?.open) {
        dialog.returnValue = '';
        dialog.close();
    }
}

export function attachLightDismissFallback(
    dialog: HTMLDialogElement | null,
): void {
    if (
        !dialog ||
        typeof HTMLDialogElement === 'undefined' ||
        'closedBy' in HTMLDialogElement.prototype
    ) {
        return;
    }

    dialog.addEventListener('click', (event: MouseEvent) => {
        if (event.target !== dialog) {
            return;
        }

        const rect = dialog.getBoundingClientRect();
        const isInside =
            rect.top <= event.clientY &&
            event.clientY <= rect.top + rect.height &&
            rect.left <= event.clientX &&
            event.clientX <= rect.left + rect.width;

        if (!isInside) {
            closeDialogSafely(dialog);
        }
    });
}
