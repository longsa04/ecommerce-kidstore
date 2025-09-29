document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        const message = element.getAttribute('data-confirm') || 'Are you sure?';

        if (element.tagName === 'FORM') {
            element.addEventListener('submit', event => {
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        } else {
            element.addEventListener('click', event => {
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        }
    });

    const salesCanvas = document.getElementById('salesChart');
    if (salesCanvas) {
        const payload = salesCanvas.getAttribute('data-sales-chart');
        if (payload) {
            try {
                const data = JSON.parse(payload);
                if (window.Chart && Array.isArray(data.labels) && Array.isArray(data.totals)) {
                    new Chart(salesCanvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Sales',
                                data: data.totals,
                                tension: 0.35,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                                fill: true,
                                pointRadius: 4,
                                pointBackgroundColor: '#4f46e5',
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => `$${ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: value => `$${Number(value).toLocaleString()}`,
                                    },
                                },
                            },
                        },
                    });
                }
            } catch (error) {
                console.error('Failed to render sales chart', error);
            }
        }
    }
});
