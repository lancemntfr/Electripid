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
$currentProvider = 'Meralco';
$currentRate = 13.01;
$user_id = mysqli_real_escape_string($conn, $userId);
$household_query = "SELECT h.household_id, p.provider_name FROM HOUSEHOLD h 
                    LEFT JOIN ELECTRICITY_PROVIDER p ON h.provider_id = p.provider_id 
                    WHERE h.user_id = '$user_id'";
$household_result = mysqli_query($conn, $household_query);

if ($household_result && mysqli_num_rows($household_result) > 0) {
  $household_row = mysqli_fetch_assoc($household_result);
  $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);
  
  // Get provider and set rate
  if (!empty($household_row['provider_name'])) {
    $currentProvider = $household_row['provider_name'];
    // Set rate based on provider
    if ($currentProvider == 'Meralco') {
      $currentRate = 13.01;
    } elseif ($currentProvider == 'BATELEC I' || $currentProvider == 'Batelec 1') {
      $currentRate = 10.08;
    } elseif ($currentProvider == 'BATELEC II' || $currentProvider == 'Batelec 2') {
      $currentRate = 9.90;
    }
  }

  $appliance_query = "SELECT * FROM APPLIANCE WHERE household_id = '$household_id'";
  $appliance_result = mysqli_query($conn, $appliance_query);

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

