/**
 * Notifications.js
 * Handles real-time in-app notification loading,
 * unread badge, and mark-as-read actions.
 */
'use strict';

const Notifications = {
    pollInterval: null,
    lastCount: 0,

    init() {
        this.loadUnreadCount();
        this.bindBellClick();
        this.bindMarkAllRead();
        // Poll every 60 seconds
        this.pollInterval = setInterval(() => this.loadUnreadCount(), 60000);
    },

    // ── Load unread count ─────────────────────────────────────────
    async loadUnreadCount() {
        try {
            const res  = await fetch('/api/notifications/unread-count', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            this.updateBadge(data.count ?? 0);
        } catch(e) { /* Silently fail */ }
    },

    updateBadge(count) {
        const dot  = document.querySelector('.notif-dot');
        const bell = document.getElementById('notifBell');
        if (!dot) return;

        if (count > 0) {
            dot.style.display    = 'flex';
            dot.style.width      = '16px';
            dot.style.height     = '16px';
            dot.style.fontSize   = '9px';
            dot.style.alignItems = 'center';
            dot.style.justifyContent = 'center';
            dot.textContent      = count > 9 ? '9+' : count;
            // Pulse animation on new notifications
            if (count > this.lastCount) {
                bell?.classList.add('pulse');
                setTimeout(() => bell?.classList.remove('pulse'), 3000);
            }
        } else {
            dot.style.display = 'none';
            dot.textContent   = '';
        }
        this.lastCount = count;
    },

    // ── Load notifications on bell click ─────────────────────────
    bindBellClick() {
        const bell = document.getElementById('notifBell');
        if (!bell) return;

        bell.addEventListener('show.bs.dropdown', () => this.loadNotifications());
    },

    async loadNotifications() {
        const list = document.getElementById('notifList');
        if (!list) return;

        list.innerHTML = `
            <div style="padding:1.5rem;text-align:center;">
                <div class="spinner-border spinner-border-sm text-primary"></div>
            </div>`;

        try {
            const res  = await fetch('/api/notifications', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (!data.success || !data.data?.length) {
                list.innerHTML = `
                    <div class="empty-state py-4">
                        <i class="bi bi-bell-slash" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:.5rem;opacity:.4;"></i>
                        <p style="font-size:.8rem;color:var(--text-muted);margin:0;">No new notifications</p>
                    </div>`;
                return;
            }

            list.innerHTML = data.data.map(n => this.renderNotif(n)).join('');
            list.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id  = item.dataset.id;
                    const url = item.dataset.url;
                    if (id) this.markRead(id);
                    if (url) window.location.href = url;
                });
            });
        } catch(e) {
            list.innerHTML = `<div style="padding:1rem;text-align:center;font-size:.8rem;color:var(--text-muted);">Failed to load notifications.</div>`;
        }
    },

    renderNotif(n) {
        const data      = n.data ? JSON.parse(n.data) : {};
        const ticketUrl = data.ticket_id ? `/tickets/${data.ticket_id}` : '#';
        const time      = this.timeAgo(n.created_at);
        const unreadCls = !n.is_read ? 'unread' : '';

        const icons = {
            new_ticket:      { icon: 'bi-ticket-perforated-fill', bg: '#dbeafe', color: '#1d4ed8' },
            ticket_assigned: { icon: 'bi-person-check-fill',      bg: '#dcfce7', color: '#166534' },
            ticket_replied:  { icon: 'bi-chat-dots-fill',         bg: '#f3e8ff', color: '#7c3aed' },
            status_changed:  { icon: 'bi-arrow-repeat',           bg: '#fffbeb', color: '#92400e' },
            ticket_resolved: { icon: 'bi-check-circle-fill',      bg: '#dcfce7', color: '#166534' },
            ticket_closed:   { icon: 'bi-x-circle-fill',          bg: '#f1f5f9', color: '#475569' },
        };

        const style = icons[n.type] ?? { icon:'bi-bell-fill', bg:'#f1f5f9', color:'#475569' };

        return `
        <div class="notif-item ${unreadCls}" data-id="${n.id}" data-url="${ticketUrl}" style="cursor:pointer;">
            <div class="notif-icon" style="background:${style.bg};color:${style.color};flex-shrink:0;">
                <i class="bi ${style.icon}"></i>
            </div>
            <div class="notif-text">
                <div class="notif-title">${this.escape(n.title)}</div>
                <div class="notif-desc">${this.escape(n.message)}</div>
                <div class="notif-time">${time}</div>
            </div>
            ${!n.is_read ? '<div style="width:8px;height:8px;border-radius:50%;background:#0d6efd;flex-shrink:0;margin-top:4px;"></div>' : ''}
        </div>`;
    },

    async markRead(notifId) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        try {
            await fetch(`/api/notifications/${notifId}/read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf,
                }
            });
            this.loadUnreadCount();
        } catch(e) { /* Silently fail */ }
    },

    bindMarkAllRead() {
        document.querySelector('[data-mark-all-read]')?.addEventListener('click', async (e) => {
            e.preventDefault();
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            try {
                await fetch('/api/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrf,
                    }
                });
                this.updateBadge(0);
                this.loadNotifications();
                SupportPortal.showToast('All notifications marked as read.', 'success');
            } catch(e) {
                SupportPortal.showToast('Failed to mark notifications.', 'danger');
            }
        });
    },

    // ── Helpers ───────────────────────────────────────────────────
    escape(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    },

    timeAgo(dateStr) {
        if (!dateStr) return '';
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)     return 'Just now';
        if (diff < 3600)   return Math.floor(diff/60) + 'm ago';
        if (diff < 86400)  return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    },
};

document.addEventListener('DOMContentLoaded', () => Notifications.init());
