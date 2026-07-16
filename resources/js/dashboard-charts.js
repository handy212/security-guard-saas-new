import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

function tenantBrandColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--tenant-brand').trim() || '#0f766e';
}

function chartPalette() {
    const brand = tenantBrandColor();
    return [brand, '#d97706', '#0891b2', '#e11d48', '#65a30d'];
}

function initDashboardCharts() {
    const chartColors = chartPalette();

    document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
        if (canvas.dataset.chartReady) return;

        const type = canvas.dataset.dashboardChart;
        const payload = JSON.parse(canvas.dataset.chartPayload || '{}');

        if (type === 'donut') {
            const labels = Object.keys(payload);
            const values = Object.values(payload);
            const total = values.reduce((a, b) => a + b, 0);

            if (!total) return;

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: chartColors.slice(0, labels.length),
                        borderWidth: 0,
                        hoverOffset: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: { legend: { display: false } },
                },
            });
        }

        if (type === 'bar') {
            const labels = Object.keys(payload).map((d) => {
                const date = new Date(d);
                return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            });
            const values = Object.values(payload);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: tenantBrandColor(),
                        borderRadius: 3,
                        barThickness: 20,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: document.documentElement.classList.contains('dark') ? '#a1a1aa' : '#71717a' },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: document.documentElement.classList.contains('dark') ? '#a1a1aa' : '#71717a' },
                            grid: { color: document.documentElement.classList.contains('dark') ? '#3f3f46' : '#f4f4f5' },
                        },
                    },
                },
            });
        }

        canvas.dataset.chartReady = '1';
    });
}

document.addEventListener('DOMContentLoaded', initDashboardCharts);
document.addEventListener('livewire:navigated', initDashboardCharts);
window.addEventListener('theme-changed', () => {
    document.querySelectorAll('[data-dashboard-chart][data-chart-ready]').forEach((canvas) => {
        const chart = Chart.getChart(canvas);
        chart?.destroy();
        delete canvas.dataset.chartReady;
    });
    initDashboardCharts();
});
