/**
 * GSM Guard Reusable UI Component Scripts
 * Handles Dark Mode states, Toast alerts, Modal triggers, and Global Loading spin overlays.
 */

// 1. Dark Mode Manager
const ThemeManager = {
    init() {
        const theme = localStorage.getItem('gsm_theme') || 'dark';
        if (theme === 'light') {
            document.body.classList.add('light-theme');
        } else {
            document.body.classList.remove('light-theme');
        }
    },
    toggle() {
        if (document.body.classList.contains('light-theme')) {
            document.body.classList.remove('light-theme');
            localStorage.setItem('gsm_theme', 'dark');
        } else {
            document.body.classList.add('light-theme');
            localStorage.setItem('gsm_theme', 'light');
        }
    }
};

// 2. Dynamic Toast Notification Manager
const ToastManager = {
    show(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-box ${type === 'error' ? 'toast-error' : ''}`;
        
        // Define color status dot
        const dotColor = type === 'error' ? '#FF3333' : '#00FF41';
        toast.innerHTML = `
            <span style="height: 8px; width: 8px; background-color: ${dotColor}; border-radius: 50%; display: inline-block;"></span>
            <span style="font-size: 13px; font-weight: 500; font-family: monospace;">${message}</span>
        `;

        container.appendChild(toast);

        // Slide in animation delay
        setTimeout(() => toast.classList.add('show'), 50);

        // Dismiss timeout
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
};

// 3. Modal Manager (With focus gates and keyboard escape listeners)
const ModalManager = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Lock scrolling

            // Register escape key close listener
            modal._escapeHandler = (e) => {
                if (e.key === 'Escape') this.close(modalId);
            };
            document.addEventListener('keydown', modal._escapeHandler);
        }
    },
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = ''; // Unlock scrolling

            if (modal._escapeHandler) {
                document.removeEventListener('keydown', modal._escapeHandler);
                delete modal._escapeHandler;
            }
        }
    }
};

// 4. Loading Spinner Overlays
const SpinnerManager = {
    show() {
        let spinner = document.getElementById('global-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'global-spinner';
            spinner.style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(2px); z-index: 99999; display: flex; align-items: center; justify-content: center;";
            spinner.innerHTML = `
                <div style="border: 3px solid rgba(0, 255, 65, 0.1); border-top-color: #00FF41; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                <style>
                    @keyframes spin { 
                        0% { transform: rotate(0deg); } 
                        100% { transform: rotate(360deg); } 
                    }
                </style>
            `;
            document.body.appendChild(spinner);
        }
    },
    hide() {
        const spinner = document.getElementById('global-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
};

// Auto-initialize theme on load
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
});
