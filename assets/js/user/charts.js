// Chart and forecast functionality
function initForecastChart() {
  const ctx = document.getElementById('forecastChart').getContext('2d');
  forecastChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: 'kWh',
        data: [],
        borderColor: '#1976d2',
        backgroundColor: 'rgba(25, 118, 210, 0.1)',
        tension: 0.4,
        fill: true,
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: '#e3f2fd'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
}

function updateForecastChart(totalKwh) {
  if (!forecastChart) return;

  const today = new Date();
  const weekCount = Math.min(4, Math.max(1, Math.ceil(today.getDate() / 7)));

  const labels = [];
  const data = [];

  const weeklyKwh = totalKwh / 4;
  const variation = weeklyKwh * 0.15;

  for (let i = 1; i <= weekCount; i++) {
    labels.push(`Week ${i}`);
    const predictedKwh = weeklyKwh + (Math.random() * variation - variation / 2);
    data.push(predictedKwh);
  }

  forecastChart.data.labels = labels;
  forecastChart.data.datasets[0].data = data;
  forecastChart.update();

  // Save monthly forecast to database
  if (totalKwh > 0) {
    saveForecast(totalKwh, totalKwh * currentRate);
  }
}

async function saveForecast(predictedKwh, predictedCost) {
  try {
    const today = new Date();
    const forecastDate = today.toISOString().split('T')[0];

    await fetch('api/save_forecast.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        forecast_type: 'monthly',
        predicted_kwh: predictedKwh,
        predicted_cost: predictedCost,
        source_type: 'appliances',
        forecast_date: forecastDate
      })
    });
  } catch (error) {
    console.error('Error saving forecast:', error);
  }
}