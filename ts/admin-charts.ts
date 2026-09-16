declare const Chart: any;

interface RevenuePoint {
    day: string;
    revenue: number;
}

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('revenue-chart') as HTMLCanvasElement | null;
    if (!canvas) {
        return;
    }

    const apiUrl = canvas.dataset.apiUrl || '/api.php';

    fetch(`${apiUrl}?action=revenue_chart&days=14`)
        .then((res) => res.json() as Promise<RevenuePoint[]>)
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
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value: number) => value.toLocaleString('vi-VN'),
                            },
                        },
                    },
                },
            });
        });
});
