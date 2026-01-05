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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Electripid - Energy Monitoring Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body class="dashboard-page">
  
  <!-- Glassmorphism Navbar -->
<nav class="glass-navbar navbar navbar-expand-lg">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <!-- Logo Section -->
        <div class="logo-container d-flex align-items-center">
            <div class="logo-icon rounded-3 d-flex align-items-center justify-content-center me-3">
                <i class="bi bi-lightning-charge"></i>
            </div>
            <div class="logo-text fs-4 fw-bold">Electri<span class="accent">pid</span></div>
        </div>

        <!-- Right Section: User & Actions -->
        <div class="header-actions d-flex align-items-center">
            <!-- Notification with Badge -->
            <div class="position-relative">
                <button class="icon-btn d-flex align-items-center justify-content-center">
                    <i class="bi bi-bell"></i>
                </button>
                <span class="notification-badge">3</span>
            </div>
            
            <!-- User Avatar with Dropdown -->
            <div class="user-avatar position-relative">
                <?php echo $userInitial; ?>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <div class="user-name"><?php echo $username; ?></div>
                        <div class="user-email"><?php echo $email; ?></div>
                    </div>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-shield me-2"></i>Security
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item logout-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Standalone Logout Button -->
            <a href="logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

  <div class="main-content container-fluid px-4 py-5" style="max-width: 1200px; margin: 0 auto; padding-top: 40px;">

    <!-- Updated Weather Widget -->
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

      <!-- Forecast Container -->
      <div class="weather-forecast d-flex gap-3" id="weatherForecast">
        <!-- JS will inject forecast here -->
      </div>
    </div>

    <!-- Rest of your content remains exactly the same -->
    <div class="card bg-white p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-bar-chart-line"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Electricity Rate & Provider</div>
          <div class="section-subtitle small text-secondary">Customize your local rate per kWh and select your provider</div>
        </div>
      </div>

      <div class="mt-4">
        <label class="small text-secondary mb-2 d-block">Location</label>
        <select id="locationSelect" class="form-select">
          <option value="">Select Location</option>
          <option value="Batangas City" selected>Batangas City</option>
          <option value="Lipa">Lipa</option>
          <option value="Tanauan">Tanauan</option>
          <option value="Sto Tomas">Sto Tomas</option>
        </select>
      </div>

      <div class="mt-3">
        <label class="small text-secondary mb-2 d-block">Electricity Provider</label>
        <select id="providerSelect" class="form-select">
          <option value="Meralco" selected>Meralco</option>
          <option value="BATELEC I">BATELEC I</option>
          <option value="BATELEC II">BATELEC II</option>
        </select>
      </div>

      <div class="rate-display d-flex justify-content-between align-items-center mt-4 p-4">
        <div class="rate-input flex-fill me-4">
          <label class="small text-secondary mb-2 d-block">Rate (₱ per kWh)</label>
          <input type="number" id="rateInput" class="form-control" 
                 value="12.00" step="0.5">
        </div>
        <div class="current-rate text-end">
          <div class="current-rate-label small text-secondary text-uppercase mb-1">Current Rate</div>
          <div class="current-rate-value fw-bold" id="currentRateDisplay">
            ₱12.00
          </div>
        </div>
      </div>
    </div>

    <div class="monthly-summary bg-white p-4 mb-4">
      <div class="summary-header d-flex justify-content-between align-items-center mb-4">
        <h6 class="mb-0 fw-semibold">Monthly Summary</h6>
        <div class="summary-stats d-flex gap-4 small">
          <div class="summary-stat text-center">
            <div class="summary-label text-secondary mb-1">Avg</div>
            <div class="summary-value fw-semibold" id="avgKwh">
              0.00 kWh
            </div>
          </div>
          <div class="summary-stat text-center">
            <div class="summary-label text-secondary mb-1">Peak</div>
            <div class="summary-value fw-semibold" id="peakKwh">
              0.00 kWh
            </div>
          </div>
          <div class="summary-stat text-center">
            <div class="summary-label text-secondary mb-1">Total</div>
            <div class="summary-value fw-semibold" id="totalKwhSummary">
              0.00 kWh
            </div>
          </div>
        </div>
      </div>
      <div class="small text-secondary">
        Month: <span id="currentMonth">Jan 01 - Jan 31</span>
      </div>
      <div class="alert-warning d-flex align-items-start gap-3 mt-4 p-3" id="weatherAlert" style="display: none;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div class="alert-content flex-fill">
          <div class="alert-title fw-semibold mb-1">Weather data unavailable</div>
          <div class="alert-text small">Using historical usage data for forecast. Weather integration will resume once data is
            available.</div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="stat-card bg-white p-4 position-relative">
          <div class="stat-label small text-secondary mb-2">Monthly Budget</div>
          <div class="stat-value dark fw-bold">
            ₱<span id="monthlyBudget">5000</span>
          </div>
          <div class="stat-icon position-absolute rounded-circle d-flex align-items-center justify-content-center" style="right: 24px; top: 50%; transform: translateY(-50%);">💰</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card bg-white p-4 position-relative">
          <div class="stat-label small text-secondary mb-2">Electricity Provider</div>
          <div class="stat-value blue fw-bold" id="providerDisplay">
            Meralco
          </div>
          <div class="stat-icon position-absolute rounded-circle d-flex align-items-center justify-content-center" style="right: 24px; top: 50%; transform: translateY(-50%);">🏢</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card bg-white p-4 position-relative">
          <div class="stat-label small text-secondary mb-2">Active Appliances</div>
          <div class="stat-value dark fw-bold" id="activeAppliances">
            0
          </div>
          <div class="stat-icon position-absolute rounded-circle d-flex align-items-center justify-content-center" style="right: 24px; top: 50%; transform: translateY(-50%);">⚡</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card bg-white p-4 position-relative">
          <div class="stat-label small text-secondary mb-2">This Month (kWh)</div>
          <div class="stat-value fw-bold" id="thisMonthKwh">
            0.0
          </div>
          <div class="stat-icon position-absolute rounded-circle d-flex align-items-center justify-content-center" style="right: 24px; top: 50%; transform: translateY(-50%);">📊</div>
        </div>
      </div>
    </div>

    <div class="energy-overview p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon rounded-circle d-flex align-items-center justify-content-center text-white">
          <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Energy Overview</div>
          <div class="section-subtitle small text-secondary">Real-time consumption & costs</div>
        </div>
      </div>

      <div class="overview-metric mb-4">
        <div class="metric-label small text-secondary mb-2">Daily Consumption</div>
        <div>
          <span class="metric-value fw-bold" id="dailyConsumption">
            0.00
          </span>
          <span class="metric-unit small text-secondary ms-1">kWh per 24 hours</span>
        </div>
      </div>

      <div class="overview-metric">
        <div class="metric-label small text-secondary mb-2">Monthly Cost</div>
        <div>
          <span class="metric-value fw-bold">
            ₱<span id="monthlyCost">0</span>
          </span>
        </div>
        <div class="metric-subtext small text-secondary mt-1">
          Yearly: ₱<span id="yearlyCost">0</span>
        </div>
      </div>
    </div>

    <div class="card bg-white p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-plus-circle"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Add New Appliance</div>
          <div class="section-subtitle small text-secondary">Track energy consumption for any device</div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-lg-6">
          <input type="text" id="deviceName" class="form-control" placeholder="e.g., Air Conditioner">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
          <input type="number" id="devicePower" class="form-control" placeholder="Power (W)">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
          <input type="number" id="deviceHours" class="form-control" placeholder="Hours/Day">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
          <input type="number" id="deviceUsagePerWeek" class="form-control" placeholder="Usage/Week">
        </div>
      </div>

      <div class="small text-secondary mb-3">
        <div class="row g-3">
          <div class="col-12 col-md-6 col-lg-6">Device Name</div>
          <div class="col-12 col-md-6 col-lg-2">Power (Watts)</div>
          <div class="col-12 col-md-6 col-lg-2">Hours per Day</div>
          <div class="col-12 col-md-6 col-lg-2">Usage per Week</div>
        </div>
      </div>

      <button class="btn-add btn text-white w-100 d-flex align-items-center justify-content-center gap-2" onclick="addAppliance()">
        <i class="bi bi-plus-lg"></i> Add Appliance
      </button>

      <div id="applianceList" class="mt-4">
        <div class="empty-state text-center py-5 text-secondary small">
          No appliances tracked yet. Add one to get started!
        </div>
      </div>
    </div>

    <div class="card bg-white p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-plug"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Your Appliances</div>
          <div class="section-subtitle small text-secondary" id="applianceCount">
            0 devices in your home
          </div>
        </div>
      </div>

      <div id="applianceDisplayList">
        <div class="empty-state text-center py-5 text-secondary small">
          No appliances tracked yet. Add one to get started!
        </div>
      </div>
    </div>

    <div class="card card-gradient p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-fire"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Top Consumers</div>
          <div class="section-subtitle small text-secondary">Highest energy-drawing appliances</div>
        </div>
      </div>

      <div id="topConsumers">
        <div class="empty-state text-center py-5 text-secondary small">
          No appliances tracked yet
        </div>
      </div>
    </div>

    <div class="card bg-white p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div class="flex-fill">
          <div class="section-title fw-semibold fs-5 mb-1">Monthly Energy Forecast</div>
          <div class="section-subtitle small text-secondary">AI predictions by week factoring in temperature, historical usage, and occupancy
            status</div>
        </div>
        <span class="small text-warning d-flex align-items-center gap-1">
          <i class="bi bi-exclamation-circle"></i> Using historical data
        </span>
      </div>

      <canvas id="forecastChart" style="max-height: 300px;"></canvas>
    </div>

    <div class="card card-gradient p-4 mb-4">
      <div class="section-header d-flex align-items-center gap-3 mb-4">
        <div class="section-icon light rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-lightbulb"></i>
        </div>
        <div>
          <div class="section-title fw-semibold fs-5 mb-1">Energy Tips & Recommendations</div>
        </div>
      </div>

      <ul class="list-unstyled mb-0" id="tipsList">
        <li class="tip-item d-flex align-items-start gap-3 p-3 mb-2 bg-white">
          <div class="tip-icon fs-5">💡</div>
          <div class="small">Use LED lights to reduce consumption by 75%</div>
        </li>
        <li class="tip-item d-flex align-items-start gap-3 p-3 mb-2 bg-white">
          <div class="tip-icon fs-5">⚡</div>
          <div class="small">Unplug devices to avoid standby power drain</div>
        </li>
        <li class="tip-item d-flex align-items-start gap-3 p-3 mb-2 bg-white">
          <div class="tip-icon fs-5">❄️</div>
          <div class="small">Adjust thermostat to save up to 10% monthly</div>
        </li>
      </ul>
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

  <button class="chat-fab position-fixed rounded-circle border-0 d-flex align-items-center justify-content-center text-white" style="bottom: 30px; right: 30px; z-index: 999;" onclick="openChatbot()">
    <i class="bi bi-chat-dots-fill"></i>
  </button>

  <button class="donation-fab position-fixed rounded-circle border-0 d-flex align-items-center justify-content-center text-white" style="bottom: 100px; right: 30px; z-index: 999;" onclick="openDonationModal()">
    <i class="bi bi-heart-fill"></i>
  </button>

  <script>
    let appliances = [];
    let currentRate = 12.00;
    let forecastChart = null;
    const WEATHER_API_KEY = 'a4ad5de980d109abed0fec591eefd391'; // Updated API key
    let currentLocation = 'Batangas';
    let userId = 1;

    const providerByLocation = {
      "Batangas City": { provider: "Meralco", rate: 11.5 },
      "Lipa": { provider: "Meralco", rate: 11.8 },
      "Tanauan": { provider: "BATELEC I", rate: 12.1 },
      "Sto Tomas": { provider: "Meralco", rate: 13.2 }
    };

    document.addEventListener('DOMContentLoaded', function() {
      updateRateDisplay();
      updateAllMetrics();
      initForecastChart();
      fetchWeather(); // Use the new weather function
      
      document.getElementById('rateInput').addEventListener('input', function() {
        currentRate = parseFloat(this.value) || 12;
        updateRateDisplay();
        updateAllMetrics();
        saveSettings();
      });

      document.getElementById('providerSelect').addEventListener('change', function() {
        document.getElementById('providerDisplay').textContent = this.value;
        saveSettings();
      });

      document.getElementById("locationSelect").addEventListener("change", function () {
        const data = providerByLocation[this.value];
        if (!data) return;

        currentLocation = this.value;
        currentRate = data.rate;
        document.getElementById("providerSelect").value = data.provider;
        document.getElementById("providerDisplay").textContent = data.provider;
        document.getElementById("rateInput").value = data.rate;

        updateRateDisplay();
        updateAllMetrics();
        fetchWeather(); // Use the new weather function
        saveSettings();
      });
    });

    // New weather functions from your updated code
    async function fetchWeather() {
      try {
        const res = await fetch(`https://api.openweathermap.org/data/2.5/forecast?q=${currentLocation}&appid=${WEATHER_API_KEY}&units=metric`);
        const data = await res.json();
        if (data.cod === '200') {
          updateCurrentWeather(data);
          updateWeatherForecast(data);
          document.getElementById('weatherAlert').style.display = 'none';
        } else {
          throw new Error('Weather data unavailable');
        }
      } catch (error) {
        console.error('Error fetching weather:', error);
        document.getElementById('weatherAlert').style.display = 'flex';
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
      const location = document.getElementById('locationSelect').value;
      const provider = document.getElementById('providerSelect').value;
      const rate = parseFloat(document.getElementById('rateInput').value);
      const budget = parseFloat(document.getElementById('monthlyBudget').innerText.replace('₱', '').replace(',', ''));
      
      try {
        const response = await fetch('save_settings.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ 
            location: location, 
            provider: provider, 
            rate_per_kwh: rate,
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

    function updateRateDisplay() {
      document.getElementById('currentRateDisplay').textContent = `₱${currentRate.toFixed(2)}`;
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
      const totalKwh = appliances.reduce((sum, app) => sum + parseFloat(app.monthly_kwh), 0);
      const totalCost = totalKwh * currentRate;
      const dailyKwh = totalKwh / 30;
      const yearlyCost = totalCost * 12;

      document.getElementById('activeAppliances').textContent = appliances.length;
      document.getElementById('thisMonthKwh').textContent = totalKwh.toFixed(1);
      document.getElementById('dailyConsumption').textContent = dailyKwh.toFixed(2);
      document.getElementById('monthlyCost').textContent = Math.round(totalCost);
      document.getElementById('yearlyCost').textContent = Math.round(yearlyCost);

      const avgKwh = appliances.length > 0 ? totalKwh / appliances.length : 0;
      const peakKwh = appliances.length > 0 ? Math.max(...appliances.map(a => parseFloat(a.monthly_kwh))) : 0;
      
      document.getElementById('avgKwh').textContent = avgKwh.toFixed(2) + ' kWh';
      document.getElementById('peakKwh').textContent = peakKwh.toFixed(2) + ' kWh';
      document.getElementById('totalKwhSummary').textContent = totalKwh.toFixed(2) + ' kWh';

      updateForecastChart(totalKwh);
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
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>