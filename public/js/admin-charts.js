"use strict";
const GRID_COLOR = 'rgba(255,255,255,0.06)';
const TICK_COLOR = '#9ca0a8';
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas.stats-chart').forEach((canvas) => {
        try {
            const series = JSON.parse(canvas.dataset.series || '');
            drawRevenueChart(canvas, canvas.dataset.kind === 'bar' ? 'bar' : 'line', series);
        }
        catch (e) {
            const note = document.createElement('p');
            note.className = 'empty-note';
            note.textContent = 'Không vẽ được biểu đồ, vui lòng xem bảng số liệu bên dưới.';
            canvas.replaceWith(note);
        }
    });
});
function formatRevenue(value) {
    return Number(value).toLocaleString('vi-VN') + 'đ';
}
function drawRevenueChart(canvas, kind, series) {
    const horizontal = kind === 'bar';
    const valueAxis = {
        beginAtZero: true,
        ticks: { color: TICK_COLOR, callback: (value) => formatRevenue(value) },
        grid: { color: GRID_COLOR },
    };
    const labelAxis = { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } };
    new Chart(canvas, {
        type: kind,
        data: {
            labels: series.labels,
            datasets: [
                {
                    label: 'Doanh thu',
                    data: series.revenue,
                    borderColor: '#e50914',
                    backgroundColor: horizontal ? 'rgba(229,9,20,0.6)' : 'rgba(229,9,20,0.1)',
                    tension: 0.3,
                    fill: !horizontal,
                },
            ],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `Doanh thu: ${formatRevenue(item.raw)}`,
                    },
                },
            },
            scales: horizontal ? { x: valueAxis, y: labelAxis } : { x: labelAxis, y: valueAxis },
        },
    });
}
