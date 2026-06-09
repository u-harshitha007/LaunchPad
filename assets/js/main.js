/**
 * LaunchPad — Main JavaScript
 * Sidebar, modals, charts, and UI interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initProgressRings();
    initCharts();
    initFlashDismiss();
});

/**
 * Mobile sidebar toggle
 */
function initSidebar() {
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay?.classList.toggle('active');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
}

/**
 * Modal open/close handlers
 */
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal buttons
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeAllModals();
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay.active').forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = '';
}

/**
 * Open modal and pre-fill edit form
 */
function openEditModal(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Fill form fields from data object
    Object.keys(data).forEach(key => {
        const field = modal.querySelector(`[name="${key}"]`);
        if (field) {
            field.value = data[key] ?? '';
        }
    });

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Animate progress rings on page load
 */
function initProgressRings() {
    document.querySelectorAll('.progress-ring-fill').forEach(ring => {
        const percent = parseFloat(ring.dataset.percent || 0);
        const circumference = 2 * Math.PI * 60; // radius = 60
        ring.style.strokeDasharray = circumference;
        ring.style.strokeDashoffset = circumference;

        // Trigger animation after a brief delay
        requestAnimationFrame(() => {
            setTimeout(() => {
                const offset = circumference - (percent / 100) * circumference;
                ring.style.strokeDashoffset = offset;
            }, 100);
        });
    });
}

/**
 * Render bar charts from data attributes
 */
function initCharts() {
    document.querySelectorAll('.chart-container[data-chart]').forEach(container => {
        try {
            const data = JSON.parse(container.dataset.chart);
            if (!data || data.length === 0) return;

            const maxVal = Math.max(...data.map(d => d.count), 1);

            container.innerHTML = data.map((item, i) => {
                const height = Math.max((item.count / maxVal) * 160, 4);
                const colorClass = i % 2 === 0 ? '' : 'emerald';
                return `
                    <div class="chart-bar-group">
                        <div class="chart-bar ${colorClass}" style="height: ${height}px" title="${item.count}"></div>
                        <span class="chart-label">${item.label || item.category || item.status}</span>
                    </div>
                `;
            }).join('');
        } catch (e) {
            console.warn('Chart data parse error:', e);
        }
    });
}

/**
 * Auto-dismiss flash alerts after 5 seconds
 */
function initFlashDismiss() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Confirm delete action
 */
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

/**
 * Update progress bar preview when range input changes
 */
function bindProgressPreview() {
    const range = document.querySelector('input[name="progress_percent"]');
    const preview = document.querySelector('.progress-preview-fill');
    const previewText = document.querySelector('.progress-preview-text');

    if (range && preview) {
        range.addEventListener('input', () => {
            preview.style.width = range.value + '%';
            if (previewText) previewText.textContent = range.value + '%';
        });
    }
}

// Export for inline onclick handlers
window.openEditModal = openEditModal;
window.confirmDelete = confirmDelete;
window.closeAllModals = closeAllModals;
