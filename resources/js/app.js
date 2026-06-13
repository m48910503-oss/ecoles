import './bootstrap';

import Alpine from 'alpinejs';

const themeStorageKey = 'ecoles-theme';
const sidebarStorageKey = 'ecoles-sidebar-collapsed';

const resolveTheme = () => {
    const storedTheme = window.localStorage.getItem(themeStorageKey);

    if (storedTheme === 'light' || storedTheme === 'dark') {
        return storedTheme;
    }

    return 'light';
};

const applyTheme = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
};

const resolveSidebarCollapsed = () => {
    return window.localStorage.getItem(sidebarStorageKey) === '1';
};

const initAutoSubmitFilters = () => {
    const pendingTimeouts = new Set();

    const clearPendingSubmissions = () => {
        pendingTimeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
        pendingTimeouts.clear();
    };

    document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
        let timeoutId = null;

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field.type === 'hidden') {
                return;
            }

            const submitNow = () => {
                if (timeoutId) {
                    window.clearTimeout(timeoutId);
                    pendingTimeouts.delete(timeoutId);
                }

                form.requestSubmit();
            };

            const submitDebounced = () => {
                if (timeoutId) {
                    window.clearTimeout(timeoutId);
                    pendingTimeouts.delete(timeoutId);
                }

                timeoutId = window.setTimeout(() => {
                    pendingTimeouts.delete(timeoutId);
                    timeoutId = null;
                    form.requestSubmit();
                }, 350);
                pendingTimeouts.add(timeoutId);
            };

            if (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'radio' || field.type === 'date') {
                field.addEventListener('change', submitNow);

                return;
            }

            field.addEventListener('input', submitDebounced);
        });

        form.addEventListener('submit', () => {
            if (timeoutId) {
                window.clearTimeout(timeoutId);
                pendingTimeouts.delete(timeoutId);
                timeoutId = null;
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a, button')) {
            clearPendingSubmissions();
        }
    }, true);

    window.addEventListener('beforeunload', clearPendingSubmissions);
};

applyTheme(resolveTheme());

window.Alpine = Alpine;

Alpine.data('appShell', () => ({
    sidebarOpen: false,
    sidebarCollapsed: resolveSidebarCollapsed(),
    academicMenuOpen: false,
    schoolMenuOpen: false,
    profileMenuOpen: false,
    theme: resolveTheme(),
    guideOpen: false,

    init() {
        this.syncTheme();
    },

    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        this.syncTheme();
    },

    toggleSidebar() {
        if (window.innerWidth < 1024) {
            this.sidebarOpen = !this.sidebarOpen;

            return;
        }

        this.sidebarCollapsed = !this.sidebarCollapsed;
        window.localStorage.setItem(sidebarStorageKey, this.sidebarCollapsed ? '1' : '0');
    },

    syncTheme() {
        applyTheme(this.theme);
        window.localStorage.setItem(themeStorageKey, this.theme);
    },

    closeOverlays() {
        this.sidebarOpen = false;
        this.academicMenuOpen = false;
        this.schoolMenuOpen = false;
        this.profileMenuOpen = false;
        this.guideOpen = false;
    },
}));

Alpine.start();
initAutoSubmitFilters();