<body class="dashboard-page">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-radius: 0 !important;">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="#" style="color: #1E88E5 !important;">
        <i class="bi bi-lightning-charge-fill me-2" style="color: #00bfa5;"></i>Electripid
      </a>
      <div class="d-flex align-items-center">
        <!-- Notifications -->
        <button class="nav-icon-btn position-relative me-3" type="button" style="font-size: 2rem;">
          <i class="bi bi-bell"></i>
        </button>
        <!-- User Profile -->
        <div class="dropdown ms-2">
          <button class="btn p-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle" style="font-size: 2rem; color: var(--secondary-color);"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="#">
                <i class="bi bi-person"></i> My Profile
              </a>
            </li>
            <li>
                <a class="dropdown-item" href="settings.php">
                  <i class="bi bi-gear-fill"></i> Settings
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
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

  <!-- Main Content -->
  <div class="container px-5 py-4 mt-4">
    <!-- Info Cards -->
    <div class="row g-4 mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-lightning-charge"></i>
          </div>
          <h6 class="text-muted mb-1">Electricity Provider</h6>
          <h4 class="mb-0" id="providerDisplay"><?php echo htmlspecialchars($currentProvider); ?></h4>
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
                <input type="number" id="devicePower" class="form-control form-control-sm" placeholder="Power (kWh)">
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
          <div id="applianceDisplayList" class="flex-grow-1" style="max-height: 120px; overflow-y: auto;">
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
          <div id="energyTipsContent" class="mt-3 flex-grow-1" style="display: none;">
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

  <!-- Donation Modal -->
  <div id="donationModal" class="modal-overlay position-fixed top-0 start-0 end-0 bottom-0 align-items-center justify-content-center" style="display: none; z-index: 1001;">
    <div class="modal-content bg-white rounded-4" style="width: 90%; max-width: 500px;">
      <div class="modal-header d-flex justify-content-between align-items-center p-4 border-bottom">
        <h3 class="mb-0">💚 Support Electripid</h3>
        <button class="modal-close border-0 bg-transparent rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="closeDonationModal()">&times;</button>
      </div>
      <div class="modal-body p-4">
        <p style="color: #64748b; margin-bottom: 20px;">Help us improve Electripid! Your donation will fund new features, better forecasting, and enhanced user experience.</p>

        <div class="mb-4">
          <label class="small text-secondary mb-2 d-block">Custom Amount (₱)</label>
          <input type="number" id="customAmount" class="form-control" placeholder="Enter custom amount" min="10">
        </div>

        <div id="paypal-button-container"></div>

        <p class="small text-secondary text-center mt-4 mb-0">
          🔒 Secure payment via PayPal • Your support means everything!
        </p>
      </div>
    </div>
  </div>

  <!-- Chatbot Widget -->
  <div id="chatbotWidget" class="chatbot-widget" style="display: none;">
    <div class="chatbot-container bg-white d-flex flex-column shadow-lg" style="border-radius: 16px 16px 0 0;">

      <!-- Header -->
      <div class="chatbot-header d-flex justify-content-between align-items-center p-3 text-white rounded-top" style="background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);">
        <div>
          <h5 class="mb-0"><span style="color: #00c853;">⚡</span> Electripid AI Assistant</h5>
          <p class="mb-0 small opacity-75" style="font-size: 0.7rem;">Powered by Ollama</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-light opacity-75" onclick="clearChatHistory()" title="Clear chat">
            <i class="bi bi-trash"></i>
          </button>
          <button class="btn btn-sm btn-light opacity-75" onclick="closeChatbot()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>

      <!-- Messages Area -->
      <div class="chatbot-messages flex-fill p-3 overflow-auto" id="chatbotMessages" style="background: #f8f9fa;">
        <div class="bot-message d-flex gap-2 mb-3">
          <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%); color: white; font-size: 0.9rem;">🤖</div>
          <div class="message-content bg-white p-2 rounded-3 small shadow-sm">
            Hello! I'm your Electripid assistant powered by AI. I can help you with:
            <br>• Energy consumption analysis
            <br>• Money-saving tips
            <br>• Appliance recommendations
            <br>• Bill estimates
            <br><br>How can I help you today?
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="chatbot-input d-flex gap-2 p-3 bg-white border-top rounded-bottom">
        <input
          type="text"
          id="chatInput"
          class="form-control flex-fill"
          placeholder="Ask me anything about energy..."
          onkeypress="handleChatKeypress(event)"
          style="border-radius: 20px; border: 2px solid #e9ecef; font-size: 0.85rem;">
        <button
          class="btn text-white rounded-circle d-flex align-items-center justify-content-center"
          onclick="sendMessage()"
          style="width: 38px; height: 38px; background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%); border: none;">
          <i class="bi bi-send-fill"></i>
        </button>
      </div>
    </div>
  </div>

  <style>
    @keyframes typing {

      0%,
      60%,
      100% {
        transform: translateY(0);
      }

      30% {
        transform: translateY(-10px);
      }
    }

    .chatbot-messages {
      scroll-behavior: smooth;
    }

    .chatbot-messages::-webkit-scrollbar {
      width: 6px;
    }

    .chatbot-messages::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    .message-content {
      animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Chatbot Widget Styles */
    .chatbot-widget {
      position: fixed;
      bottom: 0;
      right: 100px;
      width: 350px;
      height: 500px;
      max-height: 80vh;
      z-index: 1000;
      animation: slideUp 0.3s ease-out;
    }

    .chatbot-widget .chatbot-container {
      width: 100%;
      height: 100%;
      max-height: 500px;
    }

    .chatbot-widget .chatbot-messages {
      max-height: 350px;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Mobile Responsive */
    @media (max-width: 576px) {
      .chatbot-widget {
        width: calc(100% - 20px) !important;
        right: 10px !important;
        bottom: 0 !important;
        height: 60vh !important;
      }

      .chatbot-widget .chatbot-container {
        height: 100% !important;
      }

      .message-content {
        max-width: 85% !important;
      }
    }

    /* Hide number input spinners */
    #customAmount::-webkit-outer-spin-button,
    #customAmount::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    #customAmount[type=number] {
      -moz-appearance: textfield;
    }
  </style>

  <!-- Floating Action Buttons -->
  <button class="floating-btn donation-btn" onclick="openDonationModal()">
    <i class="bi bi-heart-fill"></i>
  </button>
  <button class="floating-btn chatbot-btn" onclick="openChatbot()">
    <i class="bi bi-chat-dots-fill"></i>
  </button>

  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=AWYEp1TqBsmBV8WfID4-nr3Soew-fL2FUx2ubkfXS_Qw41bKVP_YligWWRKjdYJSaQeZvDbSoKzrg5Ro&currency=USD"></script>

  <script>
    let appliances = <?php echo json_encode($appliances); ?>;
    let currentRate = <?php echo $currentRate; ?>;
    let currentProvider = <?php echo json_encode($currentProvider); ?>;
    let forecastChart = null;
    const WEATHER_API_KEY = 'a4ad5de980d109abed0fec591eefd391';
    let currentLocation = 'Batangas';
    let userId = <?php echo $userId; ?>;
    let selectedDonationAmount = null;

    const USD_RATE = 59; // 1 USD ≈ 59 PHP

    // Provider rates mapping
    const providerRates = {
      "Meralco": 13.01,
      "BATELEC I": 10.08,
      "Batelec 1": 10.08,
      "BATELEC II": 9.90,
      "Batelec 2": 9.90
    };

    // Update rate when provider changes
    if (currentProvider && providerRates[currentProvider]) {
      currentRate = providerRates[currentProvider];
    }

    document.addEventListener('DOMContentLoaded', function() {
      updateAllMetrics();
      initForecastChart();
      fetchWeather();
      loadAppliances();
    });

    // Weather functions
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
      const current = data.list[0];
      document.getElementById('weatherTemp').textContent = Math.round(current.main.temp) + '°C';
      document.getElementById('weatherCondition').textContent = current.weather[0].description;
      document.getElementById('weatherHumidity').textContent = current.main.humidity;
      document.getElementById('weatherWind').textContent = current.wind.speed;

      const iconCode = current.weather[0].icon;
      const weatherIcon = document.querySelector('#weatherIcon img');
      weatherIcon.src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
      
      // Add color filter based on weather condition
      const condition = current.weather[0].main.toLowerCase();
      const description = current.weather[0].description.toLowerCase();
      let filterColor = '';
      
      if (condition.includes('cloud') || description.includes('cloud')) {
        filterColor = 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.9)'; // Blue
      } else if (condition.includes('rain') || description.includes('rain') || description.includes('drizzle')) {
        filterColor = 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.8)'; // Darker blue
      } else if (condition.includes('clear') || description.includes('clear') || description.includes('sun')) {
        filterColor = 'brightness(0) saturate(100%) invert(80%) sepia(100%) saturate(2000%) hue-rotate(0deg) brightness(1.1)'; // Yellow/Orange
      } else if (condition.includes('snow')) {
        filterColor = 'brightness(0) saturate(100%) invert(100%)'; // White
      } else if (condition.includes('thunder') || description.includes('thunder')) {
        filterColor = 'brightness(0) saturate(100%) invert(20%) sepia(100%) saturate(2000%) hue-rotate(250deg)'; // Purple
      } else if (condition.includes('mist') || condition.includes('fog') || description.includes('mist') || description.includes('fog')) {
        filterColor = 'brightness(0) saturate(100%) invert(90%)'; // Light gray
      }
      
      if (filterColor) {
        weatherIcon.style.filter = filterColor;
      }
    }

    function updateWeatherForecast(data) {
      const forecastContainer = document.getElementById('weatherForecast');
      forecastContainer.innerHTML = '';

      const forecastByDay = {};
      data.list.forEach(item => {
        const date = new Date(item.dt_txt);
        const day = date.toLocaleDateString('en-US', {
          weekday: 'short'
        });
        const hour = date.getHours();

        if (hour === 12 && !forecastByDay[day]) {
          forecastByDay[day] = item;
        }
      });

      Object.keys(forecastByDay).forEach(day => {
        const item = forecastByDay[day];
        const iconCode = item.weather[0].icon;
        const temp = Math.round(item.main.temp);
        const condition = item.weather[0].main.toLowerCase();
        const description = item.weather[0].description.toLowerCase();
        
        // Determine color filter based on weather condition
        let filterColor = '';
        if (condition.includes('cloud') || description.includes('cloud')) {
          filterColor = 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.9)'; // Blue
        } else if (condition.includes('rain') || description.includes('rain') || description.includes('drizzle')) {
          filterColor = 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.8)'; // Darker blue
        } else if (condition.includes('clear') || description.includes('clear') || description.includes('sun')) {
          filterColor = 'brightness(0) saturate(100%) invert(80%) sepia(100%) saturate(2000%) hue-rotate(0deg) brightness(1.1)'; // Yellow/Orange
        } else if (condition.includes('snow')) {
          filterColor = 'brightness(0) saturate(100%) invert(100%)'; // White
        } else if (condition.includes('thunder') || description.includes('thunder')) {
          filterColor = 'brightness(0) saturate(100%) invert(20%) sepia(100%) saturate(2000%) hue-rotate(250deg)'; // Purple
        } else if (condition.includes('mist') || condition.includes('fog') || description.includes('mist') || description.includes('fog')) {
          filterColor = 'brightness(0) saturate(100%) invert(90%)'; // Light gray
        }

        const dayDiv = document.createElement('div');
        dayDiv.classList.add('forecast-day');
        const imgStyle = filterColor ? `style="filter: ${filterColor};"` : '';
        dayDiv.innerHTML = `
          <div class="fw-bold">${day}</div>
          <img src="https://openweathermap.org/img/wn/${iconCode}@2x.png" alt="icon" ${imgStyle}>
          <div>${temp}°C</div>
        `;
        forecastContainer.appendChild(dayDiv);
      });
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

    function openDonationModal() {
      const modal = document.getElementById('donationModal');
      modal.style.display = 'flex';
      modal.classList.add('d-flex');
      renderPayPalButtons();
    }

    function closeDonationModal() {
      const modal = document.getElementById('donationModal');
      modal.style.display = 'none';
      modal.classList.remove('d-flex');
    }

    function selectAmount(amount, el) {
      document.getElementById('customAmount').value = '';
      selectedDonationAmount = Number(amount);

      document.querySelectorAll('.donation-btn').forEach(btn => {
        btn.classList.remove('active');
      });

      if (el) el.classList.add('active');

      renderPayPalButtons();
    }

    // ===============================
    // CUSTOM AMOUNT INPUT
    // ===============================
    document.getElementById('customAmount').addEventListener('input', function() {
      const value = Number(this.value);

      if (value >= 10) {
        selectedDonationAmount = value;

        document.querySelectorAll('.donation-btn')
          .forEach(btn => btn.classList.remove('active'));

        renderPayPalButtons();
      }
    });


    // ===============================
    // PAYPAL INTEGRATION (FIXED)
    // ===============================
    function getDonationAmount() {
      const customAmountInput = document.getElementById('customAmount');
      const typedAmount = Number(customAmountInput.value);

      if (!isNaN(typedAmount) && typedAmount >= 10) {
        selectedDonationAmount = typedAmount;
        return typedAmount;
      }

      if (selectedDonationAmount && selectedDonationAmount >= 10) {
        return Number(selectedDonationAmount);
      }

      return null;
    }

    function renderPayPalButtons() {
      const container = document.getElementById('paypal-button-container');
      container.innerHTML = '';

      const phpAmount = getDonationAmount();

      // HARD VALIDATION
      if (!phpAmount || isNaN(phpAmount) || phpAmount < 10) {
        container.innerHTML =
          '<div class="alert alert-warning">Minimum donation is ₱10.</div>';
        return;
      }

      // Check if PayPal SDK is loaded
      if (typeof paypal === 'undefined') {
        container.innerHTML = '<div class="alert alert-info">Loading PayPal...</div>';
        setTimeout(function() {
          renderPayPalButtons();
        }, 500);
        return;
      }

      // Convert PHP → USD
      const usdAmount = (phpAmount / USD_RATE).toFixed(2);

      console.log('PayPal Amounts:', {
        php: phpAmount,
        usd: usdAmount
      });

      try {
        paypal.Buttons({

            // CREATE ORDER
            createOrder: async function() {
              try {
                const response = await fetch('../paypal/paypal.php?action=create', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    amount: usdAmount
                  })
                });

                const text = await response.text();
                if (!response.ok) throw new Error(text);

                const result = JSON.parse(text);
                if (!result.id) throw new Error('No order ID returned');

                return result.id;

              } catch (error) {
                console.error('Create Order Error:', error);
                alert('Unable to create payment.\n\n' + error.message);
                throw error;
              }
            },

            // CAPTURE PAYMENT
            onApprove: async function(data) {
              try {
                const response = await fetch('../paypal/paypal.php?action=capture', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    orderID: data.orderID,
                    phpAmount: phpAmount
                  })
                });

                const text = await response.text();
                if (!response.ok) throw new Error(text);

                const result = JSON.parse(text);

                if (result.success) {
                  alert(
                    'Thank you for your donation of ₱' +
                    phpAmount.toFixed(2) +
                    ' 💚'
                  );
                  closeDonationModal();
                  setTimeout(() => location.reload(), 1500);
                } else {
                  throw new Error(result.error || 'Payment failed');
                }

              } catch (error) {
                console.error('Capture Error:', error);
                alert(
                  'Payment processing failed.\n\n' +
                  error.message +
                  '\n\nIf charged, please contact support.'
                );
              }
            },

            // CANCEL
            onCancel: function() {
              alert('Payment was cancelled.');
            },

            // ERROR
            onError: function(err) {
              console.error('PayPal Error:', err);
              alert('An error occurred. Please try again.');
            },

            // STYLE
            style: {
              layout: 'vertical',
              color: 'blue',
              shape: 'rect',
              label: 'paypal'
            }

          }).render('#paypal-button-container')
          .catch(error => {
            console.error('Render Error:', error);
            container.innerHTML =
              '<div class="alert alert-danger">Failed to load PayPal buttons. Please refresh the page and try again.</div>';
          });
      } catch (error) {
        console.error('PayPal Initialization Error:', error);
        container.innerHTML = '<div class="alert alert-info">Loading PayPal...</div>';
        setTimeout(function() {
          renderPayPalButtons();
        }, 1000);
      }
    }
    // Chatbot Functions with Ollama Integration
    let isChatbotLoading = false;

    function openChatbot() {
      const widget = document.getElementById('chatbotWidget');
      widget.style.display = 'block';
      loadChatHistory();
    }

    function closeChatbot() {
      const widget = document.getElementById('chatbotWidget');
      widget.style.display = 'none';
    }

    function handleChatKeypress(event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
      }
    }

    async function loadChatHistory() {
      try {
        const response = await fetch('../chatbot/get_history.php');
        const result = await response.json();

        if (!result.success) return;

        const messagesContainer = document.getElementById('chatbotMessages');

        // Preserve only the welcome message
        const welcome = messagesContainer.firstElementChild;
        messagesContainer.innerHTML = '';
        if (welcome) messagesContainer.appendChild(welcome);

        result.messages.forEach(msg => {
          addMessageToChat(msg.message, msg.sender, false);
        });

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      } catch (error) {
        console.error('Error loading chat history:', error);
      }
    }


    async function sendMessage() {
      removeTypingIndicator(); // ← ADD THIS LINE FIRST

      const input = document.getElementById('chatInput');
      const message = input.value.trim();

      if (!message || isChatbotLoading) return;

      addMessageToChat(message, 'user');
      input.value = '';
      showTypingIndicator();
      isChatbotLoading = true;


      try {
        const response = await fetch('../chatbot/chat_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            message: message
          })
        });

        const text = await response.text();

        // Try to parse JSON
        let result;
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error('Response is not JSON:', text);
          removeTypingIndicator();
          isChatbotLoading = false;
          addMessageToChat('Error: Server returned invalid response. Please check browser console for details.', 'bot');
          return;
        }

        removeTypingIndicator();
        isChatbotLoading = false;

        if (result.success) {
          addMessageToChat(result.reply, 'bot');
        } else {
          addMessageToChat('Error: ' + (result.error || 'Unknown error occurred'), 'bot');
        }
      } catch (error) {
        console.error('Chatbot error:', error);
        removeTypingIndicator(); // REQUIRED
        isChatbotLoading = false;
        addMessageToChat('Network error. Please try again.', 'bot');
      }

    }

    function addMessageToChat(message, sender, scrollToBottom = true) {
      const messagesContainer = document.getElementById('chatbotMessages');
      const messageDiv = document.createElement('div');
      messageDiv.className = sender === 'user' ? 'user-message d-flex gap-3 mb-4 flex-row-reverse' : 'bot-message d-flex gap-3 mb-4';
      const avatar = sender === 'user' ? '👤' : '🤖';

      messageDiv.innerHTML = `
    <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; ${sender === 'user' ? 'background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);' : 'background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);'} color: white; font-size: 0.9rem;">${avatar}</div>
    <div class="message-content ${sender === 'user' ? 'text-white' : 'bg-white'}" style="${sender === 'user' ? 'background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);' : ''} p-2 rounded-3 small; max-width: 75%; word-wrap: break-word;">${escapeHtml(message)}</div>
  `;

      messagesContainer.appendChild(messageDiv);

      if (scrollToBottom) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function showTypingIndicator() {
      const messagesContainer = document.getElementById('chatbotMessages');
      const typingDiv = document.createElement('div');
      typingDiv.className = 'bot-message typing-indicator-container d-flex gap-3 mb-4';
      typingDiv.id = 'typingIndicator';
      typingDiv.innerHTML = `
    <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%); color: white; font-size: 0.9rem;">🤖</div>
    <div class="typing-indicator d-flex gap-1 p-2 bg-light rounded-3">
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite;"></div>
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite 0.2s;"></div>
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite 0.4s;"></div>
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

    async function clearChatHistory() {
      if (!confirm('Are you sure you want to clear your chat history?')) return;

      try {
        const response = await fetch('../chatbot/clear_chat.php', {
          method: 'POST'
        });
        const text = await response.text();

        // Try to parse JSON
        let result;
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error('Response is not JSON:', text);
          alert('Error clearing chat. Check browser console.');
          return;
        }

        if (result.success) {
          const messagesContainer = document.getElementById('chatbotMessages');
          messagesContainer.innerHTML = `
        <div class="bot-message d-flex gap-2 mb-3">
          <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%); color: white; font-size: 0.9rem;">🤖</div>
          <div class="message-content bg-white p-2 rounded-3 small shadow-sm">
            Hello! I'm your Electripid assistant powered by AI. I can help you with:
            <br>• Energy consumption analysis
            <br>• Money-saving tips
            <br>• Appliance recommendations
            <br>• Bill estimates
            <br><br>How can I help you today?
          </div>
        </div>
      `;
        } else {
          alert('Error: ' + (result.error || 'Failed to clear chat'));
        }
      } catch (error) {
        console.error('Error clearing chat:', error);
        alert('Network error while clearing chat');
      }
    }

    // Appliance Functions
    async function loadAppliances() {
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
        const response = await fetch('appliances/save_appliance.php', {
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

        const text = await response.text();
        let result;
        
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error('Invalid JSON response:', text);
          console.error('Error:', e);
          return;
        }

        if (result.success) {
          document.getElementById('deviceName').value = '';
          document.getElementById('devicePower').value = '';
          document.getElementById('deviceHours').value = '';
          document.getElementById('deviceUsagePerWeek').value = '';
          location.reload();
        } else {
          console.error('Error adding appliance:', result.error);
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }

    async function removeApplianceDB(applianceId) {
      if (confirm('Are you sure you want to remove this appliance?')) {
        try {
          const response = await fetch('appliances/remove_appliance.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              appliance_id: applianceId,
              user_id: userId
            })
          });

          const text = await response.text();
          let result;
          
          try {
            result = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON response:', text);
            console.error('Error:', e);
            return;
          }

          if (result.success) {
            location.reload();
          } else {
            console.error('Error removing appliance:', result.error);
          }
        } catch (error) {
          console.error('Error:', error);
        }
      }
    }

    // Metrics and Chart Functions
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

    // Save electricity reading to database
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

    // Save forecast to database
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
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>

</html>