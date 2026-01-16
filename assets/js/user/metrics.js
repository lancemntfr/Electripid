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

  // Update energy tips based on current status
  updateEnergyTips();

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

      // Also trigger email/SMS alerts if budget is exceeded
      if (title.includes('Alert') || title.includes('Warning')) {
        await triggerBudgetAlerts(title, message);
      }
    }
  } catch (error) {
    console.error('Error saving budget notification:', error);
  }
}

async function triggerBudgetAlerts(title, message) {
  try {
    // Get current budget data for the alert
    const budgetStatusBadge = document.getElementById('budgetStatusBadge');
    const budgetStatusText = document.getElementById('budgetStatusText');

    let budgetData = {};
    if (budgetStatusBadge && budgetStatusText) {
      const budgetText = document.getElementById('monthlyBudget')?.textContent?.replace('₱', '').replace(/,/g, '').trim();
      const costText = document.getElementById('monthlyCost')?.textContent?.replace(/₱/g, '').replace(/,/g, '').trim();

      budgetData = {
        monthly_budget: parseFloat(budgetText) || 0,
        current_cost: parseFloat(costText) || 0,
        exceeded_amount: 0,
        percentage: 0
      };

      if (budgetData.monthly_budget > 0 && budgetData.current_cost > budgetData.monthly_budget) {
        budgetData.exceeded_amount = budgetData.current_cost - budgetData.monthly_budget;
        budgetData.percentage = ((budgetData.current_cost / budgetData.monthly_budget) * 100);
      }
    }

    const alertType = title.includes('Alert') ? 'alert' : 'warning';

    await fetch('notification/trigger_budget_alert.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        alert_type: alertType,
        budget_data: budgetData,
        title: title,
        message: message
      })
    });
  } catch (error) {
    console.error('Error triggering budget alerts:', error);
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

// Energy Tips and Recommendations System
function updateEnergyTips() {
  const tipsContent = document.getElementById('energyTipsContent');
  if (!tipsContent) return;

  // Calculate current metrics
  const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh || 0), 0);
  const dailyKwh = totalKwh / 30;
  const now = new Date();
  const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
  const monthlyKwh = dailyKwh * daysInMonth;
  const monthlyCost = monthlyKwh * currentRate;

  // Get budget from global variable or DOM
  let budget = monthlyBudget;
  if (!budget || budget === 0) {
    const budgetEl = document.getElementById('monthlyBudget');
    if (budgetEl) {
      const budgetText = budgetEl.textContent.replace('₱', '').replace(/,/g, '').trim();
      budget = parseFloat(budgetText) || 0;
    }
  }

  const budgetExceeded = budget > 0 && monthlyCost > budget;
  const percentageOverBudget = budgetExceeded 
    ? ((monthlyCost - budget) / budget * 100).toFixed(1)
    : 0;

  // Clear existing content
  tipsContent.innerHTML = '';

  if (appliances.length === 0) {
    // No appliances tracked
    tipsContent.innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="bi bi-lightbulb" style="font-size: 3rem; opacity: 0.3;"></i>
        <p class="mt-3 mb-0">Add appliances to receive personalized energy-saving tips and recommendations.</p>
      </div>
    `;
    tipsContent.style.display = 'block';
    return;
  }

  // Don't show tips if there's no budget set or monthly cost is 0
  if (!budget || budget === 0 || monthlyCost === 0) {
    tipsContent.innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="bi bi-info-circle" style="font-size: 3rem; opacity: 0.3;"></i>
        <p class="mt-3 mb-0">Set a monthly budget in Settings to receive personalized energy-saving tips.</p>
      </div>
    `;
    tipsContent.style.display = 'block';
    return;
  }

  // Generate personalized tips based on budget status
  let tipsHTML = '';

  if (budgetExceeded) {
    // OVER BUDGET - Show actionable tips without the alert message
    // Find high-consumption appliances
    const applianceConsumption = appliances.map(app => {
      const monthlyKwh = parseFloat(app.monthly_kwh || 0);
      const monthlyCost = monthlyKwh * currentRate;
      return { ...app, monthlyKwh, monthlyCost };
    }).sort((a, b) => b.monthlyCost - a.monthlyCost);

    // Tip 1: Reduce usage of top energy consumers
    if (applianceConsumption.length > 0) {
      const topConsumer = applianceConsumption[0];
      const appName = topConsumer.name || topConsumer.appliance_name || 'Unknown';
      tipsHTML += `
        <div class="alert alert-warning mb-3">
          <i class="bi bi-arrow-down-circle me-2"></i>
          <strong>Reduce High Consumption:</strong>
          <p class="mb-0 mt-1 small">Your <strong>${appName}</strong> consumes ₱${topConsumer.monthlyCost.toFixed(2)}/month. Try reducing usage by 2 hours per day to save approximately ₱${(topConsumer.monthlyCost * 0.25).toFixed(2)}/month.</p>
        </div>
      `;
    }

    // Tip 2: Optimize usage schedule
    tipsHTML += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-clock-history me-2"></i>
        <strong>Optimize Usage Schedule:</strong>
        <p class="mb-0 mt-1 small">Run high-power appliances during off-peak hours (typically 10 PM - 6 AM) when electricity rates may be lower with some providers.</p>
      </div>
    `;

    // Tip 3: Check for energy vampires
    tipsHTML += `
      <div class="alert alert-warning mb-3">
        <i class="bi bi-plug me-2"></i>
        <strong>Eliminate Energy Vampires:</strong>
        <p class="mb-0 mt-1 small">Unplug devices when not in use. Standby power can account for 5-10% of your electricity bill.</p>
      </div>
    `;

  } else if (monthlyCost > budget * 0.8 && budget > 0) {
    // APPROACHING BUDGET - Show caution message
    tipsHTML += `
      <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
        <div class="d-flex align-items-start gap-2">
          <i class="bi bi-exclamation-circle" style="font-size: 1.2rem;"></i>
          <div>
            <strong>Approaching Budget Limit</strong>
            <p class="mb-0 mt-1">You're using ${((monthlyCost / budget) * 100).toFixed(0)}% of your monthly budget. Consider these tips to stay within budget:</p>
          </div>
        </div>
      </div>
    `;

    tipsHTML += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-thermometer-half me-2"></i>
        <strong>Smart Temperature Control:</strong>
        <p class="mb-0 mt-1 small">Set air conditioners to 24-26°C for optimal comfort and efficiency. Each degree lower can increase consumption by 5-8%.</p>
      </div>
    `;

    tipsHTML += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-lightbulb me-2"></i>
        <strong>LED Lighting:</strong>
        <p class="mb-0 mt-1 small">Replace incandescent bulbs with LED bulbs to save up to 75% on lighting costs.</p>
      </div>
    `;

  } else if (budget > 0) {
    // WITHIN BUDGET - Show positive reinforcement and general tips
    tipsHTML += `
      <div class="alert alert-success mb-3" style="border-left: 4px solid #28a745;">
        <div class="d-flex align-items-start gap-2">
          <i class="bi bi-check-circle-fill" style="font-size: 1.2rem;"></i>
          <div>
            <strong>Great Job! 🎉</strong>
            <p class="mb-0 mt-1">You're managing your energy efficiently. Keep up these good habits!</p>
          </div>
        </div>
      </div>
    `;

    tipsHTML += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-sun me-2"></i>
        <strong>Natural Resources:</strong>
        <p class="mb-0 mt-1 small">Maximize natural light during daytime and ventilation to reduce reliance on artificial lighting and cooling.</p>
      </div>
    `;

    tipsHTML += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-stars me-2"></i>
        <strong>Maintenance Tip:</strong>
        <p class="mb-0 mt-1 small">Clean air conditioner filters monthly and maintain appliances regularly for optimal efficiency.</p>
      </div>
    `;
  }

  // Always show a general energy-saving tip
  const generalTips = [
    {
      icon: 'bi-water',
      title: 'Water Heating Savings',
      text: 'Lower your water heater temperature to 50°C to save energy without sacrificing comfort.'
    },
    {
      icon: 'bi-device-ssd',
      title: 'Appliance Efficiency',
      text: 'Choose appliances with higher energy efficiency ratings when replacing old devices.'
    },
    {
      icon: 'bi-wind',
      title: 'Natural Cooling',
      text: 'Use electric fans before air conditioning. Fans use 98% less energy than AC units.'
    }
  ];

  const randomTip = generalTips[Math.floor(Math.random() * generalTips.length)];
  tipsHTML += `
    <div class="alert alert-light mb-0 border">
      <i class="bi ${randomTip.icon} me-2 text-primary"></i>
      <strong>${randomTip.title}:</strong>
      <p class="mb-0 mt-1 small">${randomTip.text}</p>
    </div>
  `;

  tipsContent.innerHTML = tipsHTML;
  tipsContent.style.display = 'block';
}