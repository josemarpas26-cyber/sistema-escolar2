import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();

const THEME_KEY = 'siga-theme';

function getThemeIcon() {
    return document.getElementById('themeIcon') || document.getElementById('authThemeIcon');
}

function syncThemeIcon() {
    const icon = getThemeIcon();

    if (!icon) {
        return;
    }

    icon.className = document.documentElement.classList.contains('dark')
        ? 'fas fa-sun'
        : 'fas fa-moon';
}

function setTheme(theme) {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    syncThemeIcon();
}

function applyStoredTheme() {
    const storedTheme = localStorage.getItem(THEME_KEY);

    if (storedTheme === 'dark' || storedTheme === 'light') {
        document.documentElement.classList.toggle('dark', storedTheme === 'dark');
    }

    syncThemeIcon();
}

function getSidebarElements() {
    return {
        sidebar: document.getElementById('sidebar'),
        overlay: document.getElementById('sidebarOverlay'),
    };
}

function setSidebarState(open) {
    const { sidebar, overlay } = getSidebarElements();

    if (!sidebar || !overlay) {
        return;
    }

    sidebar.classList.toggle('open', open);
    overlay.classList.toggle('visible', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

function bindThemeToggle() {
    const button = document.getElementById('authThemeToggle');

    if (!button) {
        syncThemeIcon();
        return;
    }

    button.addEventListener('click', () => {
        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        setTheme(nextTheme);
    });

    syncThemeIcon();
}

function bindSidebar() {
    const { sidebar, overlay } = getSidebarElements();

    if (!sidebar || !overlay) {
        return;
    }

    overlay.addEventListener('click', () => setSidebarState(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            setSidebarState(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            setSidebarState(false);
        }
    });
}

function initAutoDismissAlerts() {
    document.querySelectorAll('.auto-dismiss').forEach((alert) => {
        const timeout = Number(alert.dataset.dismissAfter || 5000);

        window.setTimeout(() => {
            alert.style.transition = 'opacity .4s, transform .4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';

            window.setTimeout(() => {
                alert.remove();
            }, 400);
        }, timeout);
    });
}

function getAuthenticatedUserId() {
    return document.head.querySelector('meta[name="auth-user-id"]')?.content || '';
}

function supportsBrowserNotifications() {
    return typeof window !== 'undefined' && 'Notification' in window;
}

function queueBrowserNotificationPermissionRequest() {
    if (!supportsBrowserNotifications() || Notification.permission !== 'default') {
        return;
    }

    const requestPermission = () => {
        Notification.requestPermission().catch(() => {});
        window.removeEventListener('click', requestPermission);
        window.removeEventListener('keydown', requestPermission);
    };

    window.addEventListener('click', requestPermission, { once: true });
    window.addEventListener('keydown', requestPermission, { once: true });
}

function showBrowserNotification(payload) {
    if (!supportsBrowserNotifications() || Notification.permission !== 'granted') {
        return;
    }

    const notification = new Notification(payload.titulo || 'SIGA', {
        body: payload.descricao || 'Nova notificacao recebida.',
        icon: '/images/logo1.png',
        badge: '/images/logo1.png',
        tag: [
            'siga-pauta',
            payload.turma_id || 'all',
            payload.disciplina_id || 'all',
            payload.trimestre || 'all',
            payload.campo || 'all',
            payload.titulo || 'notificacao',
        ].join(':'),
        data: {
            link: payload.link || null,
        },
    });

    notification.onclick = () => {
        window.focus();

        if (payload.link) {
            window.location.assign(payload.link);
        }

        notification.close();
    };

    window.setTimeout(() => notification.close(), 10000);
}

function initRealtimeNotifications() {
    if (window.__sigaRealtimeNotificationsInitialized) {
        return;
    }

    const userId = getAuthenticatedUserId();

    if (!userId || !window.Echo || typeof window.Echo.private !== 'function') {
        return;
    }

    window.__sigaRealtimeNotificationsInitialized = true;

    queueBrowserNotificationPermissionRequest();

    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            const payload = {
                titulo: notification?.titulo || 'Nova notificacao',
                descricao: notification?.descricao || '',
                link: notification?.link || null,
                ...notification,
            };

            window.dispatchEvent(new CustomEvent('siga:notification-received', {
                detail: payload,
            }));

            showBrowserNotification(payload);
        });
}

window.openSidebar = function openSidebar() {
    setSidebarState(true);
};

window.closeSidebar = function closeSidebar() {
    setSidebarState(false);
};

window.toggleSidebar = function toggleSidebar() {
    const { sidebar } = getSidebarElements();

    if (!sidebar) {
        return;
    }

    setSidebarState(!sidebar.classList.contains('open'));
};

window.toggleDropdown = function toggleDropdown(id) {
    const target = document.getElementById(id);

    if (target) {
        target.classList.toggle('open');
    }
};

window.confirmDelete = function confirmDelete(formId, message = 'Tem certeza que deseja eliminar este item?') {
    const form = document.getElementById(formId);

    if (form && window.confirm(message)) {
        form.submit();
    }
};

window.confirmAction = function confirmAction(message, callback) {
    if (window.confirm(message)) {
        callback();
    }
};

window.formatNota = function formatNota(input) {
    const rawValue = String(input.value || '').replace(',', '.');
    const value = Number.parseFloat(rawValue);

    if (Number.isNaN(value) || value < 0) {
        input.value = '';
        return;
    }

    input.value = value > 20 ? '20.00' : value.toFixed(2);
};

window.previewImage = function previewImage(input) {
    const preview = document.getElementById('preview-image');

    if (!preview || !input.files || !input.files[0]) {
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        preview.src = event.target?.result;
    };
    reader.readAsDataURL(input.files[0]);
};

window.printContent = function printContent() {
    window.print();
};

document.addEventListener('DOMContentLoaded', () => {
    applyStoredTheme();
    bindThemeToggle();
    bindSidebar();
    initAutoDismissAlerts();
    initRealtimeNotifications();
});
