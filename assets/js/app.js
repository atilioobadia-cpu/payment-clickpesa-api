document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const themeKey = 'paymentSandboxTheme';
    const sidebarKey = 'paymentSandboxSidebar';
    const desktopSidebarBreakpoint = 1024;
    const moonIcon = '<span class="icon" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 12.8A6.5 6.5 0 017.2 5.5 6.7 6.7 0 1014.5 12.8z"/></svg></span>';
    const sunIcon = '<span class="icon" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="3.2"/><path d="M10 2.8v2"/><path d="M10 15.2v2"/><path d="M17.2 10h-2"/><path d="M4.8 10h-2"/><path d="M15.1 4.9l-1.4 1.4"/><path d="M6.3 13.7l-1.4 1.4"/><path d="M15.1 15.1l-1.4-1.4"/><path d="M6.3 6.3L4.9 4.9"/></svg></span>';
    let toastStack = document.querySelector('[data-toast-stack]');

    const showToast = (message, tone = 'info') => {
        if (!toastStack) {
            toastStack = document.createElement('div');
            toastStack.className = 'toast-stack no-print';
            toastStack.setAttribute('data-toast-stack', '');
            document.body.appendChild(toastStack);
        }

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('data-toast', '');
        toast.setAttribute('data-tone', tone);

        const paragraph = document.createElement('p');
        paragraph.textContent = message;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'toast-close';
        closeButton.setAttribute('data-toast-close', '');
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', () => toast.remove());

        toast.append(paragraph, closeButton);
        toastStack.appendChild(toast);
        setTimeout(() => toast.remove(), 4500);
    };

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';
        body.classList.toggle('theme-dark', isDark);
        document.documentElement.classList.toggle('theme-dark-root', isDark);
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            button.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            button.innerHTML = isDark ? sunIcon : moonIcon;
        });
    };

    try {
        applyTheme(localStorage.getItem(themeKey) === 'dark' ? 'dark' : 'light');
    } catch (error) {
        applyTheme('light');
    }

    try {
        if (window.innerWidth > desktopSidebarBreakpoint && localStorage.getItem(sidebarKey) === 'collapsed') {
            body.classList.add('sidebar-condensed');
        }
    } catch (error) {
        console.warn(error);
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = body.classList.contains('theme-dark') ? 'light' : 'dark';
            applyTheme(nextTheme);
            try {
                localStorage.setItem(themeKey, nextTheme);
            } catch (error) {
                console.warn(error);
            }
        });
    });

    document.querySelectorAll('[data-mobile-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            if (window.innerWidth <= desktopSidebarBreakpoint) {
                body.classList.toggle('sidebar-open');
                return;
            }

            body.classList.toggle('sidebar-condensed');
            try {
                localStorage.setItem(sidebarKey, body.classList.contains('sidebar-condensed') ? 'collapsed' : 'expanded');
            } catch (error) {
                console.warn(error);
            }
        });
    });

    document.querySelectorAll('[data-mobile-close]').forEach((element) => {
        element.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
        });
    });

    document.querySelectorAll('[data-sidebar-link]').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= desktopSidebarBreakpoint) {
                body.classList.remove('sidebar-open');
            }
        });
    });

    document.querySelectorAll('[data-filter-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            if (window.innerWidth <= desktopSidebarBreakpoint) {
                body.classList.toggle('filters-open');
            }
        });
    });

    document.querySelectorAll('[data-toast-close]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('[data-toast]')?.remove();
        });
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submitButton = form.querySelector('button[type="submit"]');
            if (!(submitButton instanceof HTMLButtonElement)) {
                return;
            }

            const loadingText = submitButton.dataset.loadingText || 'Working...';
            const label = submitButton.querySelector('span:last-child');

            if (label instanceof HTMLSpanElement) {
                label.textContent = loadingText;
            } else {
                submitButton.textContent = loadingText;
            }

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
        });
    });

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const targetId = button.getAttribute('data-copy-target');
            const source = targetId ? document.getElementById(targetId) : null;

            if (!source) {
                return;
            }

            try {
                await navigator.clipboard.writeText(source.textContent?.trim() || '');
                showToast('Copied to clipboard.', 'success');
            } catch (error) {
                showToast('Copy failed on this browser.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-password-target');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });

    document.querySelectorAll('[data-print-page]').forEach((button) => {
        button.addEventListener('click', () => {
            window.print();
        });
    });

    const refreshUrl = body.dataset.refreshUrl;
    const refreshInterval = Number(body.dataset.refreshInterval || '0');

    if (refreshUrl && refreshInterval > 0) {
        window.setTimeout(() => {
            window.location.href = refreshUrl;
        }, refreshInterval);
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth > desktopSidebarBreakpoint) {
            body.classList.remove('sidebar-open');
            body.classList.remove('filters-open');
            return;
        }

        body.classList.remove('sidebar-condensed');
    });
});
