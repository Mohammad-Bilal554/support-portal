/**
 * AJAX Handler
 * Centralised AJAX utilities used by tickets,
 * conversations, status updates, and assignments.
 */

'use strict';

const Ajax = {

    /* ── Base request ─────────────────────────────────────────── */
    request(url, method = 'GET', data = null, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        const config = {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                ...options.headers,
            },
        };

        if (data instanceof FormData) {
            config.body = data;
            // Let browser set Content-Type with boundary for FormData
        } else if (data && method !== 'GET') {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(data);
        } else if (data && method === 'GET') {
            url += (url.includes('?') ? '&' : '?') + new URLSearchParams(data).toString();
        }

        return fetch(url, config)
            .then(res => {
                if (res.status === 419) throw new Error('CSRF token expired. Please refresh.');
                if (res.status === 401) { window.location.href = '/auth/login'; throw new Error('Unauthenticated'); }
                if (res.status === 403) throw new Error('Access denied.');
                return res.json();
            });
    },

    get(url, params = {})           { return this.request(url, 'GET', params); },
    post(url, data = {})            { return this.request(url, 'POST', data); },
    put(url, data = {})             { return this.request(url, 'PUT', data); },
    patch(url, data = {})           { return this.request(url, 'PATCH', data); },
    delete(url, data = {})          { return this.request(url, 'DELETE', data); },
    upload(url, formData)           { return this.request(url, 'POST', formData); },

    /* ── Status change ────────────────────────────────────────── */
    changeTicketStatus(ticketId, newStatus, note = '') {
        return this.post(`/tickets/${ticketId}/status`, { status: newStatus, note });
    },

    /* ── Assign ticket ────────────────────────────────────────── */
    assignTicket(ticketId, assigneeId) {
        return this.post(`/tickets/${ticketId}/assign`, { assigned_to: assigneeId });
    },

    /* ── Send reply ───────────────────────────────────────────── */
    sendReply(ticketId, formData) {
        return this.upload(`/tickets/${ticketId}/reply`, formData);
    },

    /* ── Mark notification read ───────────────────────────────── */
    markNotifRead(notifId) {
        return this.post(`/notifications/${notifId}/read`);
    },

    /* ── Loading button helper ────────────────────────────────── */
    setLoading(btn, loading = true) {
        if (!btn) return;
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText ?? 'Submit';
        }
    },
};
