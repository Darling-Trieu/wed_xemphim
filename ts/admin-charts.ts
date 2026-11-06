declare const Chart: any;

interface ChartSeries {
    labels: string[];
    revenue: number[];
}

const GRID_COLOR = 'rgba(255,255,255,0.06)';
const TICK_COLOR = '#9ca0a8';

/**
 * Vẽ các biểu đồ thống kê trên Dashboard. Dữ liệu do server nhúng sẵn vào data-series
 * (cùng bộ lọc với bảng số liệu), data-kind = "line" | "bar".
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') {
        return;
    }
    document.querySelectorAll<HTMLCanvasElement>('canvas.stats-chart').forEach((canvas) => {
        let series: ChartSeries;
        try {
            series = JSON.parse(canvas.dataset.series || '');
        } catch (e) {
            return;
        }
        drawRevenueChart(canvas, canvas.dataset.kind === 'bar' ? 'bar' : 'line', series);
    });
});

function drawRevenueChart(canvas: HTMLCanvasElement, kind: 'line' | 'bar', series: ChartSeries): void {
    new Chart(canvas, {
        type: kind,
        data: {
            labels: series.labels,
            datasets: [
                {
                    label: 'Doanh thu (đ)',
                    data: series.revenue,
                    borderColor: '#e50914',
                    backgroundColor: kind === 'bar' ? 'rgba(229,9,20,0.6)' : 'rgba(229,9,20,0.1)',
                    tension: 0.3,
                    fill: kind === 'line',
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
                y: {
                    beginAtZero: true,
                    ticks: { color: TICK_COLOR, callback: (value: number) => value.toLocaleString('vi-VN') },
                    grid: { color: GRID_COLOR },
                },
            },
        },
    });
}
