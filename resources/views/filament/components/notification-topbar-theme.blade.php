<style>
    .fi-topbar-database-notifications-btn {
        border-radius: 999px !important;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.12), rgba(14, 165, 233, 0.18));
        border: 1px solid rgba(16, 185, 129, 0.28);
        color: #0f172a !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.15);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .fi-topbar-database-notifications-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(14, 165, 233, 0.2);
    }

    .fi-topbar-database-notifications-btn .fi-badge {
        background: linear-gradient(135deg, #f97316, #ef4444) !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55);
        animation: fiNotificationPulse 1.8s infinite;
    }

    @keyframes fiNotificationPulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55); }
        70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    @media (max-width: 640px) {
        .fi-topbar-database-notifications-btn {
            padding: 0.45rem !important;
            min-width: 2.25rem;
            min-height: 2.25rem;
        }
    }

    .dark .fi-topbar-database-notifications-btn {
        background: linear-gradient(135deg, rgba(250, 204, 21, 0.24), rgba(254, 240, 138, 0.28));
        border-color: rgba(253, 224, 71, 0.55);
        color: #fde68a !important;
        box-shadow: 0 8px 24px rgba(250, 204, 21, 0.25);
    }

    .dark .fi-topbar-database-notifications-btn:hover {
        box-shadow: 0 12px 26px rgba(250, 204, 21, 0.32);
    }
</style>
