// General metrics and UI functionality
function updateAllMetrics() {
  const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh || 0), 0);
  const dailyKwh = totalKwh / 30;
  
  // Get days in current month for accurate forecast
  const now = new Date();
  const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
  
  // Calculate forecasted monthly consumption: daily consumption * days in month
  const forecastedMonthlyKwh = dailyKwh * daysInMonth;
  const forecastedMonthlyCost = forecastedMonthlyKwh * currentRate;
  const monthlyCost = forecastedMonthlyCost;

  const thisMonthKwhEl = document.getElementById('thisMonthKwh');
  const dailyConsumptionEl = document.getElementById('dailyConsumption');
  const monthlyCostEl = document.getElementById('monthlyCost');
  const forecastedCostEl = document.getElementById('forecastedCost');

  // Set real-time consumption to 0 (will come from datasets)
  if (thisMonthKwhEl) thisMonthKwhEl.textContent = '0.0';
  if (dailyConsumptionEl) dailyConsumptionEl.textContent = dailyKwh.toFixed(2);
  if (monthlyCostEl) monthlyCostEl.textContent = Math.round(monthlyCost);
  // Display forecasted monthly consumption in kWh (daily kWh * days in month)
  if (forecastedCostEl) forecastedCostEl.textContent = forecastedMonthlyKwh.toFixed(2);

  // Update budget status using forecasted monthly cost
  updateBudgetStatus(forecastedMonthlyCost);

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

function updateBudgetStatus(monthlyCost) {
  const budgetStatusBadge = document.getElementById('budgetStatusBadge');
  const budgetStatusText = document.getElementById('budgetStatusText');
  
  if (!budgetStatusBadge || !budgetStatusText) return;

  // Get budget from global variable or DOM
  let budget = monthlyBudget;
  if (!budget || budget === 0) {
    // Try to get from DOM if not in global variable
    const budgetEl = document.getElementById('monthlyBudget');
    if (budgetEl) {
      const budgetText = budgetEl.textContent.replace('₱', '').replace(/,/g, '').trim();
      budget = parseFloat(budgetText) || 0;
    }
  }

  if (!budget || budget === 0) {
    budgetStatusBadge.textContent = 'Not Set';
    budgetStatusBadge.className = 'badge bg-secondary';
    budgetStatusText.textContent = 'No budget configured';
    const budgetStatusNote = document.getElementById('budgetStatusNote');
    if (budgetStatusNote) {
      budgetStatusNote.innerHTML = `
        <small class="text-muted d-block" style="font-size: 0.75rem;">
          <i class="bi bi-info-circle me-1"></i>
          <em>Set a monthly budget in Settings to track if your predicted cost exceeds your spending limit.</em>
        </small>
      `;
    }
    // Hide notification badge when budget is not set
    hideBudgetNotification();
    return;
  }

  const difference = monthlyCost - budget;
  const percentage = ((monthlyCost / budget) * 100).toFixed(1);
  const differenceAbs = Math.abs(difference).toFixed(2);

  const budgetStatusNote = document.getElementById('budgetStatusNote');
  
  if (difference < -50) {
    // Well within budget (more than ₱50 under)
    budgetStatusBadge.textContent = 'Within Budget';
    budgetStatusBadge.className = 'badge bg-success';
    budgetStatusText.textContent = `₱${differenceAbs} under (${percentage}% of budget)`;
    if (budgetStatusNote) {
      budgetStatusNote.innerHTML = `
        <small class="text-muted d-block" style="font-size: 0.75rem;">
          <i class="bi bi-info-circle me-1"></i>
          <em>You're well within your budget. Great job managing your energy consumption!</em>
        </small>
      `;
    }
    // Hide notification badge
    hideBudgetNotification();
  } else if (difference <= 0) {
    // Within budget (less than ₱50 under or at budget)
    budgetStatusBadge.textContent = 'Within Budget';
    budgetStatusBadge.className = 'badge bg-success';
    budgetStatusText.textContent = difference === 0 
      ? 'At budget limit' 
      : `₱${differenceAbs} under (${percentage}% of budget)`;
    if (budgetStatusNote) {
      budgetStatusNote.innerHTML = `
        <small class="text-muted d-block" style="font-size: 0.75rem;">
          <i class="bi bi-info-circle me-1"></i>
          <em>You're within your budget. Monitor your consumption to avoid exceeding it.</em>
        </small>
      `;
    }
    // Hide notification badge
    hideBudgetNotification();
  } else if (difference <= budget * 0.1) {
    // Slightly over budget (up to 10% over)
    budgetStatusBadge.textContent = 'Over Budget';
    budgetStatusBadge.className = 'badge bg-warning';
    budgetStatusText.textContent = `₱${differenceAbs} over (${percentage}% of budget)`;
    if (budgetStatusNote) {
      budgetStatusNote.innerHTML = `
        <small class="text-warning d-block" style="font-size: 0.75rem;">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong>Warning:</strong> You have exceeded your budget by ₱${differenceAbs}. Consider reducing appliance usage or adjusting your budget in Settings.
        </small>
      `;
    }
    // Save notification to database and show badge
    saveBudgetNotification(`Budget Warning`, `You have exceeded your budget by ₱${differenceAbs}. Consider reducing appliance usage or adjusting your budget in Settings.`);
    showBudgetNotification();
  } else {
    // Significantly over budget (more than 10% over)
    budgetStatusBadge.textContent = 'Over Budget';
    budgetStatusBadge.className = 'badge bg-danger';
    budgetStatusText.textContent = `₱${differenceAbs} over (${percentage}% of budget)`;
    if (budgetStatusNote) {
      budgetStatusNote.innerHTML = `
        <small class="text-danger d-block" style="font-size: 0.75rem;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>
          <strong>Alert:</strong> You have significantly exceeded your budget by ₱${differenceAbs} (${percentage}% over). Please reduce appliance usage or increase your budget in Settings to avoid unexpected costs.
        </small>
      `;
    }
    // Save notification to database and show badge
    saveBudgetNotification(`Budget Alert`, `You have significantly exceeded your budget by ₱${differenceAbs} (${percentage}% over). Please reduce appliance usage or increase your budget in Settings to avoid unexpected costs.`);
    showBudgetNotification();
  }
}

