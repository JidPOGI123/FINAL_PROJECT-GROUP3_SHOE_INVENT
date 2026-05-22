/* ============================================================
   ShStorage — charts.js
   Chart.js helpers for the admin dashboard
   Called after Chart.js CDN is loaded and PHP injects data
   ============================================================ */

/* Shared chart defaults */
Chart.defaults.color = '#9aa0b8';
Chart.defaults.font.family = 'DM Sans';
Chart.defaults.font.size = 13;

/**
 * Render the brand doughnut / pie chart.
 * @param {string} canvasId  - id of the <canvas> element
 * @param {string[]} labels  - brand names
 * @param {number[]} data    - stock totals per brand
 */
function renderBrandChart(canvasId, labels, data) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: ['#ff5722', '#2196f3', '#4caf50', '#ffc107', '#9c27b0'],
        borderWidth: 0,
        hoverOffset: 10,
      }]
    },
    options: {
      responsive: true,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#9aa0b8',
            padding: 16,
            usePointStyle: true,
            pointStyleWidth: 10,
          }
        },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              return ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString() + ' units';
            }
          }
        }
      }
    }
  });
}

/**
 * Render a horizontal bar chart (e.g. stock per brand).
 * @param {string}   canvasId
 * @param {string[]} labels
 * @param {number[]} data
 * @param {string}   label  - dataset label
 */
function renderBarChart(canvasId, labels, data, label) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: label || 'Stock',
        data: data,
        backgroundColor: 'rgba(255,87,34,.75)',
        borderColor: '#ff5722',
        borderWidth: 1,
        borderRadius: 5,
      }]
    },
    options: {
      responsive: true,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              return ' ' + ctx.parsed.x.toLocaleString() + ' units';
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,.05)' },
          ticks: { color: '#9aa0b8' }
        },
        y: {
          grid: { display: false },
          ticks: { color: '#eef0f6' }
        }
      }
    }
  });
}

/**
 * Render a line chart (e.g. requests over time).
 * @param {string}   canvasId
 * @param {string[]} labels
 * @param {number[]} data
 * @param {string}   label
 */
function renderLineChart(canvasId, labels, data, label) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: label || 'Requests',
        data: data,
        borderColor: '#2196f3',
        backgroundColor: 'rgba(33,150,243,.1)',
        borderWidth: 2,
        pointBackgroundColor: '#2196f3',
        pointRadius: 4,
        tension: 0.35,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9aa0b8' } },
        y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9aa0b8' } }
      }
    }
  });
}