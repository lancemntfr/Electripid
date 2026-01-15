<?php
session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$userId = $_SESSION['user_id'];
$userName = 'User';
$userEmail = '';
$userCity = 'Batangas'; // Default fallback
$userBarangay = '';

if (!empty($userId)) {
  $user_id = mysqli_real_escape_string($conn, $userId);
  $user_query = "SELECT fname, lname, email, city, barangay FROM USER WHERE user_id = '$user_id' LIMIT 1";
  $user_result = executeQuery($user_query);

  if ($user_result && mysqli_num_rows($user_result) === 1) {
    $user_row = mysqli_fetch_assoc($user_result);
    $userName = trim($user_row['fname'] . ' ' . $user_row['lname']);
    $userEmail = $user_row['email'];
    $userCity = $user_row['city'] ?: 'Batangas'; // Default to Batangas if empty
    $userBarangay = $user_row['barangay'] ?: '';
  } else {
    $userName = htmlspecialchars($_SESSION['username'] ?? 'User');
    $userEmail = htmlspecialchars($_SESSION['email'] ?? '');
  }
} else {
  $userName = htmlspecialchars($_SESSION['username'] ?? 'User');
  $userEmail = htmlspecialchars($_SESSION['email'] ?? '');
}

$provider_query = "SELECT provider_name, rates FROM ELECTRICITY_PROVIDER";
$provider_result = executeQuery($provider_query);
$providerRates = [];
while ($row = $provider_result->fetch_assoc()) {
  $providerRates[$row['provider_name']] = floatval($row['rates']);
}

$appliances = [];
$currentProvider = '';
$currentRate = 0.00;
$monthlyBudget = 0; // Default budget
$user_id_escaped = mysqli_real_escape_string($conn, $userId);
$household_query = "SELECT h.household_id, h.monthly_budget, p.provider_name, p.rates FROM HOUSEHOLD h LEFT JOIN ELECTRICITY_PROVIDER p ON h.provider_id = p.provider_id WHERE h.user_id = '$user_id_escaped'";
$household_result = executeQuery($household_query);

if ($household_result && $household_result->num_rows > 0) {
  $household_row = $household_result->fetch_assoc();
  $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);

  if (!empty($household_row['provider_name'])) {
    $currentProvider = $household_row['provider_name'];
    if (!empty($household_row['rates'])) {
      $currentRate = floatval($household_row['rates']);
    } elseif (isset($providerRates[$currentProvider])) {
      $currentRate = $providerRates[$currentProvider];
    }
  }

  if (!empty($household_row['monthly_budget'])) {
    $monthlyBudget = floatval($household_row['monthly_budget']);
  }

  $appliance_query = "SELECT * FROM APPLIANCE WHERE household_id = '$household_id' ORDER BY appliance_id DESC";
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
  <!-- Meta tag for passing city to JavaScript -->
  <meta name="user-city" content="<?php echo htmlspecialchars($userCity); ?>">
</head>

