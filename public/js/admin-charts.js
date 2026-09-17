"use strict";
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('revenue-chart');
    if (!canvas) {
        return;
    }
    const apiUrl = canvas.dataset.apiUrl || '/api.php';
    fetch(`${apiUrl}?action=revenue_chart&days=14`)
        .then((res) => res.json())
        .then((points) => {
        const labels = points.map((p) => {
            const d = new Date(p.day);
            return `${d.getDate()}/${d.getMonth() + 1}`;
        });
        const values = points.map((p) => p.revenue);
        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Doanh thu (đ)',
                        data: values,
                        borderColor: '#e50914',
                        backgroundColor: 'rgba(229,9,20,0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        ticks: { color: '#9ca0a8' },
                        grid: { color: 'rgba(255,255,255,0.06)' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca0a8',
                            callback: (value) => value.toLocaleString('vi-VN'),
                        },
                        grid: { color: 'rgba(255,255,255,0.06)' },
                    },
                },
            },
        });
    });
});
