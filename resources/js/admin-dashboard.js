import 'bootstrap/dist/css/bootstrap.min.css';
import '../css/admin-dashboard.css';
import Chart from 'chart.js/auto';

const chartData = window.adminDashboardData;
const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

Chart.defaults.font.family = '"Figtree", system-ui, sans-serif';
Chart.defaults.color = '#64748b';

const expenseCanvas = document.getElementById('maintenance-expense-chart');

if (expenseCanvas && chartData) {
    new Chart(expenseCanvas, {
        type: 'line',
        data: {
            labels: chartData.expenses.labels,
            datasets: [{
                label: 'Biaya maintenance',
                data: chartData.expenses.values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.10)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${rupiah.format(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => rupiah.format(value),
                    },
                    grid: {
                        color: '#eef2f7',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });
}

const maintenanceCanvas = document.getElementById('monthly-maintenance-chart');

if (maintenanceCanvas && chartData) {
    new Chart(maintenanceCanvas, {
        type: 'bar',
        data: {
            labels: chartData.maintenance.labels,
            datasets: [{
                label: 'Maintenance selesai',
                data: chartData.maintenance.values,
                backgroundColor: '#16a34a',
                borderRadius: 4,
                maxBarThickness: 32,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y}`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1,
                    },
                    grid: {
                        color: '#eef2f7',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });
}
