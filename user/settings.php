<?php
    session_start();
    require_once __DIR__ . '/../connect.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);

    // Load user data
    $user_query = "SELECT fname, lname, email, cp_number, city, barangay FROM USER WHERE user_id = '$user_id_escaped'";
    $user_result = executeQuery($user_query);
    
    if (!$user_result || mysqli_num_rows($user_result) === 0) {
        header('Location: login.php');
        exit;
    }
    
    $user = mysqli_fetch_assoc($user_result);

    // Get user name and email for navbar
    $userName = trim($user['fname'] . ' ' . $user['lname']);
    $userEmail = $user['email'];

    // Load notification preferences (default to true if not set)
    $notify_email = true;
    $notify_sms = true;
    
    // Check if columns exist first
    $check_email_col = executeQuery("SHOW COLUMNS FROM USER LIKE 'notify_email'");
    $check_sms_col = executeQuery("SHOW COLUMNS FROM USER LIKE 'notify_sms'");
    
    if ($check_email_col && mysqli_num_rows($check_email_col) > 0 && 
        $check_sms_col && mysqli_num_rows($check_sms_col) > 0) {
        $notif_pref_query = "SELECT notify_email, notify_sms FROM USER WHERE user_id = '$user_id_escaped'";
        $notif_pref_result = executeQuery($notif_pref_query);
        
        if ($notif_pref_result && mysqli_num_rows($notif_pref_result) > 0) {
            $pref_row = mysqli_fetch_assoc($notif_pref_result);
            $notify_email = isset($pref_row['notify_email']) ? (bool)$pref_row['notify_email'] : true;
            $notify_sms = isset($pref_row['notify_sms']) ? (bool)$pref_row['notify_sms'] : true;
        }
    }

    // Check if phone was just verified
    $phone_verified = isset($_GET['verified']) && $_GET['verified'] == '1';
    
    // Check email verification status
    $email_verified = false;
    $email_verification_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND is_verified=1 LIMIT 1");
    $email_verification_check->bind_param("i", $user_id);
    $email_verification_check->execute();
    $email_verification_result = $email_verification_check->get_result();
    if ($email_verification_result && $email_verification_result->num_rows > 0) {
        $email_verified = true;
    }
    
    // Check phone verification status
    $phone_verified_status = false;
    $original_phone_digits = '';
    if (!empty($user['cp_number'])) {
        // Normalize phone to last 10 digits for comparison
        $digits_only = preg_replace('/\D/', '', $user['cp_number']);
        $original_phone_digits = substr($digits_only, -10);

        $phone_verification_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='sms' AND is_verified=1 LIMIT 1");
        $phone_verification_check->bind_param("i", $user_id);
        $phone_verification_check->execute();
        $phone_verification_result = $phone_verification_check->get_result();
        if ($phone_verification_result && $phone_verification_result->num_rows > 0) {
            $phone_verified_status = true;
        }
    }

    // Load household/provider data
    $household_query = "SELECT h.provider_id, h.monthly_budget, p.provider_name FROM HOUSEHOLD h 
                        LEFT JOIN ELECTRICITY_PROVIDER p ON h.provider_id = p.provider_id 
                        WHERE h.user_id = '$user_id_escaped'";
    $household_result = executeQuery($household_query);
    $current_provider_id = 0;
    $current_provider_name = '';
    $current_monthly_budget = 0;
    
    if ($household_result && mysqli_num_rows($household_result) > 0) {
        $household = mysqli_fetch_assoc($household_result);
        $current_provider_id = $household['provider_id'];
        $current_provider_name = $household['provider_name'] ?? '';
        $current_monthly_budget = floatval($household['monthly_budget'] ?? 0);
    }

    // Get all providers
    $providers_result = executeQuery("SELECT provider_id, provider_name FROM electricity_provider ORDER BY provider_name ASC");
    $providers = [];
    if ($providers_result && mysqli_num_rows($providers_result) > 0) {
        while ($row = mysqli_fetch_assoc($providers_result)) {
            $providers[] = $row;
        }
    }

    // Get city code from city name (for dropdown)
    $city_code = '';
    if (!empty($user['city'])) {
        // Try to find city code from API
        $cities_json = @file_get_contents("https://psgc.cloud/api/cities");
        $municipalities_json = @file_get_contents("https://psgc.cloud/api/municipalities");
        $cities = json_decode($cities_json, true) ?? [];
        $municipalities = json_decode($municipalities_json, true) ?? [];
        $all = array_merge($cities, $municipalities);
        
        foreach ($all as $loc) {
            if (stripos($loc['name'], $user['city']) !== false || stripos($user['city'], $loc['name']) !== false) {
                $city_code = $loc['code'];
                break;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electripid - Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
            min-height: 100vh;
            padding-top: 0;
        }
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-item:hover {
            background-color: #f8f9fa;
        }
        .setting-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .setting-value {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .setting-value.empty {
            color: #adb5bd;
            font-style: italic;
        }
        .change-btn {
            white-space: nowrap;
            min-width: 45px;
            width: 45px;
            height: 45px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .change-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .phone-verify-btn {
            margin-top: 0;
        }
        .settings-section {
            margin-bottom: 20px;
        }
        .settings-section:last-child {
            margin-bottom: 0;
        }
        .section-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0;
            padding: 14px 18px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: 6px 6px 0 0;
        }
        .section-header:hover {
            background-color: #f8f9fa;
        }
        .section-header i {
            font-size: 1.3rem;
        }
        .section-header .bi-chevron-down {
            transition: transform 0.3s;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .section-header.collapsed .bi-chevron-down {
            transform: rotate(-90deg);
        }
        .section-content {
            border-top: 1px solid #e9ecef;
        }
        .section-content.collapse:not(.show) {
            display: none;
        }
        .setting-item {
            padding: 14px 18px;
        }
        .setting-label {
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        .setting-value {
            font-size: 0.9rem;
        }
        .settings-card {
            padding: 20px;
        }
        .code-input-box:focus {
            outline: none;
            border-color: #1e88e5;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-radius: 0 !important;">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="dashboard.php" style="color: #1E88E5 !important;">
                <i class="bi bi-lightning-charge-fill me-2" style="color: #00bfa5;"></i>Electripid
            </a>
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <button class="nav-icon-btn position-relative me-3" type="button" style="font-size: 2rem;">
                    <i class="bi bi-bell"></i>
                </button>
                <!-- User Profile -->
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
                        <li><hr class="dropdown-divider d-block d-md-none mb-0"></li>
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

    <div class="container settings-container px-5 py-4">
        <div class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0" style="font-size: 1.75rem;"><i class="bi bi-gear me-2"></i>Settings</h2>
                <a href="dashboard.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <div id="alertContainer">
                <?php if ($phone_verified): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>Phone number verified and saved successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Personal Details Section -->
            <div class="settings-section">
                <div class="section-header collapsed" data-bs-toggle="collapse" data-bs-target="#personalDetailsCollapse" aria-expanded="false" aria-controls="personalDetailsCollapse">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-person-circle"></i>
                        <span>Personal Details</span>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                    </div>
                <div class="collapse section-content" id="personalDetailsCollapse">
                    <!-- Name -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Name</div>
                            <div class="setting-value"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></div>
                        </div>
                        <button type="button" class="btn btn-outline-primary change-btn" onclick="openNameModal()" title="Edit Name">
                            <i class="bi bi-pencil"></i>
                        </button>
                </div>

                    <!-- Contact -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Contact</div>
                            <div class="setting-value">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <i class="bi bi-envelope me-2"></i><?= htmlspecialchars($user['email']) ?>
                                    </div>
                                    <span class="ms-4 me-3 text-<?= $email_verified ? 'success' : 'danger' ?> small">
                                        <?= $email_verified ? 'Verified' : 'Not verified' ?>
                                    </span>
                                </div>
                                <div class="mt-1 d-flex align-items-center <?= empty($user['cp_number']) ? 'empty' : '' ?>">
                                    <div class="flex-grow-1">
                                        <i class="bi bi-telephone me-2"></i>
                                        <?= !empty($user['cp_number']) ? htmlspecialchars($user['cp_number']) : 'add contact number' ?>
                                    </div>
                                    <span class="ms-4 me-3 text-<?= $phone_verified_status ? 'success' : 'danger' ?> small">
                                        <?= $phone_verified_status ? 'Verified' : 'Not verified' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-outline-primary change-btn" onclick="openContactsModal()" title="Change Contacts">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Address</div>
                            <div class="setting-value">
                                <div><?= htmlspecialchars($user['city']) ?></div>
                                <div class="mt-1"><?= htmlspecialchars($user['barangay']) ?></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary change-btn" onclick="openLocationModal()" title="Edit Address">
                            <i class="bi bi-pencil"></i>
                        </button>
                </div>

                    <!-- Electricity Provider -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Electricity Provider</div>
                            <div class="setting-value"><?= !empty($current_provider_name) ? htmlspecialchars($current_provider_name) : 'Not set' ?></div>
                        </div>
                        <button type="button" class="btn btn-outline-primary change-btn" onclick="openProviderModal()" title="Edit Provider">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Security Section -->
            <div class="settings-section">
                <div class="section-header collapsed" data-bs-toggle="collapse" data-bs-target="#securityCollapse" aria-expanded="false" aria-controls="securityCollapse">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-shield-lock"></i>
                        <span>Security</span>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="collapse section-content" id="securityCollapse">
                    <!-- Password -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Password</div>
                            <div class="setting-value">••••••••</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary change-btn" onclick="openPasswordModal()" title="Change Password">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Budgeting Section -->
            <div class="settings-section">
                <div class="section-header collapsed" data-bs-toggle="collapse" data-bs-target="#budgetingCollapse" aria-expanded="false" aria-controls="budgetingCollapse">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-wallet2"></i>
                        <span>Budgeting</span>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="collapse section-content" id="budgetingCollapse">
                    <!-- Monthly Budget -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Monthly Budget</div>
                            <div class="setting-value">
                                <?= $current_monthly_budget > 0 ? '₱' . number_format($current_monthly_budget, 2) : 'Not set' ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary change-btn" onclick="openBudgetModal()" title="<?= $current_monthly_budget > 0 ? 'Edit Budget' : 'Set Budget' ?>">
                            <i class="bi bi-<?= $current_monthly_budget > 0 ? 'pencil' : 'plus-circle' ?>"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Preferences Section -->
            <div class="settings-section">
                <div class="section-header collapsed" data-bs-toggle="collapse" data-bs-target="#notificationsCollapse" aria-expanded="false" aria-controls="notificationsCollapse">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-bell"></i>
                        <span>Notification Preferences</span>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="collapse section-content" id="notificationsCollapse">
                    <!-- Email Notifications -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">Email Notifications</div>
                            <div class="setting-value">
                                <?= $notify_email ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Enabled</span>' : '<span class="text-muted"><i class="bi bi-x-circle me-1"></i>Disabled</span>' ?>
                            </div>
                            <small class="text-muted">Receive notifications via email</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notifyEmailSwitch" 
                                   <?= $notify_email ? 'checked' : '' ?> 
                                   onchange="saveNotificationPreferences()" 
                                   style="width: 3rem; height: 1.5rem; cursor: pointer;">
                        </div>
                    </div>

                    <!-- SMS Notifications -->
                    <div class="setting-item">
                        <div class="flex-grow-1">
                            <div class="setting-label">SMS Notifications</div>
                            <div class="setting-value">
                                <?= $notify_sms ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Enabled</span>' : '<span class="text-muted"><i class="bi bi-x-circle me-1"></i>Disabled</span>' ?>
                            </div>
                            <small class="text-muted">Receive notifications via SMS <?= empty($user['cp_number']) ? '<span class="text-danger">(Phone number required)</span>' : '' ?></small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notifySmsSwitch" 
                                   <?= $notify_sms ? 'checked' : '' ?> 
                                   <?= empty($user['cp_number']) ? 'disabled' : '' ?>
                                   onchange="saveNotificationPreferences()" 
                                   style="width: 3rem; height: 1.5rem; cursor: pointer;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Contacts Modal -->
    <div class="modal fade" id="contactsModal" tabindex="-1" aria-labelledby="contactsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactsModalLabel">Change Contacts</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="email" class="form-control flex-grow-1" id="contactsEmailInput" required value="<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>" onchange="checkEmailChanged()">
                            <span class="text-<?= $email_verified ? 'success' : 'danger' ?> small" id="emailStatusText" style="min-width: 90px; text-align: right;">
                                <?= $email_verified ? 'Verified' : 'Not verified' ?>
                            </span>
                        </div>
                        <div id="emailVerifyLink" style="display: none;">
                            <small class="text-danger">
                                <a href="#" class="text-decoration-none" onclick="event.preventDefault(); openEmailVerifyModal();">Click here to verify your email</a>
                            </small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group flex-grow-1">
                                <span class="input-group-text">+63</span>
                                <input type="text" class="form-control" id="contactsPhoneInput" 
                                       placeholder="<?= empty($user['cp_number']) ? 'add contact number' : '912 345 6789' ?>" maxlength="13" 
                                       pattern="[0-9\s]{10,13}" value="<?= !empty($user['cp_number']) ? htmlspecialchars(str_replace('+63', '', $user['cp_number']), ENT_QUOTES) : '' ?>"
                                       oninput="checkPhoneChanged()">
                            </div>
                            <span id="phoneStatusText" class="text-<?= !empty($user['cp_number']) ? ($phone_verified_status ? 'success' : 'danger') : 'secondary' ?> small" style="min-width: 90px; text-align: right;">
                                <?php if (empty($user['cp_number'])): ?>
                                    add contact number
                                <?php else: ?>
                                    <?= $phone_verified_status ? 'Verified' : 'Not verified' ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div id="phoneVerifyLinkModal" style="<?= (!empty($user['cp_number']) && !$phone_verified_status) ? '' : 'display:none;' ?>">
                            <small class="text-danger">
                                <a href="#" class="text-decoration-none" onclick="event.preventDefault(); bootstrap.Modal.getInstance(document.getElementById('contactsModal')).hide(); openPhoneModal();">Click here to verify phone</a>
                            </small>
                        </div>
                    </div>
                    <div id="contactsAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveContactsBtn" onclick="saveContacts()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Field Modal -->
    <div class="modal fade" id="changeModal" tabindex="-1" aria-labelledby="changeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeModalLabel">Change <span id="changeFieldName"></span></h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New <span id="changeFieldLabel"></span> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="changeFieldInput" required>
                        <input type="hidden" id="changeFieldType">
                    </div>
                    <div id="changeAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveChangeBtn" onclick="saveChange()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationModalLabel">Change Location</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">City <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalCity" required>
                            <option value="">Select city</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barangay <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalBarangay" required>
                            <option value="">Select barangay</option>
                        </select>
                    </div>
                    <div id="locationAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveLocationBtn" onclick="saveLocation()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
                    </div>
                </div>

    <!-- Provider Modal -->
    <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="providerModalLabel">Change Electricity Provider</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Electricity Provider <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalProvider" required>
                            <option value="">Select your provider</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= $provider['provider_id'] ?>" 
                                    <?= ($current_provider_id == $provider['provider_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($provider['provider_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="providerAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProviderBtn" onclick="saveProvider()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
                    </div>
                </div>

    <!-- Name Modal -->
    <div class="modal fade" id="nameModal" tabindex="-1" aria-labelledby="nameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nameModalLabel">Change Name</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modalFname" required 
                               value="<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modalLname" required 
                               value="<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>">
                    </div>
                    <div id="nameAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNameBtn" onclick="saveName()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Modal -->
    <div class="modal fade" id="budgetModal" tabindex="-1" aria-labelledby="budgetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="budgetModalLabel">Set Monthly Budget</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Set your monthly electricity budget to track your spending.</p>
                    <div class="mb-3">
                        <label class="form-label">Monthly Budget (₱) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="modalBudget" required 
                                   min="0" step="0.01" value="<?= $current_monthly_budget > 0 ? $current_monthly_budget : '' ?>" 
                                   placeholder="Enter monthly budget">
                        </div>
                        <small class="text-muted">Enter the amount you want to budget for electricity per month</small>
                    </div>
                    <div id="budgetAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBudgetBtn" onclick="saveBudget()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="modalPassword" 
                                   placeholder="Enter new password" minlength="8" 
                                   onkeyup="checkPasswordStrength()" autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" 
                                    id="toggleModalPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                            <div id="lengthReq"><i class="bi bi-circle"></i> 8+ characters</div>
                            <div id="caseReq"><i class="bi bi-circle"></i> Upper & lowercase</div>
                            <div id="numberReq"><i class="bi bi-circle"></i> One number</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="modalConfirmPassword" 
                                   placeholder="Re-enter new password" minlength="8" 
                                   onkeyup="checkPasswordMatch()" autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" 
                                    id="toggleModalConfirmPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="mt-1" id="passwordMatch"></div>
                    </div>
                    <div id="passwordAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePasswordBtn" onclick="savePassword()">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Phone Verification Modal -->
    <div class="modal fade" id="phoneModal" tabindex="-1" aria-labelledby="phoneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="phoneModalLabel">Verify Phone Number</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Enter your phone number with +63 country code. An OTP will be sent to verify your number.</p>
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+63</span>
                            <input type="text" class="form-control" id="phoneInput" 
                                   placeholder="912 345 6789" maxlength="13" 
                                   pattern="[0-9\s]{10,13}">
                        </div>
                        <small class="text-muted">Enter 10 digits (e.g., 912 345 6789)</small>
                    </div>
                    <div id="phoneAlert"></div>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="closePhoneModalAndOpenContacts()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sendOtpBtn" onclick="sendOTP()">
                        <i class="bi bi-send me-1"></i>Send OTP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Verification Modal -->
    <div class="modal fade" id="emailVerifyModal" tabindex="-1" aria-labelledby="emailVerifyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailVerifyModalLabel">Verify Email Address</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted text-center mb-4">Enter the 6-digit code sent to you.</p>
                    <div id="emailVerifyError"></div>
                    <form method="POST" id="emailVerifyForm" class="mt-3">
                        <input type="hidden" name="code" id="emailFullCode" required>
                        <div class="code-input-container" style="display: flex; justify-content: center; align-items: center; margin: 30px 0;">
                            <div class="code-input-group" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode1" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode2" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode3" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode4" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode5" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                                <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="emailCode6" autocomplete="off" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e9ecef; border-radius: 8px; background: white; transition: all 0.3s ease;">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeEmailVerifyModalAndOpenContacts()">Close</button>
                    <button type="button" class="btn btn-primary" id="emailVerifyBtn" onclick="submitEmailVerify()">Verify</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentCity = '<?= htmlspecialchars($user['city']) ?>';
        const currentBarangay = '<?= htmlspecialchars($user['barangay']) ?>';
        const cityCode = '<?= $city_code ?>';
        let currentField = '';
        let currentFieldType = '';

        // Handle collapse toggle for section headers
        document.addEventListener('DOMContentLoaded', function() {
            const collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    const header = this.previousElementSibling;
                    if (header) {
                        header.classList.remove('collapsed');
                    }
                });
                collapse.addEventListener('hide.bs.collapse', function() {
                    const header = this.previousElementSibling;
                    if (header) {
                        header.classList.add('collapsed');
                    }
                });
            });
        });

        // Load cities for location modal
        function loadCitiesForModal() {
            const citySelect = document.getElementById('modalCity');
            const barangaySelect = document.getElementById('modalBarangay');
            
            fetch('api_batangas.php')
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select city</option>' + 
                        data.map(city => 
                            `<option value="${city.name}" ${city.name === currentCity ? 'selected' : ''}>${city.name}</option>`
                        ).join('');
                    
                    if (currentCity && citySelect.querySelector(`option[value="${currentCity}"]`)) {
                        citySelect.value = currentCity;
                        loadBarangaysForModal(currentCity);
                    }
                })
                .catch(() => {
                    citySelect.innerHTML = '<option>Error loading cities</option>';
                });

            citySelect.addEventListener('change', () => {
                const cityName = citySelect.value;
                if (cityName) {
                    loadBarangaysForModal(cityName);
                } else {
                    barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                    barangaySelect.disabled = true;
                }
            });
        }

        function loadBarangaysForModal(code) {
            const barangaySelect = document.getElementById('modalBarangay');
                barangaySelect.innerHTML = '<option>Loading...</option>';
                barangaySelect.disabled = true;

                fetch(`api_batangas.php?city=${code}`)
                    .then(res => res.json())
                    .then(data => {
                        barangaySelect.innerHTML = '<option value="">Select barangay</option>' +
                            (data.length ? data.map(brgy => 
                                `<option value="${brgy.name}" ${brgy.name === currentBarangay ? 'selected' : ''}>${brgy.name}</option>`
                            ).join('') : '<option>No barangays found</option>');
                        barangaySelect.disabled = false;
                    })
                    .catch(() => {
                        barangaySelect.innerHTML = '<option>Error loading barangays</option>';
                        barangaySelect.disabled = false;
                    });
            }

        function openContactsModal() {
            document.getElementById('contactsAlert').innerHTML = '';
            const modal = new bootstrap.Modal(document.getElementById('contactsModal'));
            modal.show();
        }

        function openChangeModal(field, label, currentValue, type) {
            currentField = field;
            currentFieldType = type;
            document.getElementById('changeFieldName').textContent = label;
            document.getElementById('changeFieldLabel').textContent = label;
            document.getElementById('changeFieldInput').value = currentValue;
            document.getElementById('changeFieldType').value = type;
            document.getElementById('changeFieldInput').type = type;
            document.getElementById('changeAlert').innerHTML = '';
            
            const modal = new bootstrap.Modal(document.getElementById('changeModal'));
            modal.show();
        }

        function openLocationModal() {
            loadCitiesForModal();
            document.getElementById('locationAlert').innerHTML = '';
            const modal = new bootstrap.Modal(document.getElementById('locationModal'));
            modal.show();
        }

        function openNameModal() {
            document.getElementById('nameAlert').innerHTML = '';
            const modal = new bootstrap.Modal(document.getElementById('nameModal'));
            modal.show();
        }

        function openProviderModal() {
            document.getElementById('providerAlert').innerHTML = '';
            const modal = new bootstrap.Modal(document.getElementById('providerModal'));
            modal.show();
        }

        function openBudgetModal() {
            document.getElementById('budgetAlert').innerHTML = '';
            const modal = new bootstrap.Modal(document.getElementById('budgetModal'));
            modal.show();
        }

        function openPasswordModal() {
            document.getElementById('modalPassword').value = '';
            document.getElementById('modalConfirmPassword').value = '';
            document.getElementById('passwordAlert').innerHTML = '';
            document.getElementById('passwordMatch').innerHTML = '';
            checkPasswordStrength();
            
            // Setup password toggles
            setupPasswordToggle('toggleModalPassword', 'modalPassword');
            setupPasswordToggle('toggleModalConfirmPassword', 'modalConfirmPassword');
            
            const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            modal.show();
        }

        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        toggle.querySelector('i').classList.remove('bi-eye');
                        toggle.querySelector('i').classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        toggle.querySelector('i').classList.remove('bi-eye-slash');
                        toggle.querySelector('i').classList.add('bi-eye');
                    }
                });
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('modalPassword').value;
            const lengthReq = document.getElementById('lengthReq');
            const caseReq = document.getElementById('caseReq');
            const numberReq = document.getElementById('numberReq');
            
            const hasLength = password.length >= 8;
            const hasCase = /([a-z].*[A-Z])|([A-Z].*[a-z])/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            lengthReq.innerHTML = `<i class="bi ${hasLength ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> 8+ characters`;
            caseReq.innerHTML = `<i class="bi ${hasCase ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> Upper & lowercase`;
            numberReq.innerHTML = `<i class="bi ${hasNumber ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> One number`;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('modalPassword').value;
            const confirm = document.getElementById('modalConfirmPassword').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle"></i> Passwords match</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle"></i> Passwords do not match</small>';
            }
        }

        let originalEmail = '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>';
        let originalPhoneDigits = '<?= $original_phone_digits ?>';
        const hasInitialPhone = <?= !empty($user['cp_number']) ? 'true' : 'false' ?>;
        const phoneInitiallyVerified = <?= $phone_verified_status ? 'true' : 'false' ?>;

        function checkEmailChanged() {
            const emailInput = document.getElementById('contactsEmailInput');
            const emailStatusText = document.getElementById('emailStatusText');
            const emailVerifyLink = document.getElementById('emailVerifyLink');
            const currentEmail = emailInput.value.trim();
            
            if (currentEmail !== originalEmail && currentEmail !== '') {
                emailStatusText.textContent = 'Not verified';
                emailStatusText.className = 'text-danger small';
                emailStatusText.style.minWidth = '90px';
                emailStatusText.style.textAlign = 'right';
                emailVerifyLink.style.display = 'block';
            } else {
                emailStatusText.textContent = '<?= $email_verified ? 'Verified' : 'Not verified' ?>';
                emailStatusText.className = 'text-<?= $email_verified ? 'success' : 'danger' ?> small';
                emailStatusText.style.minWidth = '90px';
                emailStatusText.style.textAlign = 'right';
                emailVerifyLink.style.display = 'none';
            }
        }

        function checkPhoneChanged() {
            const phoneInput = document.getElementById('contactsPhoneInput');
            const phoneStatusText = document.getElementById('phoneStatusText');
            const phoneVerifyLinkModal = document.getElementById('phoneVerifyLinkModal');
            const digits = phoneInput.value.trim().replace(/\s/g, '');

            if (digits && digits !== originalPhoneDigits) {
                // Phone has changed and is non-empty → mark as not verified and show link
                phoneStatusText.textContent = 'Not verified';
                phoneStatusText.className = 'text-danger small';
                phoneStatusText.style.minWidth = '90px';
                phoneStatusText.style.textAlign = 'right';
                if (phoneVerifyLinkModal) {
                    phoneVerifyLinkModal.style.display = 'block';
                }
            } else {
                // Revert to original state
                if (!hasInitialPhone) {
                    phoneStatusText.textContent = 'add contact number';
                    phoneStatusText.className = 'text-secondary small';
                    phoneStatusText.style.minWidth = '90px';
                    phoneStatusText.style.textAlign = 'right';
                    if (phoneVerifyLinkModal) {
                        phoneVerifyLinkModal.style.display = 'none';
                    }
                } else {
                    phoneStatusText.textContent = phoneInitiallyVerified ? 'Verified' : 'Not verified';
                    phoneStatusText.className = 'text-' + (phoneInitiallyVerified ? 'success' : 'danger') + ' small';
                    phoneStatusText.style.minWidth = '90px';
                    phoneStatusText.style.textAlign = 'right';
                    if (phoneVerifyLinkModal) {
                        phoneVerifyLinkModal.style.display = phoneInitiallyVerified ? 'none' : 'block';
                    }
                }
            }
        }

        function openEmailVerifyModal() {
            const emailInput = document.getElementById('contactsEmailInput');
            const email = emailInput.value.trim();
            
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address first.');
                return;
            }
            
            // Close Change Contacts modal
            const contactsModal = bootstrap.Modal.getInstance(document.getElementById('contactsModal'));
            if (contactsModal) {
                contactsModal.hide();
            }
            
            document.getElementById('emailVerifyError').innerHTML = '';
            
            // Clear code inputs
            for (let i = 1; i <= 6; i++) {
                document.getElementById('emailCode' + i).value = '';
            }
            
            // Send OTP
            sendEmailOTP(email);
            
            // Show email verify modal after contacts modal is hidden
            setTimeout(() => {
                const modal = new bootstrap.Modal(document.getElementById('emailVerifyModal'));
                modal.show();
                
                // Focus first input
                setTimeout(() => {
                    document.getElementById('emailCode1').focus();
                }, 300);
            }, 300);
        }

        async function sendEmailOTP(email) {
            try {
                const response = await fetch('settings/send_email_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    document.getElementById('emailVerifyError').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to send OTP.'}</div>`;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('emailVerifyError').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
            }
        }

        async function saveContacts() {
            const emailInput = document.getElementById('contactsEmailInput');
            const phoneInput = document.getElementById('contactsPhoneInput');
            const alertDiv = document.getElementById('contactsAlert');
            const saveBtn = document.getElementById('saveContactsBtn');
            
            const email = emailInput.value.trim();
            const phoneDigits = phoneInput.value.trim().replace(/\s/g, '');
            
            if (!email) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter an email address.</div>';
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid email address.</div>';
                return;
            }
            
            let phone = '';
            if (phoneDigits) {
                if (!/^[0-9]{10}$/.test(phoneDigits)) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid 10-digit phone number.</div>';
                    return;
                }
                phone = '+63' + phoneDigits;
            }
            
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: '<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>',
                        lname: '<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>',
                        email: email,
                        cp_number: phone,
                        city: '<?= htmlspecialchars($user['city'], ENT_QUOTES) ?>',
                        barangay: '<?= htmlspecialchars($user['barangay'], ENT_QUOTES) ?>',
                        provider_id: <?= $current_provider_id ?: 0 ?>
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Contacts updated successfully!</div>';
                    originalEmail = email;
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update contacts.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function saveChange() {
            const newValue = document.getElementById('changeFieldInput').value.trim();
            const alertDiv = document.getElementById('changeAlert');
            const saveBtn = document.getElementById('saveChangeBtn');
            
            if (!newValue) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a value.</div>';
                return;
            }
            
            // Validate email format if changing email
            if (currentField === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newValue)) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid email address.</div>';
                return;
            }
            
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            try {
                const formData = {
                    [currentField]: newValue
                };

                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: currentField === 'fname' ? newValue : '<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>',
                        lname: currentField === 'lname' ? newValue : '<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>',
                        email: currentField === 'email' ? newValue : '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>',
                        city: '<?= htmlspecialchars($user['city'], ENT_QUOTES) ?>',
                        barangay: '<?= htmlspecialchars($user['barangay'], ENT_QUOTES) ?>',
                        provider_id: <?= $current_provider_id ?: 0 ?>
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function saveLocation() {
            const citySelect = document.getElementById('modalCity');
            const barangaySelect = document.getElementById('modalBarangay');
            const alertDiv = document.getElementById('locationAlert');
            const saveBtn = document.getElementById('saveLocationBtn');

            const cityName = citySelect.options[citySelect.selectedIndex].text;
            const barangay = barangaySelect.value;

            if (!cityName || cityName === 'Select city' || !barangay || barangay === 'Select barangay') {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please select both city and barangay.</div>';
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: '<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>',
                        lname: '<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>',
                        email: '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>',
                        city: cityName,
                        barangay: barangay,
                        provider_id: <?= $current_provider_id ?: 0 ?>
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Location updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update location.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function saveName() {
            const fname = document.getElementById('modalFname').value.trim();
            const lname = document.getElementById('modalLname').value.trim();
            const alertDiv = document.getElementById('nameAlert');
            const saveBtn = document.getElementById('saveNameBtn');

            if (!fname || !lname) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter both first and last name.</div>';
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: fname,
                        lname: lname,
                        email: '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>',
                        city: '<?= htmlspecialchars($user['city'], ENT_QUOTES) ?>',
                        barangay: '<?= htmlspecialchars($user['barangay'], ENT_QUOTES) ?>',
                        provider_id: <?= $current_provider_id ?: 0 ?>
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Name updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update name.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function saveProvider() {
            const providerSelect = document.getElementById('modalProvider');
            const alertDiv = document.getElementById('providerAlert');
            const saveBtn = document.getElementById('saveProviderBtn');

            const providerId = parseInt(providerSelect.value);

            if (!providerId || providerId <= 0) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please select a provider.</div>';
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            
            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: '<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>',
                        lname: '<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>',
                        email: '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>',
                        city: '<?= htmlspecialchars($user['city'], ENT_QUOTES) ?>',
                        barangay: '<?= htmlspecialchars($user['barangay'], ENT_QUOTES) ?>',
                        provider_id: providerId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Provider updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update provider.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function saveBudget() {
            const budgetInput = document.getElementById('modalBudget');
            const budgetValue = budgetInput.value.trim();
            const budget = parseFloat(budgetValue);
            const alertDiv = document.getElementById('budgetAlert');
            const saveBtn = document.getElementById('saveBudgetBtn');

            if (!budgetValue || isNaN(budget) || budget < 0) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid budget amount (must be 0 or greater).</div>';
                return;
            }
                
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

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

                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Monthly budget updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update budget.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function savePassword() {
            const password = document.getElementById('modalPassword').value;
            const confirmPassword = document.getElementById('modalConfirmPassword').value;
            const alertDiv = document.getElementById('passwordAlert');
            const saveBtn = document.getElementById('savePasswordBtn');

            if (!password) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a new password.</div>';
                    return;
                }

            if (password.length < 8) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Password must be at least 8 characters long.</div>';
                return;
            }

            if (password !== confirmPassword) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Passwords do not match.</div>';
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fname: '<?= htmlspecialchars($user['fname'], ENT_QUOTES) ?>',
                        lname: '<?= htmlspecialchars($user['lname'], ENT_QUOTES) ?>',
                        email: '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>',
                        city: '<?= htmlspecialchars($user['city'], ENT_QUOTES) ?>',
                        barangay: '<?= htmlspecialchars($user['barangay'], ENT_QUOTES) ?>',
                        provider_id: <?= $current_provider_id ?: 0 ?>,
                        password: password,
                        confirm_password: confirmPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password updated successfully!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to update password.'}</div>`;
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        function openPhoneModal() {
            // Close Change Contacts modal if open
            const contactsModal = bootstrap.Modal.getInstance(document.getElementById('contactsModal'));
            if (contactsModal) {
                contactsModal.hide();
            }
            
            setTimeout(() => {
                const modal = new bootstrap.Modal(document.getElementById('phoneModal'));
                modal.show();
                document.getElementById('phoneAlert').innerHTML = '';
                document.getElementById('phoneInput').value = '';
            }, 300);
        }

        function closePhoneModalAndOpenContacts() {
            const phoneModal = bootstrap.Modal.getInstance(document.getElementById('phoneModal'));
            if (phoneModal) {
                phoneModal.hide();
            }
            
            setTimeout(() => {
                const contactsModal = new bootstrap.Modal(document.getElementById('contactsModal'));
                contactsModal.show();
            }, 300);
        }

        function closeEmailVerifyModalAndOpenContacts() {
            const emailVerifyModal = bootstrap.Modal.getInstance(document.getElementById('emailVerifyModal'));
            if (emailVerifyModal) {
                emailVerifyModal.hide();
            }
            
            setTimeout(() => {
                const contactsModal = new bootstrap.Modal(document.getElementById('contactsModal'));
                contactsModal.show();
            }, 300);
        }

        // Email OTP input handling
        document.addEventListener('DOMContentLoaded', function() {
            const emailInputs = ['emailCode1', 'emailCode2', 'emailCode3', 'emailCode4', 'emailCode5', 'emailCode6'];
            const emailInputElements = emailInputs.map(id => document.getElementById(id));
            
            if (emailInputElements[0]) {
                emailInputElements.forEach((input, index) => {
                    input.addEventListener('input', function(e) {
                        if (!/^\d$/.test(e.target.value)) {
                            e.target.value = '';
                            return;
                        }
                        
                        if (e.target.value && index < emailInputElements.length - 1) {
                            emailInputElements[index + 1].focus();
                        }
                        
                        updateEmailFullCode();
                    });
                    
                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                        if (/^\d{6}$/.test(pastedData)) {
                            pastedData.split('').forEach((digit, idx) => {
                                if (idx < emailInputElements.length) {
                                    emailInputElements[idx].value = digit;
                                }
                            });
                            emailInputElements[emailInputElements.length - 1].focus();
                            updateEmailFullCode();
                        }
                    });
                    
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && !e.target.value && index > 0) {
                            emailInputElements[index - 1].focus();
                            emailInputElements[index - 1].value = '';
                            updateEmailFullCode();
                        }
                    });
                    
                    input.addEventListener('keypress', function(e) {
                        if (!/^\d$/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                            e.preventDefault();
                        }
                    });
                });
                
                function updateEmailFullCode() {
                    const fullCode = emailInputElements.map(input => input.value).join('');
                    document.getElementById('emailFullCode').value = fullCode;
                }
                
                async function submitEmailVerify() {
                    const fullCode = emailInputElements.map(input => input.value).join('');
                    
                    if (fullCode.length !== 6) {
                        document.getElementById('emailVerifyError').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter the complete 6-digit code.</div>';
                        emailInputElements[0].focus();
                        return;
                    }
                    
                    const emailInput = document.getElementById('contactsEmailInput');
                    const email = emailInput.value.trim();
                    const verifyBtn = document.getElementById('emailVerifyBtn');
                    const originalText = verifyBtn.innerHTML;
                    verifyBtn.disabled = true;
                    verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
                    
                    try {
                        const response = await fetch('settings/verify_email_otp.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                email: email,
                                code: fullCode
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            document.getElementById('emailVerifyError').innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Email verified successfully!</div>';
                            bootstrap.Modal.getInstance(document.getElementById('emailVerifyModal')).hide();
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            document.getElementById('emailVerifyError').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Invalid or expired code.'}</div>`;
                            emailInputElements[0].focus();
                            verifyBtn.disabled = false;
                            verifyBtn.innerHTML = originalText;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        document.getElementById('emailVerifyError').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                        verifyBtn.disabled = false;
                        verifyBtn.innerHTML = originalText;
                    }
                }
                
            }
        });

        async function sendOTP() {
            const phoneInput = document.getElementById('phoneInput');
            const phoneDigits = phoneInput.value.trim().replace(/\s/g, '');
            const alertDiv = document.getElementById('phoneAlert');
            const sendBtn = document.getElementById('sendOtpBtn');
            
            if (!phoneDigits) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a phone number.</div>';
                return;
            }
            
            if (!/^[0-9]{10}$/.test(phoneDigits)) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid 10-digit phone number.</div>';
                return;
            }
            
            const phone = '+63' + phoneDigits;
            
            const originalText = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            
            try {
                const formData = new FormData();
                formData.append('cp_number', phone);
                
                const response = await fetch('verification/sms/send_otp.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>OTP sent successfully! Redirecting to verification page...</div>';
                    
                    setTimeout(() => {
                        window.location.href = 'verification/sms/verify_otp.php';
                    }, 1500);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${result.error || 'Failed to send OTP. Please try again.'}</div>`;
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.</div>';
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        }

        // Auto-format phone input
        document.getElementById('phoneInput')?.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substring(0, 10);
            
            if (value.length > 6) {
                value = value.substring(0, 6) + ' ' + value.substring(6);
            }
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3);
            }
            
            this.value = value;
        });

    </script>
</body>
</html>