async function saveBudgetNotification(title, message) {
  try {
    // Check if notification already exists (to avoid duplicates)
    const checkResponse = await fetch('api/check_budget_notification.php');
    const checkResult = await checkResponse.json();
    
    // Only save if there's no unread notification
    if (checkResult.success && !checkResult.has_unread) {
      await fetch('api/save_notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          notification_type: 'budget',
          channel: 'in-app',
          related_type: 'budget',
          title: title,
          message: message
        })
      });
    }
  } catch (error) {
    console.error('Error saving budget notification:', error);
  }
}

async function showBudgetNotification() {
  const notificationBadge = document.getElementById('budgetNotificationBadge');
  if (!notificationBadge) return;

  // Check database first to see if notification was already read
  try {
    const response = await fetch('api/check_budget_notification.php');
    const result = await response.json();
    
    if (result.success && result.has_unread) {
      // Show badge if there's an unread notification
      notificationBadge.style.display = 'flex';
    } else {
      // Hide badge if notification was already read
      notificationBadge.style.display = 'none';
    }
  } catch (error) {
    console.error('Error checking notification:', error);
    // Hide badge on error
    notificationBadge.style.display = 'none';
  }
}

async function hideBudgetNotification() {
  const notificationBadge = document.getElementById('budgetNotificationBadge');
  const notificationBox = document.getElementById('budgetNotificationBox');
  if (notificationBadge) {
    notificationBadge.style.display = 'none';
  }
  if (notificationBox) {
    notificationBox.classList.remove('show');
  }
  
  // Mark any remaining unread budget notifications as read when back within budget
  try {
    await fetch('api/mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        notification_type: 'budget'
      })
    });
  } catch (error) {
    console.error('Error marking notifications as read:', error);
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