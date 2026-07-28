/**
 * Support Portal — Main JavaScript
 * Handles: sidebar toggle, AJAX setup, flash alerts,
 * CSRF injection, confirm dialogs, form helpers.
 */

'use strict';

/* ── CSRF Token ──────────────────────────────────────────────── */
const SupportPortal = {

  csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',

  /* ── Init ─────────────────────────────────────────────────── */
  init() {
    this.initSidebar();
    this.initAlerts();
    this.initConfirmDialogs();
    this.initAjaxForms();
    this.initTooltips();
    this.initDropdowns();
    this.initNotifications();
    this.initTableSearch();
  },

  /* ── Sidebar ──────────────────────────────────────────────── */
  initSidebar() {
    const sidebar        = document.getElementById('sidebar');
    const mainContent    = document.getElementById('mainContent');
    const toggleBtn      = document.getElementById('sidebarToggle');
    const overlay        = document.getElementById('sidebarOverlay');
    const isMobile       = () => window.innerWidth < 992;
    const COLLAPSED_KEY  = 'sidebar_collapsed';

    if (!sidebar) return;

    // Restore collapse state on desktop
    if (!isMobile() && localStorage.getItem(COLLAPSED_KEY) === '1') {
      sidebar.classList.add('collapsed');
      mainContent?.classList.add('sidebar-collapsed');
    }

    // Toggle
    toggleBtn?.addEventListener('click', () => {
      if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        overlay?.classList.toggle('show');
      } else {
        const collapsed = sidebar.classList.toggle('collapsed');
        mainContent?.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0');
      }
    });

    // Overlay closes sidebar on mobile
    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
      overlay.classList.remove('show');
    });

    // Responsive: reset on resize
    window.addEventListener('resize', () => {
      if (!isMobile()) {
        sidebar.classList.remove('mobile-open');
        overlay?.classList.remove('show');
      }
    });

    // Highlight active nav link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link-item').forEach(link => {
      const href = link.getAttribute('href') ?? '';
      if (href && currentPath.startsWith(href) && href !== '/') {
        link.classList.add('active');
      }
    });
  },

  /* ── Flash Alerts ─────────────────────────────────────────── */
  initAlerts() {
    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(alert => {
      setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert?.close();
      }, 5000);
    });
  },

  /* ── Confirm Dialogs ──────────────────────────────────────── */
  initConfirmDialogs() {
    // data-confirm="Are you sure?" on any button/link
    document.addEventListener('click', (e) => {
      const el = e.target.closest('[data-confirm]');
      if (!el) return;

      e.preventDefault();
      const message = el.dataset.confirm || 'Are you sure?';

      SupportPortal.confirm(message, () => {
        // If it's a form submit button
        const form = el.closest('form');
        if (form) { form.submit(); return; }

        // If it's a link
        const href = el.getAttribute('href');
        if (href) { window.location.href = href; }

        // data-action with hidden form for DELETE
        const action = el.dataset.action;
        const method = el.dataset.method ?? 'POST';
        if (action) {
          SupportPortal.submitForm(action, method, { id: el.dataset.id });
        }
      });
    });
  },

  /* ── AJAX Form Helpers ────────────────────────────────────── */
  initAjaxForms() {
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!form.matches('[data-ajax]')) return;
      e.preventDefault();

      const btn = form.querySelector('[type="submit"]');
      const originalText = btn?.innerHTML;
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
      }

      const formData = new FormData(form);
      const method   = (form.dataset.method ?? form.method ?? 'POST').toUpperCase();
      const url      = form.action;

      SupportPortal.ajax(url, method, Object.fromEntries(formData))
        .then(data => {
          if (data.success) {
            SupportPortal.showToast(data.message ?? 'Success', 'success');
            const redirect = form.dataset.redirect ?? data.redirect;
            if (redirect) { setTimeout(() => window.location.href = redirect, 800); }
            else if (form.dataset.reload) { setTimeout(() => window.location.reload(), 800); }
          } else {
            SupportPortal.showToast(data.message ?? 'An error occurred', 'danger');
            if (data.errors) SupportPortal.showFormErrors(form, data.errors);
          }
        })
        .catch(() => SupportPortal.showToast('Network error. Please try again.', 'danger'))
        .finally(() => {
          if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        });
    });
  },

  /* ── Tooltips ─────────────────────────────────────────────── */
  initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
  },

  /* ── Dropdowns ────────────────────────────────────────────── */
  initDropdowns() {
    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
          menu.classList.remove('show');
        });
      }
    });
  },

  /* ── Notifications ────────────────────────────────────────── */
  initNotifications() {
    const bell = document.getElementById('notifBell');
    if (!bell) return;

    // Load unread count
    SupportPortal.ajax('/api/notifications/unread-count', 'GET')
      .then(data => {
        if (data.count > 0) {
          const dot = bell.querySelector('.notif-dot');
          if (dot) {
            dot.textContent = data.count > 9 ? '9+' : data.count;
            dot.style.display = 'flex';
          }
        }
      })
      .catch(() => {}); // Silently fail
  },

  /* ── Table Search ─────────────────────────────────────────── */
  initTableSearch() {
    document.querySelectorAll('[data-table-search]').forEach(input => {
      const tableId = input.dataset.tableSearch;
      const table   = document.getElementById(tableId);
      if (!table) return;

      input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    });
  },

  /* ── Core AJAX helper ─────────────────────────────────────── */
  ajax(url, method = 'GET', data = null) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': SupportPortal.csrfToken,
      },
    };

    if (data && method !== 'GET') {
      options.body = JSON.stringify(data);
    }

    if (data && method === 'GET') {
      url += '?' + new URLSearchParams(data).toString();
    }

    return fetch(url, options).then(res => {
      if (!res.ok && res.status === 419) {
        SupportPortal.showToast('Session expired. Please refresh the page.', 'warning');
        throw new Error('CSRF expired');
      }
      return res.json();
    });
  },

  /* ── Submit hidden form ───────────────────────────────────── */
  submitForm(action, method = 'POST', fields = {}) {
    const form   = document.createElement('form');
    form.method  = 'POST';
    form.action  = action;

    // CSRF
    const csrf   = document.createElement('input');
    csrf.type    = 'hidden';
    csrf.name    = '_csrf_token';
    csrf.value   = SupportPortal.csrfToken;
    form.appendChild(csrf);

    // Method override for DELETE/PUT
    if (['DELETE','PUT','PATCH'].includes(method.toUpperCase())) {
      const mv   = document.createElement('input');
      mv.type    = 'hidden';
      mv.name    = '_method';
      mv.value   = method.toUpperCase();
      form.appendChild(mv);
    }

    // Extra fields
    Object.entries(fields).forEach(([name, value]) => {
      if (value == null) return;
      const input   = document.createElement('input');
      input.type    = 'hidden';
      input.name    = name;
      input.value   = value;
      form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
  },

  /* ── Confirm modal ────────────────────────────────────────── */
  confirm(message, onConfirm, options = {}) {
    const existing = document.getElementById('spConfirmModal');
    if (existing) existing.remove();

    const {
      title       = 'Confirm Action',
      confirmText = 'Confirm',
      cancelText  = 'Cancel',
      type        = 'danger',
    } = options;

    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'spConfirmModal';
    modal.tabIndex = -1;
    modal.innerHTML = `
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <h6 class="modal-title fw-bold">${title}</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body pt-2">
            <p class="text-muted mb-0" style="font-size:.875rem;">${message}</p>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">${cancelText}</button>
            <button type="button" class="btn btn-sm btn-${type}" id="spConfirmBtn">${confirmText}</button>
          </div>
        </div>
      </div>`;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    document.getElementById('spConfirmBtn').addEventListener('click', () => {
      bsModal.hide();
      onConfirm();
    });

    modal.addEventListener('hidden.bs.modal', () => modal.remove());
  },

  /* ── Toast notifications ──────────────────────────────────── */
  showToast(message, type = 'success', duration = 4000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;min-width:280px;max-width:380px;';
      document.body.appendChild(container);
    }

    const icons = { success:'check-circle-fill', danger:'exclamation-triangle-fill', warning:'exclamation-circle-fill', info:'info-circle-fill' };
    const colors = { success:'#166534', danger:'#991b1b', warning:'#92400e', info:'#1d4ed8' };
    const bgs    = { success:'#f0fdf4', danger:'#fef2f2', warning:'#fffbeb', info:'#eff6ff' };

    const toast = document.createElement('div');
    toast.style.cssText = `background:${bgs[type]||'#fff'};border:1px solid ${bgs[type]||'#e2e8f0'};border-radius:12px;padding:.75rem 1rem;display:flex;align-items:flex-start;gap:.6rem;box-shadow:0 4px 16px rgba(0,0,0,.1);animation:fadeInUp .25s ease;`;
    toast.innerHTML = `
      <i class="bi bi-${icons[type]||'info-circle'}" style="color:${colors[type]||'#374151'};font-size:1rem;margin-top:1px;flex-shrink:0;"></i>
      <span style="font-size:.875rem;color:${colors[type]||'#374151'};flex:1;">${message}</span>
      <button onclick="this.closest('div').remove()" style="background:none;border:none;color:${colors[type]};opacity:.6;cursor:pointer;padding:0;font-size:1rem;line-height:1;">&times;</button>`;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = 'opacity .4s,transform .4s';
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(20px)';
      setTimeout(() => toast.remove(), 400);
    }, duration);
  },

  /* ── Form errors ──────────────────────────────────────────── */
  showFormErrors(form, errors) {
    // Clear existing
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback';
      fb.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode.insertBefore(fb, input.nextSibling);
    });
  },

  /* ── Format helpers ───────────────────────────────────────── */
  formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
  },

  timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)      return diff + 's ago';
    if (diff < 3600)    return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)   return Math.floor(diff/3600) + 'h ago';
    if (diff < 604800)  return Math.floor(diff/86400) + 'd ago';
    return this.formatDate(dateStr);
  },
};

/* ── Boot on DOM ready ────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => SupportPortal.init());
