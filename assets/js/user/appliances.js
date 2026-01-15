// Appliance management functionality
let currentEditingApplianceId = null;
let editApplianceModalInstance = null;

// Validation functions using native HTML5 validation
function validateHoursPerDay(input) {
  const value = parseFloat(input.value);
  
  if (input.value === '') {
    input.setCustomValidity('');
    return true;
  }
  
  if (value > 24) {
    input.setCustomValidity('You cannot input hours that exceed to 24');
    input.reportValidity();
    return false;
  } else {
    input.setCustomValidity('');
    return true;
  }
}

function validateUsagePerWeek(input) {
  const value = parseFloat(input.value);
  
  if (input.value === '') {
    input.setCustomValidity('');
    return true;
  }
  
  if (value > 7) {
    input.setCustomValidity('You cannot input usage that exceed 7 per week');
    input.reportValidity();
    return false;
  } else {
    input.setCustomValidity('');
    return true;
  }
}

function validateEditHoursPerDay(input) {
  const value = parseFloat(input.value);
  
  if (input.value === '') {
    input.setCustomValidity('');
    return true;
  }
  
  if (value > 24) {
    input.setCustomValidity('You cannot input hours that exceed to 24');
    input.reportValidity();
    return false;
  } else {
    input.setCustomValidity('');
    return true;
  }
}

function validateEditUsagePerWeek(input) {
  const value = parseFloat(input.value);
  
  if (input.value === '') {
    input.setCustomValidity('');
    return true;
  }
  
  if (value > 7) {
    input.setCustomValidity('You cannot input usage that exceed 7 per week');
    input.reportValidity();
    return false;
  } else {
    input.setCustomValidity('');
    return true;
  }
}
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
  const nameInput = document.getElementById('deviceName');
  const powerInput = document.getElementById('devicePower');
  const hoursInput = document.getElementById('deviceHours');
  const usageInput = document.getElementById('deviceUsagePerWeek');

  const name = nameInput.value.trim();
  const power = parseFloat(powerInput.value);
  const hours = parseFloat(hoursInput.value);
  const usagePerWeek = parseFloat(usageInput.value);

  // Use native HTML5 validation
  if (!name) {
    nameInput.setCustomValidity('Please fill in all fields');
    nameInput.reportValidity();
    return;
  }
  nameInput.setCustomValidity('');

  if (!power || isNaN(power)) {
    powerInput.setCustomValidity('Please fill in all fields');
    powerInput.reportValidity();
    return;
  }
  powerInput.setCustomValidity('');

  if (!hours || isNaN(hours)) {
    hoursInput.setCustomValidity('Please fill in all fields');
    hoursInput.reportValidity();
    return;
  }
  hoursInput.setCustomValidity('');

  if (!usagePerWeek || isNaN(usagePerWeek)) {
    usageInput.setCustomValidity('Please fill in all fields');
    usageInput.reportValidity();
    return;
  }
  usageInput.setCustomValidity('');

  // Validate hours per day
  if (hours > 24) {
    const hoursInput = document.getElementById('deviceHours');
    validateHoursPerDay(hoursInput);
    hoursInput.focus();
    return;
  }

  // Validate usage per week
  if (usagePerWeek > 7) {
    const usageInput = document.getElementById('deviceUsagePerWeek');
    validateUsagePerWeek(usageInput);
    usageInput.focus();
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
    nameInput.value = '';
    powerInput.value = '';
    hoursInput.value = '';
    usageInput.value = '';
    
    // Clear validation messages
    nameInput.setCustomValidity('');
    powerInput.setCustomValidity('');
    hoursInput.setCustomValidity('');
    usageInput.setCustomValidity('');

    await refreshAppliances();
  } else {
    alert('Error: ' + (result.error || 'Failed to add appliance'));
  }
}

let currentDeletingApplianceId = null;
let deleteApplianceModalInstance = null;

function openDeleteApplianceModal(applianceId) {
  currentDeletingApplianceId = Number(applianceId);
  const modalEl = document.getElementById('deleteApplianceModal');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  if (!deleteApplianceModalInstance) {
    deleteApplianceModalInstance = new bootstrap.Modal(modalEl);
  }

  deleteApplianceModalInstance.show();
}

async function confirmDeleteAppliance() {
  if (!currentDeletingApplianceId) return;

  try {
    const response = await fetch('appliances/remove_appliance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        appliance_id: currentDeletingApplianceId,
        user_id: userId
      })
    });

    const result = await response.json();

    if (result.success) {
      if (deleteApplianceModalInstance) {
        deleteApplianceModalInstance.hide();
      }
      currentDeletingApplianceId = null;
      await refreshAppliances();
    } else {
      alert('Error: ' + (result.error || 'Failed to remove appliance'));
    }
  } catch (error) {
    console.error('Error removing appliance:', error);
    alert('An error occurred. Please try again.');
  }
}