<body class="dashboard-page">
  <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-radius: 0 !important;">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="#" style="color: #1E88E5 !important;">
        <i class="bi bi-lightning-charge-fill me-2" style="color: #00bfa5;"></i>Electripid
      </a>
      <div class="d-flex align-items-center">
        <button class="nav-icon-btn position-relative me-3" type="button" style="font-size: 2rem;">
          <i class="bi bi-bell"></i>
        </button>
        <div class="dropdown ms-2">
          <button class="btn p-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle" style="font-size: 2rem; color: var(--secondary-color);"></i>
            <div class="ms-2 text-start d-none d-md-block">
              <div class="fw-semibold" style="font-size: 0.9rem; line-height: 1.2;">
                <?= htmlspecialchars($userName) ?>
              </div>
              <?php if (!empty($userEmail)): ?>
                <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.2;">
                  <?= htmlspecialchars($userEmail) ?>
                </div>
              <?php endif; ?>
            </div>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="d-block d-md-none px-3 pt-2 pb-1">
              <div class="fw-semibold"><?= htmlspecialchars($userName) ?></div>
              <?php if (!empty($userEmail)): ?>
                <div class="small text-muted"><?= htmlspecialchars($userEmail) ?></div>
              <?php endif; ?>
            </li>
            <li>
              <hr class="dropdown-divider d-block d-md-none mb-0">
            </li>
            <li>
              <a class="dropdown-item" href="settings.php">
                <i class="bi bi-gear-fill me-2"></i> Settings
              </a>
            </li>
            <li>
              <a class="dropdown-item text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

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
          <h4 class="mb-0">₱<span id="monthlyBudget"><?php echo number_format($monthlyBudget); ?></span></h4>
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
      <div class="col-lg-3 col-md-6">
        <div class="info-card h-100 d-flex flex-column">
          <div class="info-card-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-cloud-sun"></i>
          </div>
          <h6 class="text-muted mb-1">Forecasted Cost</h6>
          <h4 class="mb-0">₱<span id="forecastedCost">0</span></h4>
        </div>
      </div>
    </div>

    <!-- Updated Weather Widget -->
    <div class="weather-widget bg-white p-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div class="weather-current d-flex align-items-center gap-4 mb-3 mb-md-0">
        <div class="weather-icon" id="weatherIcon">
          <img src="https://openweathermap.org/img/wn/02d@2x.png" alt="Weather Icon" loading="lazy" style="width: 80px; height: 80px;">
        </div>
        <div>
          <div class="weather-location small text-secondary text-uppercase fw-semibold" id="weatherLocation">
            <?php echo htmlspecialchars($userCity . ($userBarangay ? ', ' . $userBarangay : '')); ?>
          </div>
          <div class="weather-date small text-muted mb-2" id="weatherDate">
            <?php echo date('l, F j, Y'); ?>
          </div>
          <div class="weather-temp fw-bold mb-2" id="weatherTemp">--°C</div>
          <div class="weather-info d-flex flex-wrap gap-3 small text-secondary">
            <span class="d-flex align-items-center gap-1" id="weatherCondition">--</span>
            <span class="d-flex align-items-center gap-1">
              <i class="bi bi-droplet"></i>
              <span id="weatherHumidity">--</span>%
            </span>
            <span class="d-flex align-items-center gap-1">
              <i class="bi bi-wind"></i>
              <span id="weatherWind">--</span> km/h
            </span>
          </div>
        </div>
      </div>
      <div class="weather-forecast d-flex gap-3" id="weatherForecast">
        <!-- JavaScript will populate this with 5-day forecast -->
      </div>
    </div>

    <!-- Appliances and Energy Overview -->
    <div class="row g-4 mb-4">
      <div class="col-lg-9">
        <div class="chart-container">
          <h5 class="mb-3"><i class="bi bi-list-check me-2"></i>Appliances <span class="badge bg-primary ms-2" id="activeApplianceCount">0</span></h5>

          <div class="row g-3">
            <div class="col-lg-4">
              <div class="mb-2">
                <label class="form-label small text-muted mb-1">Device Name</label>
                <input type="text" id="deviceName" class="form-control form-control-sm" placeholder="e.g. Aircon">
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted mb-1">Power (Watts)</label>
                <input type="number" id="devicePower" class="form-control form-control-sm" placeholder="e.g. 1200">
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted mb-1">Hours per Day</label>
                <input type="number" id="deviceHours" class="form-control form-control-sm" placeholder="e.g. 8">
              </div>
              <div class="mb-3">
                <label class="form-label small text-muted mb-1">Usage per Week (Days)</label>
                <input type="number" id="deviceUsagePerWeek" class="form-control form-control-sm" placeholder="e.g. 5">
              </div>
              <button class="btn btn-primary btn-sm w-100" onclick="addAppliance()">
                <i class="bi bi-plus-circle me-1"></i>Add Appliance
              </button>
            </div>

            <div class="col-lg-8 d-flex flex-column">
              <div id="applianceDisplayList" class="flex-grow-1" style="max-height: 280px; overflow-y: auto;">
                <div class="text-center text-muted small py-3">
                  No appliances tracked yet. Add one to get started!
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3">
        <div class="chart-container h-100 d-flex flex-column">
          <h5 class="mb-3"><i class="bi bi-bar-chart me-2"></i>Energy Overview</h5>
          <p class="text-muted">Predicted cost of your energy consumption</p>
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

    <!-- Edit Appliance Modal -->
    <div class="modal fade" id="editApplianceModal" tabindex="-1" aria-labelledby="editApplianceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editApplianceModalLabel">Edit Appliance</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label small text-muted">Device Name</label>
              <input type="text" id="editDeviceName" class="form-control" placeholder="e.g. Aircon">
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted">Power (Watts)</label>
              <input type="number" id="editDevicePower" class="form-control" placeholder="e.g. 1200">
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted">Hours per Day</label>
              <input type="number" id="editDeviceHours" class="form-control" placeholder="e.g. 8">
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted">Usage per Week (Days)</label>
              <input type="number" id="editDeviceUsagePerWeek" class="form-control" placeholder="e.g. 5">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveEditedAppliance()">
              <i class="bi bi-check-circle me-1"></i>Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Appliance Modal -->
    <div class="modal fade" id="deleteApplianceModal" tabindex="-1" aria-labelledby="deleteApplianceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title" id="deleteApplianceModalLabel">Remove Appliance</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <p class="mb-0">Are you sure you want to remove this appliance from your list?</p>
          </div>
          <div class="modal-footer justify-content-center border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger btn-sm px-4" onclick="confirmDeleteAppliance()">
              <i class="bi bi-trash me-1"></i>Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Energy Forecast and Tips -->
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
    <div class="chatbot-header d-flex justify-content-between align-items-center p-3 text-white rounded-top"
      style="background: #1E88E5 !important;">
      <div>
        <h5 class="mb-0">
          <span style="color: #00c853;">⚡</span> Electripid AI Assistant
        </h5>
      </div>

      <!-- Header Buttons -->
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-light opacity-75"
          onclick="clearChatHistory()" title="Clear chat">
          <i class="bi bi-trash"></i>
        </button>

        <!-- SINGLE EXIT BUTTON -->
        <button class="btn btn-sm btn-light opacity-75"
          onclick="closeChatbot()" title="Close chat">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div class="chatbot-messages flex-fill p-3 overflow-auto"
      id="chatbotMessages" style="background: #f8f9fa;">
      <div class="bot-message d-flex gap-2 mb-3">
        <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center"
          style="flex-shrink: 0; width: 30px; height: 30px; background: #1E88E5 !important; color: white; font-size: 0.9rem;">
          🤖
        </div>
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

    <!-- Input -->
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
        style="width: 38px; height: 38px; background: #1E88E5 !important; border: none;">
        <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</div>

