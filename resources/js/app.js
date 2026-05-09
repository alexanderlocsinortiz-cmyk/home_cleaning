import './bootstrap';

const clampPercent = (value) => {
    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return 0;
    }

    return Math.max(0, Math.min(100, numericValue));
};

const applyDynamicFillMetrics = () => {
    document.querySelectorAll('[data-fill-width]').forEach((element) => {
        element.style.width = `${clampPercent(element.dataset.fillWidth)}%`;
    });

    document.querySelectorAll('[data-fill-height]').forEach((element) => {
        element.style.height = `${clampPercent(element.dataset.fillHeight)}%`;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyDynamicFillMetrics);
} else {
    applyDynamicFillMetrics();
}
