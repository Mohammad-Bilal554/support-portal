/**
 * Chart helpers & default config for Chart.js
 * Provides consistent styling across all charts.
 */

'use strict';

const PortalCharts = {

    /* ── Default options ──────────────────────────────────────── */
    defaults: {
        font:        { family: "'Inter', sans-serif", size: 11 },
        colors: {
            primary:   '#0d6efd',
            success:   '#198754',
            danger:    '#dc3545',
            warning:   '#ffc107',
            info:      '#0dcaf0',
            secondary: '#6c757d',
            purple:    '#6f42c1',
            orange:    '#fd7e14',
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor:      '#e2e8f0',
            bodyColor:       '#94a3b8',
            borderColor:     '#1e293b',
            borderWidth:     1,
            padding:         10,
            cornerRadius:    8,
            displayColors:   true,
        },
        grid: { color: '#f1f5f9' },
        ticks: { color: '#94a3b8' },
    },

    /* ── Line chart ───────────────────────────────────────────── */
    line(canvasId, labels, datasets, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const styledDatasets = datasets.map((ds, i) => {
            const colors = Object.values(this.defaults.colors);
            const color  = ds.color ?? colors[i % colors.length];
            return {
                label:                ds.label,
                data:                 ds.data,
                borderColor:          color,
                backgroundColor:      this.hexToRgba(color, 0.08),
                borderWidth:          2.5,
                fill:                 ds.fill ?? true,
                tension:              0.4,
                pointRadius:          4,
                pointBackgroundColor: color,
                pointBorderColor:     '#fff',
                pointBorderWidth:     2,
                ...ds.extra,
            };
        });

        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: styledDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend:  options.legend  ?? { display: datasets.length > 1 },
                    tooltip: this.defaults.tooltip,
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: this.defaults.font, color: this.defaults.ticks.color } },
                    y: { grid: this.defaults.grid,  ticks: { font: this.defaults.font, color: this.defaults.ticks.color, stepSize: 1 }, beginAtZero: true },
                },
                ...options.chart,
            },
        });
    },

    /* ── Bar chart ────────────────────────────────────────────── */
    bar(canvasId, labels, datasets, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const colors = Object.values(this.defaults.colors);
        const styledDatasets = datasets.map((ds, i) => ({
            label:           ds.label,
            data:            ds.data,
            backgroundColor: ds.color ?? colors[i % colors.length],
            borderRadius:    6,
            borderSkipped:   false,
            ...ds.extra,
        }));

        return new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: styledDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend:  options.legend  ?? { display: datasets.length > 1 },
                    tooltip: this.defaults.tooltip,
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: this.defaults.font, color: this.defaults.ticks.color } },
                    y: { grid: this.defaults.grid,  ticks: { font: this.defaults.font, color: this.defaults.ticks.color, stepSize: 1 }, beginAtZero: true },
                },
                ...options.chart,
            },
        });
    },

    /* ── Doughnut chart ───────────────────────────────────────── */
    doughnut(canvasId, labels, data, colors = null, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const defaultColors = Object.values(this.defaults.colors);
        const bgColors = colors ?? defaultColors.slice(0, data.length);

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: bgColors,
                    borderWidth:     3,
                    borderColor:     '#ffffff',
                    hoverBorderColor:'#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: options.cutout ?? '72%',
                plugins: {
                    legend:  options.legend  ?? { display: false },
                    tooltip: this.defaults.tooltip,
                },
                ...options.chart,
            },
        });
    },

    /* ── Utility ──────────────────────────────────────────────── */
    hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r},${g},${b},${alpha})`;
    },

    /* ── Destroy & recreate ───────────────────────────────────── */
    destroy(canvasId) {
        const existing = Chart.getChart(canvasId);
        if (existing) existing.destroy();
    },
};
