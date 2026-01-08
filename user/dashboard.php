<?php
session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Define user variables
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$userInitial = isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'U';
$email = htmlspecialchars($_SESSION['email'] ?? 'user@example.com');
$userId = $_SESSION['user_id'];

// Load appliances from database
$appliances = [];
$user_id = mysqli_real_escape_string($conn, $userId);
$household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id'";
$household_result = executeQuery($household_query);

if ($household_result && mysqli_num_rows($household_result) > 0) {
    $household_row = mysqli_fetch_assoc($household_result);
    $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);
    
    $appliance_query = "SELECT * FROM APPLIANCE WHERE household_id = '$household_id'";
    $appliance_result = executeQuery($appliance_query);
    
    if ($appliance_result) {
        while ($row = mysqli_fetch_assoc($appliance_result)) {
            $appliances[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Electripid - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body>
  <!-- Navbar -->
  <div class="container-fluid px-5 mt-4">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
      <div class="w-100 d-flex justify-content-between align-items-center">
        <a class="navbar-brand ms-3" href="#">
          <i class="bi bi-lightning-charge-fill me-2"></i>Electripid
        </a>
        <div class="d-flex align-items-center">
          <!-- Notifications -->
          <button class="nav-icon-btn position-relative" type="button">
            <i class="bi bi-bell"></i>
            <span class="notification-badge">3</span>
          </button>
          <!-- User Profile -->
          <div class="dropdown ms-2">
            <button class="btn p-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown">
              <div class="user-avatar me-2"><?php echo $userInitial; ?></div>
              <i class="bi bi-chevron-down"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="#">
                  <i class="bi bi-person"></i> My Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#">
                  <i class="bi bi-gear"></i> Settings
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#">
                  <i class="bi bi-question-circle"></i> Help & Support
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  <i class="bi bi-box-arrow-right"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="container px-5 py-4">
    <!-- Info Cards -->
    <div class="row g-4 mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-lightning-charge"></i>
          </div>
          <h6 class="text-muted mb-1">Electricity Provider</h6>
          <h4 class="mb-0" id="providerDisplay">Meralco</h4>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-wallet2"></i>
          </div>
          <h6 class="text-muted mb-1">Monthly Budget</h6>
          <h4 class="mb-0">₱<span id="monthlyBudget">5,000</span></h4>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-plug"></i>
          </div>
          <h6 class="text-muted mb-1">Active Appliances</h6>
          <h4 class="mb-0"><span id="activeAppliances">0</span> Devices</h4>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-graph-up"></i>
          </div>
          <h6 class="text-muted mb-1">Real-time Consumption</h6>
          <h4 class="mb-0"><span id="thisMonthKwh">0.0</span> kWh</h4>
        </div>
      </div>
    </div>

    <!-- Weather Widget -->
    <div class="weather-widget bg-white p-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div class="weather-current d-flex align-items-center gap-4 mb-3 mb-md-0">
        <div class="weather-icon" id="weatherIcon">
          <img src="" alt="Weather Icon">
        </div>
        <div>
          <div class="weather-location small text-secondary text-uppercase" id="weatherLocation">BATANGAS CITY</div>
          <div class="weather-temp fw-bold" id="weatherTemp">--°C</div>
          <div class="weather-info d-flex gap-4 small text-secondary">
            <span id="weatherCondition">--</span>
            <span>💧 <span id="weatherHumidity">--</span>%</span>
            <span>💨 <span id="weatherWind">--</span> km/h</span>
          </div>
        </div>
      </div>
      <div class="weather-forecast d-flex gap-3" id="weatherForecast">
        <!-- JS will inject forecast here -->
      </div>
    </div>

    <!-- Main Content Areas -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="chart-container h-100 d-flex flex-column">
          <h5 class="mb-3"><i class="bi bi-list-check me-2"></i>Your Appliances & Add New Appliances</h5>
          <p class="text-muted">Manage your registered appliances and add new ones to track</p>
          
          <!-- Add Appliance Form -->
          <div class="mb-3">
            <div class="row g-2 mb-2">
              <div class="col-12 col-md-6">
                <input type="text" id="deviceName" class="form-control form-control-sm" placeholder="Device Name">
              </div>
              <div class="col-6 col-md-3">
                <input type="number" id="devicePower" class="form-control form-control-sm" placeholder="Power (W)">
              </div>
              <div class="col-6 col-md-3">
                <input type="number" id="deviceHours" class="form-control form-control-sm" placeholder="Hours/Day">
              </div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-12 col-md-6">
                <input type="number" id="deviceUsagePerWeek" class="form-control form-control-sm" placeholder="Usage/Week">
              </div>
              <div class="col-12 col-md-6">
                <button class="btn btn-primary btn-sm w-100" onclick="addAppliance()">
                  <i class="bi bi-plus-circle me-2"></i>Add Appliance
                </button>
              </div>
            </div>
          </div>
          
          <!-- Appliance List -->
          <div id="applianceDisplayList" class="flex-grow-1">
            <div class="text-center text-muted small py-3">
              No appliances tracked yet. Add one to get started!
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="chart-container h-100 d-flex flex-column">
          <h5 class="mb-3"><i class="bi bi-bar-chart me-2"></i>Energy Overview</h5>
          <p class="text-muted">Visual representation of your energy consumption patterns</p>
          <div class="mt-4 flex-grow-1">
            <div class="mb-3">
              <div class="small text-secondary mb-1">Daily Consumption</div>
              <div class="h4 mb-0"><span id="dailyConsumption">0.00</span> <small class="text-muted">kWh</small></div>
            </div>
            <div class="mb-3">
              <div class="small text-secondary mb-1">Monthly Cost</div>
              <div class="h4 mb-0">₱<span id="monthlyCost">0</span></div>
              <div class="small text-muted">Yearly: ₱<span id="yearlyCost">0</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Additional Sections -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="chart-container h-100 d-flex flex-column">
          <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Monthly Energy Forecast</h5>
          <p class="text-muted">Predicted energy usage based on your consumption patterns</p>
          <div class="mt-4 flex-grow-1">
            <canvas id="forecastChart" style="max-height: 300px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-container h-100 d-flex flex-column">
          <h5 class="mb-3"><i class="bi bi-lightbulb me-2"></i>Energy Tips & Recommendations</h5>
          <div class="mt-3 flex-grow-1">
            <div class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Tip:</strong> Use LED bulbs to save up to 75% on lighting costs
            </div>
            <div class="alert alert-success mb-3">
              <i class="bi bi-check-circle me-2"></i>
              <strong>Great job!</strong> You're managing your energy efficiently
            </div>
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Notice:</strong> Unplug devices when not in use to reduce standby power
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal and chatbot code remains exactly the same -->
  <div id="donationModal" class="modal-overlay position-fixed top-0 start-0 end-0 bottom-0 align-items-center justify-content-center" style="display: none; z-index: 1001;">
    <div class="modal-content bg-white rounded-4" style="width: 90%; max-width: 500px;">
      <div class="modal-header d-flex justify-content-between align-items-center p-4 border-bottom">
        <h3 class="mb-0">💚 Support Electripid</h3>
        <button class="modal-close border-0 bg-transparent rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="closeDonationModal()">&times;</button>
      </div>
      <div class="modal-body p-4">
        <p style="color: #64748b; margin-bottom: 20px;">Help us improve Electripid! Your donation will fund new
          features, better forecasting, and enhanced user experience.</p>

        <div class="donation-amounts row g-2 mb-4">
          <div class="col-6 col-md-3">
            <button class="donation-btn w-100 p-3 fw-semibold" onclick="selectAmount(50)">₱50</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="donation-btn w-100 p-3 fw-semibold" onclick="selectAmount(100)">₱100</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="donation-btn w-100 p-3 fw-semibold" onclick="selectAmount(250)">₱250</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="donation-btn w-100 p-3 fw-semibold" onclick="selectAmount(500)">₱500</button>
          </div>
        </div>

        <div class="mb-4">
          <label class="small text-secondary mb-2 d-block">Custom Amount (₱)</label>
          <input type="number" id="customAmount" class="form-control" placeholder="Enter custom amount" min="10">
        </div>

        <form action="https://www.paypal.com/donate" method="post" target="_blank" id="donateForm">
          <input type="hidden" name="business" value="YOUR_PAYPAL_EMAIL@example.com">
          <input type="hidden" name="item_name" value="Electripid Enhancement Fund">
          <input type="hidden" name="currency_code" value="PHP">
          <input type="hidden" name="amount" id="donationAmount" value="100">
          <button type="submit" class="btn-add btn text-white w-100 d-flex align-items-center justify-content-center gap-2 mt-3">
            <i class="bi bi-heart-fill"></i> Donate via PayPal
          </button>
        </form>

        <p class="small text-secondary text-center mt-4 mb-0">
          🔒 Secure payment via PayPal • Your support means everything!
        </p>
      </div>
    </div>
  </div>

  <div id="chatbotModal" class="modal-overlay position-fixed top-0 start-0 end-0 bottom-0 align-items-center justify-content-center" style="display: none; z-index: 1001;">
    <div class="chatbot-container bg-white rounded-4 d-flex flex-column" style="width: 90%; max-width: 450px; height: 600px;">
      <div class="chatbot-header d-flex justify-content-between align-items-center p-4 rounded-top">
        <div>
          <h3 class="mb-0 text-white">⚡ Electripid Assistant</h3>
          <p class="mb-0 small text-white-50">AI-powered energy advisor</p>
        </div>
        <button class="modal-close border-0 bg-transparent rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px;" onclick="closeChatbot()">&times;</button>
      </div>
      <div class="chatbot-messages flex-fill p-4 overflow-auto bg-light" id="chatbotMessages">
        <div class="bot-message d-flex gap-3 mb-4">
          <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0;">🤖</div>
          <div class="message-content bg-white p-3 rounded small">
            Hello! I'm your Electripid assistant. I can help you with:
            <br>• Energy consumption analysis
            <br>• Money-saving tips
            <br>• Appliance recommendations
            <br>• Bill estimates
            <br><br>How can I help you today?
          </div>
        </div>
      </div>
      <div class="chatbot-input d-flex gap-3 p-4 bg-white border-top rounded-bottom">
        <input type="text" id="chatInput" class="form-control flex-fill" placeholder="Ask me anything about your energy usage..."
          onkeypress="handleChatKeypress(event)">
        <button class="btn-send border-0 text-white rounded d-flex align-items-center justify-content-center" onclick="sendMessage()">
          <i class="bi bi-send-fill"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Floating Action Buttons -->
  <button class="floating-btn donation-btn" onclick="openDonationModal()">
    <i class="bi bi-heart-fill"></i>
  </button>

  <button class="floating-btn chatbot-btn" onclick="openChatbot()">
    <i class="bi bi-chat-dots-fill"></i>
  </button>

  <script>
    let appliances = <?php echo json_encode($appliances); ?>;
    let currentRate = 12.00;
    let forecastChart = null;
    const WEATHER_API_KEY = 'a4ad5de980d109abed0fec591eefd391';
    let currentLocation = 'Batangas';
    let userId = <?php echo $userId; ?>;

    const providerByLocation = {
      "Batangas City": { provider: "Meralco", rate: 11.5 },
      "Lipa": { provider: "Meralco", rate: 11.8 },
      "Tanauan": { provider: "BATELEC I", rate: 12.1 },
      "Sto Tomas": { provider: "Meralco", rate: 13.2 }
    };

    document.addEventListener('DOMContentLoaded', function() {
      updateAllMetrics();
      initForecastChart();
      fetchWeather();
      loadAppliances();
    });

    // New weather functions from your updated code
    async function fetchWeather() {
      try {
        const res = await fetch(`https://api.openweathermap.org/data/2.5/forecast?q=${currentLocation}&appid=${WEATHER_API_KEY}&units=metric`);
        const data = await res.json();
        if (data.cod === '200') {
          updateCurrentWeather(data);
          updateWeatherForecast(data);
        } else {
          throw new Error('Weather data unavailable');
        }
      } catch (error) {
        console.error('Error fetching weather:', error);
      }
    }

    function updateCurrentWeather(data) {
      const current = data.list[0]; // first item = current weather
      document.getElementById('weatherTemp').textContent = Math.round(current.main.temp) + '°C';
      document.getElementById('weatherCondition').textContent = current.weather[0].description;
      document.getElementById('weatherHumidity').textContent = current.main.humidity;
      document.getElementById('weatherWind').textContent = current.wind.speed;

      const iconCode = current.weather[0].icon;
      document.querySelector('#weatherIcon img').src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
    }

    function updateWeatherForecast(data) {
      const forecastContainer = document.getElementById('weatherForecast');
      forecastContainer.innerHTML = '';

      // OpenWeatherMap returns 3-hour intervals. We'll pick 12:00 for daily forecast
      const forecastByDay = {};
      data.list.forEach(item => {
        const date = new Date(item.dt_txt);
        const day = date.toLocaleDateString('en-US', { weekday: 'short' });
        const hour = date.getHours();
        if(hour === 12 && !forecastByDay[day]){
          forecastByDay[day] = item;
        }
      });

      Object.keys(forecastByDay).forEach(day => {
        const item = forecastByDay[day];
        const iconCode = item.weather[0].icon;
        const temp = Math.round(item.main.temp);

        const dayDiv = document.createElement('div');
        dayDiv.classList.add('forecast-day');
        dayDiv.innerHTML = `
          <div class="fw-bold">${day}</div>
          <img src="https://openweathermap.org/img/wn/${iconCode}@2x.png" alt="icon">
          <div>${temp}°C</div>
        `;
        forecastContainer.appendChild(dayDiv);
      });
    }

    async function saveSettings() {
      const budget = parseFloat(document.getElementById('monthlyBudget').innerText.replace('₱', '').replace(',', ''));
      
      try {
        const response = await fetch('save_settings.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ 
            monthly_budget: budget
          })
        });
        
        const result = await response.json();
        if(!result.success) {
          console.error('Error saving settings:', result.error);
        }
      } catch(error) {
        console.error('Error saving settings:', error);
      }
    }

    function openDonationModal() {
      const modal = document.getElementById('donationModal');
      modal.style.display = 'flex';
      modal.classList.add('d-flex');
    }

    function closeDonationModal() {
      const modal = document.getElementById('donationModal');
      modal.style.display = 'none';
      modal.classList.remove('d-flex');
    }

    function selectAmount(amount) {
      document.getElementById('customAmount').value = '';
      document.getElementById('donationAmount').value = amount;
      
      document.querySelectorAll('.donation-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      event.target.classList.add('active');
    }

    document.getElementById('customAmount')?.addEventListener('input', function() {
      if (this.value) {
        document.getElementById('donationAmount').value = this.value;
        document.querySelectorAll('.donation-btn').forEach(btn => {
          btn.classList.remove('active');
        });
      }
    });

    function openChatbot() {
      const modal = document.getElementById('chatbotModal');
      modal.style.display = 'flex';
      modal.classList.add('d-flex');
    }

    function closeChatbot() {
      const modal = document.getElementById('chatbotModal');
      modal.style.display = 'none';
      modal.classList.remove('d-flex');
    }

    function handleChatKeypress(event) {
      if (event.key === 'Enter') {
        sendMessage();
      }
    }

    async function sendMessage() {
      const input = document.getElementById('chatInput');
      const message = input.value.trim();
      
      if (!message) return;
      
      addMessageToChat(message, 'user');
      input.value = '';
      
      showTypingIndicator();
      
      try {
        removeTypingIndicator();
        
        const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh), 0);
        const totalCost = totalKwh * currentRate;
        
        const responses = [
          `Based on your current usage of ${totalKwh.toFixed(2)} kWh, you could save around 15% by turning off appliances when not in use.`,
          `Your monthly cost is ₱${totalCost.toFixed(2)}. Consider using energy-efficient appliances to reduce costs.`,
          `Based on your ${appliances.length} appliances, try to run high-consumption devices during off-peak hours to save money.`,
          `Consider unplugging devices that aren't in use to reduce standby power consumption. You could save up to 10% monthly.`
        ];
        
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addMessageToChat(randomResponse, 'bot');
        
      } catch (error) {
        console.error('Chatbot error:', error);
        removeTypingIndicator();
        addMessageToChat("I can help you with: checking your monthly bill estimate, suggesting energy-saving tips, or analyzing your appliance usage. What would you like to know?", 'bot');
      }
    }

    function addMessageToChat(message, sender) {
      const messagesContainer = document.getElementById('chatbotMessages');
      const messageDiv = document.createElement('div');
      messageDiv.className = sender === 'user' ? 'user-message' : 'bot-message';
      
      const avatar = sender === 'user' ? '👤' : '🤖';
      
      messageDiv.innerHTML = `
        <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0;">${avatar}</div>
        <div class="message-content bg-white p-3 rounded small">${message}</div>
      `;
      
      messagesContainer.appendChild(messageDiv);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function showTypingIndicator() {
      const messagesContainer = document.getElementById('chatbotMessages');
      const typingDiv = document.createElement('div');
      typingDiv.className = 'bot-message typing-indicator-container';
      typingDiv.id = 'typingIndicator';
      typingDiv.innerHTML = `
        <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0;">🤖</div>
        <div class="typing-indicator d-flex gap-1 p-3">
          <div class="typing-dot rounded-circle"></div>
          <div class="typing-dot rounded-circle"></div>
          <div class="typing-dot rounded-circle"></div>
        </div>
      `;
      messagesContainer.appendChild(typingDiv);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function removeTypingIndicator() {
      const indicator = document.getElementById('typingIndicator');
      if (indicator) {
        indicator.remove();
      }
    }

    async function loadAppliances() {
      // Appliances are loaded from PHP on page load
      // Calculate monthly_kwh for each appliance if not present
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

    async function addAppliance() {
      const name = document.getElementById('deviceName').value.trim();
      const power = parseFloat(document.getElementById('devicePower').value);
      const hours = parseFloat(document.getElementById('deviceHours').value);
      const usagePerWeek = parseFloat(document.getElementById('deviceUsagePerWeek').value);

      if (!name || !power || !hours || !usagePerWeek) {
        alert('Please fill in all fields');
        return;
      }

      try {
        const response = await fetch('save_appliance.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
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
        
        if(result.success) {
          document.getElementById('deviceName').value = '';
          document.getElementById('devicePower').value = '';
          document.getElementById('deviceHours').value = '';
          document.getElementById('deviceUsagePerWeek').value = '';
          
          // Reload appliances
          location.reload();
        } else {
          alert('Error adding appliance: ' + result.error);
        }
      } catch(error) {
        console.error('Error:', error);
        alert('Error adding appliance');
      }
    }

    async function removeApplianceDB(applianceId) {
      if(confirm('Are you sure you want to remove this appliance?')) {
        try {
          const response = await fetch('remove_appliance.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
              appliance_id: applianceId,
              user_id: userId 
            })
          });
          
          const result = await response.json();
          if(result.success) {
            location.reload();
          } else {
            alert('Error removing appliance');
          }
        } catch(error) {
          console.error('Error:', error);
          alert('Error removing appliance');
        }
      }
    }

    function updateAllMetrics() {
      const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh || 0), 0);
      const totalCost = totalKwh * currentRate;
      const dailyKwh = totalKwh / 30;
      const yearlyCost = totalCost * 12;

      const activeAppliancesEl = document.getElementById('activeAppliances');
      const thisMonthKwhEl = document.getElementById('thisMonthKwh');
      const dailyConsumptionEl = document.getElementById('dailyConsumption');
      const monthlyCostEl = document.getElementById('monthlyCost');
      const yearlyCostEl = document.getElementById('yearlyCost');

      if (activeAppliancesEl) activeAppliancesEl.textContent = appliances.length;
      if (thisMonthKwhEl) thisMonthKwhEl.textContent = totalKwh.toFixed(1);
      if (dailyConsumptionEl) dailyConsumptionEl.textContent = dailyKwh.toFixed(2);
      if (monthlyCostEl) monthlyCostEl.textContent = Math.round(totalCost);
      if (yearlyCostEl) yearlyCostEl.textContent = Math.round(yearlyCost);

      updateForecastChart(totalKwh);
      updateApplianceDisplay();
    }
  
    function initForecastChart() {
      const ctx = document.getElementById('forecastChart').getContext('2d');
      forecastChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
          datasets: [{
            label: 'kWh',
            data: [0, 0, 0, 0],
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
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: '#e3f2fd' }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }
  
    function updateForecastChart(totalKwh) {
      if (!forecastChart) return;
      
      const weeklyKwh = totalKwh / 4;
      const variation = weeklyKwh * 0.15;
      
      forecastChart.data.datasets[0].data = [
        weeklyKwh + (Math.random() * variation - variation/2),
        weeklyKwh + (Math.random() * variation - variation/2),
        weeklyKwh + (Math.random() * variation - variation/2),
        weeklyKwh + (Math.random() * variation - variation/2)
      ];
      
      forecastChart.update();
    }

    function updateApplianceDisplay() {
      const container = document.getElementById('applianceDisplayList');
      if (!container) return;

      if (appliances.length === 0) {
        container.innerHTML = '<div class="text-center text-muted small py-3">No appliances tracked yet. Add one to get started!</div>';
        return;
      }

      container.innerHTML = appliances.map(app => {
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
                  <small class="text-muted">${monthlyKwh.toFixed(2)} kWh/month • ₱${cost.toFixed(2)}</small>
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
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>
</html>
</body>
</html>
</html>
</html>