<style>
@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-10px); }
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
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

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
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

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

#customAmount::-webkit-outer-spin-button,
#customAmount::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

#customAmount[type=number] {
  -moz-appearance: textfield;
}
</style>

<!-- Floating Buttons -->
<button class="floating-btn donation-btn" onclick="openDonationModal()">
  <i class="bi bi-heart-fill"></i>
</button>

<button class="floating-btn chatbot-btn" onclick="openChatbot()">
  <i class="bi bi-chat-dots-fill"></i>
</button>


  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=AWYEp1TqBsmBV8WfID4-nr3Soew-fL2FUx2ubkfXS_Qw41bKVP_YligWWRKjdYJSaQeZvDbSoKzrg5Ro&currency=USD"></script>

  <!-- Global Variables -->
  <script>
    // Global variables needed across modules
    let appliances = <?php echo json_encode($appliances); ?>;
    let currentRate = <?php echo $currentRate; ?>;
    let currentProvider = <?php echo json_encode($currentProvider); ?>;
    let monthlyBudget = <?php echo $monthlyBudget; ?>;
    let forecastChart = null;
    let currentLocation = '<?php echo addslashes($userCity); ?>';
    let userBarangay = '<?php echo addslashes($userBarangay); ?>';
    let userId = <?php echo $userId; ?>;
    let selectedDonationAmount = null;
    const USD_RATE = 59;
    const providerRates = <?php echo json_encode($providerRates); ?>;

    if (currentProvider && providerRates[currentProvider]) {
      currentRate = providerRates[currentProvider];
    }

    document.addEventListener('DOMContentLoaded', function() {
      updateAllMetrics();
      initForecastChart();
      loadAppliances();

      // ✅ THIS LINE IS MISSING
      if (typeof fetchWeather === 'function') {
        fetchWeather(currentLocation);
      }
    });
  </script>

  <!-- Modular JavaScript Files -->
  <script src="../assets/js/user/appliances.js"></script>
  <script src="../assets/js/user/donations.js"></script>
  <script src="../assets/js/user/chatbot.js"></script>
  <script src="../assets/js/user/charts.js"></script>
  <script src="../assets/js/user/metrics.js"></script>

  <!-- Weather JavaScript - Updated -->
  <script src="../assets/js/user/weather.js"></script>

  <!-- Bootstrap and Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>

</html>