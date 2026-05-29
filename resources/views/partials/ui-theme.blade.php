{{--
|--------------------------------------------------------------------------
| CleanFlow UI Theme - BLUE SYSTEM PALETTE
|--------------------------------------------------------------------------
|
| Color Palette:
| - Blue 900: #1E3A8A
| - Blue 800: #1E40AF
| - Blue 700: #1D4ED8
| - Blue 600: #2563EB
| - Blue 500: #3B82F6
| - Blue 400: #60A5FA
| - Blue 300: #93C5FD
| - Blue 200: #BFDBFE
| - Blue 100: #DBEAFE
| - Blue 50:  #EFF6FF
|
--}}

<style>
/* ==========================================
   BLUE SYSTEM PALETTE
   ========================================== */

:root {
    --font-display: 'Inter', system-ui, sans-serif;

    /* Primary Colors */
    --primary-color: #2563EB;
    --accent-color: #2563EB;
    --success-color: #2563EB;
    --warning-color: #3B82F6;
    --danger-color: #1D4ED8;
    --highlight-color: #2563EB;
    --secondary-color: #475569;
    --brand-navy: #1E3A8A;
    --brand-progress: #2563EB;

    /* Background and text */
    --bg-color: #f8fafc;
    --light-bg: #ffffff;
    --dark-text: #0f172a;
    --border-color: #e2e8f0;

    /* RGB Values for Opacity Variants */
    --primary-rgb: 37, 99, 235;
    --accent-rgb: 37, 99, 235;
    --success-rgb: 37, 99, 235;
    --warning-rgb: 59, 130, 246;
    --danger-rgb: 29, 78, 216;
    --highlight-rgb: 37, 99, 235;
    --secondary-rgb: 71, 85, 105;

    /* Shadows */
    --shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    --shadow-lg: 0 18px 42px rgba(15, 23, 42, 0.14);
}

/* ==========================================
   COLOR UTILITY CLASSES
   ========================================== */

/* Text Colors */
.text-primary { color: var(--primary-color) !important; }
.text-secondary { color: var(--secondary-color) !important; }
.text-accent { color: var(--accent-color) !important; }
.text-highlight { color: var(--highlight-color) !important; }
.text-success { color: var(--success-color) !important; }
.text-warning { color: var(--warning-color) !important; }
.text-danger { color: var(--danger-color) !important; }

/* Background Colors */
.bg-primary { background-color: var(--primary-color) !important; }
.bg-secondary { background-color: var(--secondary-color) !important; }
.bg-accent { background-color: var(--accent-color) !important; }
.bg-highlight { background-color: var(--highlight-color) !important; }
.bg-success { background-color: var(--success-color) !important; }
.bg-warning { background-color: var(--warning-color) !important; }
.bg-danger { background-color: var(--danger-color) !important; }

/* Border Colors */
.border-primary { border-color: var(--primary-color) !important; }
.border-secondary { border-color: var(--secondary-color) !important; }
.border-accent { border-color: var(--accent-color) !important; }
.border-highlight { border-color: var(--highlight-color) !important; }
.border-success { border-color: var(--success-color) !important; }
.border-warning { border-color: var(--warning-color) !important; }
.border-danger { border-color: var(--danger-color) !important; }

/* ==========================================
   COMPONENT STYLES
   ========================================== */

body {
    font-family: var(--font-sans);
}

.cleanflow-page-shell {
    background: var(--bg-color);
}

.cleanflow-hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    border: 1px solid rgba(37, 99, 235, 0.15);
    border-radius: 1.75rem;
    background: #1E3A8A;
    box-shadow: 0 24px 48px rgba(37, 99, 235, 0.2);
}

.cleanflow-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
    z-index: 0;
}

.cleanflow-hero-content {
    position: relative;
    z-index: 1;
}

.cleanflow-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.45rem 0.85rem;
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.cleanflow-kicker i {
    font-size: 0.78rem;
}

.cleanflow-panel {
    border: 1px solid var(--border-color);
    background: #ffffff;
    border-radius: 1.25rem;
    box-shadow: var(--shadow);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.cleanflow-panel:hover {
    border-color: rgba(var(--accent-rgb), 0.35);
    box-shadow: var(--shadow-lg);
}

/* Alert Components */
.cleanflow-alert {
    border-radius: 1.25rem;
    padding: 1rem 1.25rem;
    box-shadow: var(--shadow);
}

.cleanflow-alert--success {
    border: 1px solid var(--success-color);
    background: rgba(37, 99, 235, 0.1);
    color: var(--success-color);
}

.cleanflow-alert--error {
    border: 1px solid var(--danger-color);
    background: rgba(225, 29, 72, 0.08);
    color: var(--danger-color);
}

.cleanflow-alert--warning {
    border: 1px solid var(--warning-color);
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.cleanflow-alert--info {
    border: 1px solid var(--primary-color);
    background: rgba(37, 99, 235, 0.08);
    color: var(--primary-color);
}

/* Button Components */
.btn-primary {
    background-color: var(--primary-color);
    color: #ffffff;
    border: none;
}

.btn-primary:hover {
    background-color: #1D4ED8;
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: #ffffff;
    border: none;
}

.btn-accent {
    background-color: var(--accent-color);
    color: #ffffff;
    border: none;
}

.btn-highlight {
    background-color: var(--highlight-color);
    color: #ffffff;
    border: none;
}

/* Focus States */
:where(input:not([type='checkbox']):not([type='radio']), select, textarea):focus {
    outline: none;
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.18) !important;
}

:where(button, a, input, select, textarea):focus-visible {
    outline: 2px solid rgba(var(--accent-rgb), 0.45);
    outline-offset: 2px;
}

input[type='checkbox'],
input[type='radio'] {
    accent-color: var(--accent-color);
}

@media (max-width: 640px) {
    .cleanflow-hero {
        border-radius: 1.5rem;
    }

    .cleanflow-kicker {
        font-size: 0.68rem;
        letter-spacing: 0.1em;
    }
}
</style>
