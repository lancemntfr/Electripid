// General metrics and UI functionality
function updateAllMetrics() {
  const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh || 0), 0);
  const totalCost = totalKwh * currentRate;
  const dailyKwh = totalKwh / 30;

  const activeAppliancesEl = document.getElementById('activeAppliances');
  const thisMonthKwhEl = document.getElementById('thisMonthKwh');
  const dailyConsumptionEl = document.getElementById('dailyConsumption');
  const monthlyCostEl = document.getElementById('monthlyCost');

  if (activeAppliancesEl) activeAppliancesEl.textContent = appliances.length;
  if (thisMonthKwhEl) thisMonthKwhEl.textContent = totalKwh.toFixed(1);
  if (dailyConsumptionEl) dailyConsumptionEl.textContent = dailyKwh.toFixed(2);
  if (monthlyCostEl) monthlyCostEl.textContent = Math.round(totalCost);

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