function updateApplianceDisplay() {
  const container = document.getElementById('applianceDisplayList');
  const countBadge = document.getElementById('activeApplianceCount');
  if (!container) return;

  if (countBadge) {
    countBadge.textContent = appliances.length || 0;
    countBadge.className = 'badge ' + (appliances.length ? 'bg-primary' : 'bg-secondary');
    countBadge.style.fontSize = '0.65rem';
    countBadge.style.padding = '0.2rem 0.4rem';
  }

  if (appliances.length === 0) {
    container.innerHTML = '<div class="text-center text-muted small py-3">No appliances tracked yet. Add one to get started!</div>';
    return;
  }

  // Sort appliances by ID descending (newest first)
  const sortedAppliances = [...appliances].sort((a, b) => (b.appliance_id || 0) - (a.appliance_id || 0));

  const listHtml = sortedAppliances.map(app => {
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
              <small class="text-muted">${monthlyKwh.toFixed(2)} kWh/month • ₱${cost.toFixed(2)}/mo</small>
            </div>
            <div class="d-flex align-items-center">
              <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="openEditApplianceModal(${appId})" title="Edit appliance">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="openDeleteApplianceModal(${appId})" title="Remove appliance">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');

  container.innerHTML = listHtml;
}

function openEditApplianceModal(applianceId) {
  const numericId = Number(applianceId);
  const app = appliances.find(a => Number(a.appliance_id || a.id || 0) === numericId);
  if (!app) return;

  currentEditingApplianceId = numericId;

  const nameInput = document.getElementById('editDeviceName');
  const powerInput = document.getElementById('editDevicePower');
  const hoursInput = document.getElementById('editDeviceHours');
  const usageInput = document.getElementById('editDeviceUsagePerWeek');

  if (!nameInput || !powerInput || !hoursInput || !usageInput) return;

  const powerWatts = parseFloat(app.power_kwh || 0) * 1000;

  nameInput.value = app.name || app.appliance_name || '';
  powerInput.value = powerWatts ? powerWatts.toFixed(0) : '';
  hoursInput.value = app.hours_per_day || '';
  usageInput.value = app.usage_per_week || '';

  // Clear any previous validation messages
  nameInput.setCustomValidity('');
  powerInput.setCustomValidity('');
  hoursInput.setCustomValidity('');
  usageInput.setCustomValidity('');

  const modalEl = document.getElementById('editApplianceModal');
  if (!modalEl) return;

  if (!editApplianceModalInstance && typeof bootstrap !== 'undefined') {
    editApplianceModalInstance = new bootstrap.Modal(modalEl);
  }

  if (editApplianceModalInstance) {
    editApplianceModalInstance.show();
  }
}

async function saveEditedAppliance() {
  if (!currentEditingApplianceId) return;

  const nameInput = document.getElementById('editDeviceName');
  const powerInput = document.getElementById('editDevicePower');
  const hoursInput = document.getElementById('editDeviceHours');
  const usageInput = document.getElementById('editDeviceUsagePerWeek');

  const name = nameInput.value.trim();
  const powerWatts = parseFloat(powerInput.value);
  const hours = parseFloat(hoursInput.value);
  const usagePerWeek = parseFloat(usageInput.value);

  // Use native HTML5 validation
  if (!name) {
    nameInput.setCustomValidity('Please fill in all fields');
    nameInput.reportValidity();
    return;
  }
  nameInput.setCustomValidity('');

  if (!powerWatts || isNaN(powerWatts)) {
    powerInput.setCustomValidity('Please fill in all fields');
    powerInput.reportValidity();
    return;
  }
  powerInput.setCustomValidity('');

  if (!hours || isNaN(hours)) {
    hoursInput.setCustomValidity('Please fill in all fields');
    hoursInput.reportValidity();
    return;
  }
  hoursInput.setCustomValidity('');

  if (!usagePerWeek || isNaN(usagePerWeek)) {
    usageInput.setCustomValidity('Please fill in all fields');
    usageInput.reportValidity();
    return;
  }
  usageInput.setCustomValidity('');

  // Validate hours per day
  if (hours > 24) {
    const hoursInput = document.getElementById('editDeviceHours');
    validateEditHoursPerDay(hoursInput);
    hoursInput.focus();
    return;
  }

  // Validate usage per week
  if (usagePerWeek > 7) {
    const usageInput = document.getElementById('editDeviceUsagePerWeek');
    validateEditUsagePerWeek(usageInput);
    usageInput.focus();
    return;
  }

  try {
    const response = await fetch('appliances/update_appliance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        appliance_id: currentEditingApplianceId,
        name: name,
        power: powerWatts,
        hours: hours,
        usage_per_week: usagePerWeek,
        rate: currentRate
      })
    });

    const result = await response.json();

    if (result.success) {
      if (editApplianceModalInstance) {
        editApplianceModalInstance.hide();
      }
      currentEditingApplianceId = null;
      
      // Clear validation messages
      nameInput.setCustomValidity('');
      powerInput.setCustomValidity('');
      hoursInput.setCustomValidity('');
      usageInput.setCustomValidity('');
      
      await refreshAppliances();
    } else {
      alert('Error: ' + (result.error || 'Failed to update appliance'));
    }
  } catch (error) {
    console.error('Error updating appliance:', error);
    alert('An error occurred. Please try again.');
  }
}