<style>
    /* Make all Filament infolist "View" entries easier to scan for non-technical users. */
    .fi-in-entry {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 0.6rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.85));
        padding: 0.7rem 0.8rem;
        margin-bottom: 0.45rem;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .fi-in-entry:hover {
        border-color: rgba(14, 165, 233, 0.45);
        box-shadow: 0 8px 20px rgba(2, 132, 199, 0.12);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(240, 249, 255, 0.92));
    }

    .fi-in-entry-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgb(71, 85, 105);
    }

    .fi-in-entry-content {
        font-size: 0.94rem;
        font-weight: 600;
        line-height: 1.45;
        color: rgb(15, 23, 42);
        border-left: 3px solid rgba(14, 165, 233, 0.35);
        padding-left: 0.6rem;
        border-radius: 0.2rem;
    }

    .fi-in-entry .fi-badge {
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .fi-sc-section .fi-section-header-heading {
        font-weight: 800;
        letter-spacing: 0.02em;
    }

    .fi-sc-section .fi-section-header-description {
        font-size: 0.8rem;
        color: rgb(100, 116, 139);
    }

    .dark .fi-in-entry {
        border-color: rgba(148, 163, 184, 0.28);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.38));
    }

    .dark .fi-in-entry:hover {
        border-color: rgba(56, 189, 248, 0.55);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.72), rgba(8, 47, 73, 0.52));
    }

    .dark .fi-in-entry-label {
        color: rgb(148, 163, 184);
    }

    .dark .fi-in-entry-content {
        color: rgb(226, 232, 240);
        border-left-color: rgba(125, 211, 252, 0.65);
    }

    /* Traditional clickable star rating style for Filament Radio field. */
    .fi-rating-stars {
        display: inline-flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.2rem;
    }

    .fi-rating-stars .fi-fo-radio-label {
        display: inline-flex;
        align-items: center;
        cursor: pointer;
    }

    .fi-rating-stars .fi-radio-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .fi-rating-stars .fi-fo-radio-label-text p {
        margin: 0;
        font-size: 1.55rem;
        line-height: 1;
        color: #cbd5e1;
        transition: color 0.15s ease, transform 0.15s ease;
    }

    .fi-rating-stars .fi-fo-radio-label:has(.fi-radio-input:checked) .fi-fo-radio-label-text p,
    .fi-rating-stars .fi-fo-radio-label:has(.fi-radio-input:checked) ~ .fi-fo-radio-label .fi-fo-radio-label-text p {
        color: #facc15;
    }

    .fi-rating-stars:hover .fi-fo-radio-label .fi-fo-radio-label-text p {
        color: #cbd5e1;
    }

    .fi-rating-stars .fi-fo-radio-label:hover .fi-fo-radio-label-text p,
    .fi-rating-stars .fi-fo-radio-label:hover ~ .fi-fo-radio-label .fi-fo-radio-label-text p {
        color: #facc15;
    }

    .fi-rating-stars .fi-fo-radio-label:hover .fi-fo-radio-label-text p {
        transform: scale(1.07);
    }

    .dark .fi-rating-stars .fi-fo-radio-label-text p {
        color: #475569;
    }
</style>
