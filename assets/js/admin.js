/**
 * La Chingada — Panel de Administración JavaScript
 */

'use strict';

// ── Sidebar toggle (mobile) ────────────────────────────────────────────────
(function initSidebar() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar   = document.getElementById('admin-sidebar');
    const main      = document.querySelector('.admin-main');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            e.target !== toggleBtn &&
            !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
})();

// ── Auto-cerrar alertas ────────────────────────────────────────────────────
(function autoCloseAlerts() {
    const alerts = document.querySelectorAll('.alert-admin-success, .alert-admin-info');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
})();

// ── Preview de imagen al seleccionar ──────────────────────────────────────
document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    if (input.multiple) return; // Manejar múltiple por separado

    input.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const preview = this.parentElement.querySelector('img');
        if (preview) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
});

// ── Confirmaciones con texto dinámico ─────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

// ── Drag & drop reordenamiento de galería ─────────────────────────────────
(function initDragSort() {
    const grids = document.querySelectorAll('.gallery-admin-grid[data-sortable]');

    grids.forEach(grid => {
        let dragSrc = null;

        grid.querySelectorAll('.gallery-admin-item').forEach(item => {
            item.draggable = true;

            item.addEventListener('dragstart', function(e) {
                dragSrc = this;
                this.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.id || '');
            });

            item.addEventListener('dragend', function() {
                this.style.opacity = '1';
                grid.querySelectorAll('.gallery-admin-item').forEach(i => {
                    i.classList.remove('drag-over');
                });
            });

            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
                return false;
            });

            item.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            item.addEventListener('drop', function(e) {
                e.stopPropagation();
                this.classList.remove('drag-over');

                if (dragSrc !== this) {
                    const allItems = Array.from(grid.querySelectorAll('.gallery-admin-item'));
                    const srcIndex  = allItems.indexOf(dragSrc);
                    const destIndex = allItems.indexOf(this);

                    if (srcIndex < destIndex) {
                        grid.insertBefore(dragSrc, this.nextSibling);
                    } else {
                        grid.insertBefore(dragSrc, this);
                    }

                    // Guardar nuevo orden via AJAX
                    saveOrder(grid);
                }
                return false;
            });
        });

        function saveOrder(grid) {
            const ids = Array.from(grid.querySelectorAll('.gallery-admin-item'))
                             .map(item => item.dataset.id)
                             .filter(Boolean);

            fetch('?action=reorder_gallery', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids }),
            }).catch(console.error);
        }
    });
})();

// ── Validación de formularios admin en tiempo real ────────────────────────
(function initFormValidation() {
    const forms = document.querySelectorAll('.admin-form-validate');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const required = this.querySelectorAll('[required]');
            let valid = true;

            required.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--admin-danger)';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!valid) {
                e.preventDefault();
                // Scroll al primer error
                const firstError = form.querySelector('[required]:invalid, [style*="admin-danger"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
})();

// ── AJAX para probar conexión de DB (en install.php) ─────────────────────
window.testDbConnection = async function(formId, resultId) {
    const form   = document.getElementById(formId);
    const result = document.getElementById(resultId);
    if (!form || !result) return;

    const formData = new FormData(form);
    formData.append('action', 'test_connection');

    result.innerHTML = '<span style="color:#6b7280">Probando conexión...</span>';

    try {
        const resp = await fetch('install.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            result.innerHTML = '<span style="color:#10b981">✅ ' + data.message + '</span>';
        } else {
            result.innerHTML = '<span style="color:#ef4444">❌ ' + data.error + '</span>';
        }
    } catch {
        result.innerHTML = '<span style="color:#ef4444">❌ Error de conexión</span>';
    }
};

// ── Buscador en tablas admin ───────────────────────────────────────────────
(function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');

    searchInputs.forEach(input => {
        const tableId = input.dataset.tableSearch;
        const table   = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');

        input.addEventListener('input', () => {
            const q = input.value.toLowerCase().trim();

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = !q || text.includes(q) ? '' : 'none';
            });
        });
    });
})();

// ── Notificaciones toast ───────────────────────────────────────────────────
window.showToast = function(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `admin-toast admin-toast-${type}`;
    toast.textContent = message;

    const style = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: #fff; padding: 12px 20px; border-radius: 8px;
        font-size: .9rem; font-weight: 600;
        box-shadow: 0 4px 20px rgba(0,0,0,.2);
        animation: toastIn 0.3s ease forwards;
    `;
    toast.style.cssText = style;

    const animStyle = document.createElement('style');
    animStyle.textContent = `
        @keyframes toastIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    `;
    document.head.appendChild(animStyle);

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
};
