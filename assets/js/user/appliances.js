// Appliance management functionality
function loadAppliances() {
  // Process appliance data (calculate monthly usage, normalize names)
  appliances = appliances.map(app => {
    if (!app.monthly_kwh && app.power_kwh && app.hours_per_day && app.usage_per_week) {
      app.monthly_kwh = parseFloat(app.power_kwh) * parseFloat(app.hours_per_day) * parseFloat(app.usage_per_week) * 4.33;
    }
    if (!app.name && app.appliance_name) {
      app.name = app.appliance_name;
    }
    return app;
  });
  updateAllMetrics();
}

async function refreshAppliances() {
  try {
    const response = await fetch('appliances/get_appliances.php');
    const result = await response.json();

    if (result.success) {
      appliances = result.appliances;
      loadAppliances();
    } else {
      console.error('Failed to refresh appliances:', result.error);
    }
  } catch (error) {
    console.error('Error refreshing appliances:', error);
  }
}

async function addAppliance() {
  const name = document.getElementById('deviceName').value.trim();
  const power = parseFloat(document.getElementById('devicePower').value);
  const hours = parseFloat(document.getElementById('deviceHours').value);
  const usagePerWeek = parseFloat(document.getElementById('deviceUsagePerWeek').value);

  if (!name || !power || !hours || !usagePerWeek) {
    alert('Please fill in all fields');
    return;
  }

  const response = await fetch('appliances/save_appliance.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      name: name,
      power: power,
      hours: hours,
      usage_per_week: usagePerWeek,
      rate: currentRate
    })
  });

  const result = await response.json();

  if (result.success) {
    // Clear form and refresh appliances
    document.getElementById('deviceName').value = '';
    document.getElementById('devicePower').value = '';
    document.getElementById('deviceHours').value = '';
    document.getElementById('deviceUsagePerWeek').value = '';

    await refreshAppliances();
  } else {
    alert('Error: ' + (result.error || 'Failed to add appliance'));
  }
}

async function removeApplianceDB(applianceId) {
  if (confirm('Are you sure you want to remove this appliance?')) {
    const response = await fetch('appliances/remove_appliance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        appliance_id: applianceId,
        user_id: userId
      })
    });

    const result = await response.json();

    if (result.success) {
      await refreshAppliances();
    } else {
      alert('Error: ' + (result.error || 'Failed to remove appliance'));
    }
  }
}

function updateApplianceDisplay() {
  const container = document.getElementById('applianceDisplayList');
  if (!container) return;

  if (appliances.length === 0) {
    container.innerHTML = '<div class="text-center text-muted small py-3">No appliances tracked yet. Add one to get started!</div>';
    return;
  }

  // Sort appliances by ID descending (newest first)
  const sortedAppliances = [...appliances].sort((a, b) => (b.appliance_id || 0) - (a.appliance_id || 0));

  container.innerHTML = sortedAppliances.map(app => {
    const appName = app.name || app.appliance_name || 'Unknown';
    const monthlyKwh = parseFloat(app.monthly_kwh || 0);
    const cost = monthlyKwh * currentRate;
    const appId = app.appliance_id || app.id || 0;

    return `
      <div class="card mb-2">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-1">${appName}</h6>
              <small class="text-muted">${monthlyKwh.toFixed(2)} kWh/month</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="removeApplianceDB(${appId})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}