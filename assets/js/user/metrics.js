// General metrics and UI functionality
function updateAllMetrics() {
  const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh || 0), 0);
  const totalCost = totalKwh * currentRate;
  const dailyKwh = totalKwh / 30;

  const thisMonthKwhEl = document.getElementById('thisMonthKwh');
  const dailyConsumptionEl = document.getElementById('dailyConsumption');
  const monthlyCostEl = document.getElementById('monthlyCost');
  const forecastedCostEl = document.getElementById('forecastedCost');
  const budgetStatusBadge = document.getElementById('budgetStatusBadge');
  const budgetStatusText = document.getElementById('budgetStatusText');

  // Real-time consumption stays at 0 (no hardware)
  if (thisMonthKwhEl) thisMonthKwhEl.textContent = '0.0';
  if (dailyConsumptionEl) dailyConsumptionEl.textContent = dailyKwh.toFixed(2);
  if (monthlyCostEl) monthlyCostEl.textContent = Math.round(totalCost);
  // Forecasted monthly consumption uses the equation from real-time consumption (sum of monthly_kwh from appliances)
  if (forecastedCostEl) forecastedCostEl.textContent = totalKwh.toFixed(1);

  // Update budget status
  if (budgetStatusBadge && budgetStatusText && monthlyBudget > 0) {
    const budgetExceeded = totalCost > monthlyBudget;
    const budgetPercentage = ((totalCost / monthlyBudget) * 100).toFixed(1);
    const difference = Math.abs(totalCost - monthlyBudget).toFixed(2);
    
    if (budgetExceeded) {
      budgetStatusBadge.className = 'badge bg-danger';
      budgetStatusBadge.textContent = 'Exceeded';
      budgetStatusText.textContent = `₱${difference} over budget (${budgetPercentage}%)`;
      budgetStatusText.className = 'small text-danger';
    } else {
      budgetStatusBadge.className = 'badge bg-success';
      budgetStatusBadge.textContent = 'Within Budget';
      budgetStatusText.textContent = `₱${difference} remaining (${budgetPercentage}%)`;
      budgetStatusText.className = 'small text-success';
    }
  } else if (budgetStatusBadge && budgetStatusText) {
    budgetStatusBadge.className = 'badge bg-secondary';
    budgetStatusBadge.textContent = 'No Budget Set';
    budgetStatusText.textContent = 'Set a monthly budget in settings';
    budgetStatusText.className = 'small text-muted';
  }

  // Save electricity reading
  if (totalKwh > 0) {
    saveReading(dailyKwh, totalKwh);
  }

  updateForecastChart(totalKwh);
  updateApplianceDisplay();

  const tips = document.getElementById('energyTipsContent');
  if (tips) {
    tips.style.display = appliances.length > 0 ? 'block' : 'none';
  }
}

async function saveReading(dailyKwh, monthlyKwh) {
  try {
    const power = dailyKwh * 1000; // Convert to watts
    const voltage = 220;
    const current = power / voltage;

    await fetch('api/save_readings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        energy_kwh: dailyKwh,
        voltage: voltage,
        current: current,
        power: power
      })
    });
  } catch (error) {
    console.error('Error saving reading:', error);
  }
}

async function saveSettings() {
  const budget = parseFloat(document.getElementById('monthlyBudget').innerText.replace('₱', '').replace(',', ''));
  try {
    const response = await fetch('settings/save_settings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        monthly_budget: budget
      })
    });
    const result = await response.json();
    if (!result.success) {
      console.error('Error saving settings:', result.error);
    }
  } catch (error) {
    console.error('Error saving settings:', error);
  }
}