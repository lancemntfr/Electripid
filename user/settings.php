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

    // Check if phone was just verified
    $phone_verified = isset($_GET['verified']) && $_GET['verified'] == '1';

    // Load household/provider data
    $household_query = "SELECT h.provider_id, p.provider_name FROM HOUSEHOLD h 
                        INNER JOIN ELECTRICITY_PROVIDER p ON h.provider_id = p.provider_id 
                        WHERE h.user_id = '$user_id_escaped'";
    $household_result = executeQuery($household_query);
    $current_provider_id = 0;
    $current_provider_name = '';
    
    if ($household_result && mysqli_num_rows($household_result) > 0) {
        $household = mysqli_fetch_assoc($household_result);
        $current_provider_id = $household['provider_id'];
        $current_provider_name = $household['provider_name'];
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
            padding: 20px 0;
        }
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .phone-verify-btn {
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container settings-container">
        <div class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
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

            <form id="settingsForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fname" name="fname" required 
                               value="<?= htmlspecialchars($user['fname']) ?>" autocomplete="given-name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lname" name="lname" required 
                               value="<?= htmlspecialchars($user['lname']) ?>" autocomplete="family-name">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?= htmlspecialchars($user['email']) ?>" autocomplete="email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user['cp_number'] ?? '') ?>" 
                                   placeholder="No phone number" readonly>
                            <button type="button" class="btn btn-primary phone-verify-btn" onclick="openPhoneModal()">
                                <i class="bi bi-telephone me-1"></i><?= !empty($user['cp_number']) ? 'Update' : 'Add' ?>
                            </button>
                        </div>
                        <small class="text-muted">Click to add or update your phone number via SMS verification</small>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">City <span class="text-danger">*</span></label>
                        <select class="form-select" id="city" name="city" required>
                            <option value="">Select city</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Barangay <span class="text-danger">*</span></label>
                        <select class="form-select" id="barangay" name="barangay" required>
                            <option value="">Select barangay</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Electricity Provider <span class="text-danger">*</span></label>
                        <select class="form-select" id="provider_id" name="provider_id" required>
                            <option value="">Select your provider</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= $provider['provider_id'] ?>" 
                                    <?= ($current_provider_id == $provider['provider_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($provider['provider_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Leave blank to keep current password" minlength="8" 
                                   onkeyup="checkPasswordStrength()" autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" 
                                    id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                            <div id="lengthReq"><i class="bi bi-circle"></i> 8+ characters</div>
                            <div id="caseReq"><i class="bi bi-circle"></i> Upper & lowercase</div>
                            <div id="numberReq"><i class="bi bi-circle"></i> One number</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Re-enter new password" minlength="8" 
                                   onkeyup="checkPasswordMatch()" autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" 
                                    id="toggleConfirmPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="mt-1" id="passwordMatch"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="window.location.href='dashboard.php'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Phone Verification Modal -->
    <div class="modal fade" id="phoneModal" tabindex="-1" aria-labelledby="phoneModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="phoneModalLabel">Verify Phone Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="sendOtpBtn" onclick="sendOTP()">
                        <i class="bi bi-send me-1"></i>Send OTP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentCity = '<?= htmlspecialchars($user['city']) ?>';
        const currentBarangay = '<?= htmlspecialchars($user['barangay']) ?>';
        const cityCode = '<?= $city_code ?>';

        // Load cities
        document.addEventListener('DOMContentLoaded', function() {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            
            fetch('api_batangas.php')
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select city</option>' + 
                        data.map(city => 
                            `<option value="${city.code}" ${city.name === currentCity ? 'selected' : ''}>${city.name}</option>`
                        ).join('');
                    
                    if (cityCode && citySelect.querySelector(`option[value="${cityCode}"]`)) {
                        citySelect.value = cityCode;
                        loadBarangays(cityCode);
                    }
                })
                .catch(() => {
                    citySelect.innerHTML = '<option>Error loading cities</option>';
                });

            citySelect.addEventListener('change', () => {
                const code = citySelect.value;
                if (code) {
                    loadBarangays(code);
                } else {
                    barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                    barangaySelect.disabled = true;
                }
            });

            function loadBarangays(code) {
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

            // Password toggle
            setupPasswordToggle('togglePassword', 'password');
            setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
        });

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
            const password = document.getElementById('password').value;
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
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
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

        function openPhoneModal() {
            const modal = new bootstrap.Modal(document.getElementById('phoneModal'));
            modal.show();
            // Clear previous alerts
            document.getElementById('phoneAlert').innerHTML = '';
            document.getElementById('phoneInput').value = '';
        }

        async function sendOTP() {
            const phoneInput = document.getElementById('phoneInput');
            const phoneDigits = phoneInput.value.trim().replace(/\s/g, '');
            const alertDiv = document.getElementById('phoneAlert');
            const sendBtn = document.getElementById('sendOtpBtn');
            
            // Validate phone number
            if (!phoneDigits) {
                alertDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a phone number.</div>';
                return;
            }
            
            // Validate Philippine phone number (10 digits)
            if (!/^[0-9]{10}$/.test(phoneDigits)) {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Please enter a valid 10-digit phone number.</div>';
                return;
            }
            
            // Format phone with +63
            const phone = '+63' + phoneDigits;
            
            // Disable button and show loading
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
                    
                    // Redirect to OTP verification page
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
            let value = this.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 10) value = value.substring(0, 10); // Limit to 10 digits
            
            // Format: XXX XXX XXXX
            if (value.length > 6) {
                value = value.substring(0, 6) + ' ' + value.substring(6);
            }
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3);
            }
            
            this.value = value;
        });

        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const saveBtn = document.getElementById('saveBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validate password if provided
            if (password || confirmPassword) {
                if (password.length < 8) {
                    showAlert('Password must be at least 8 characters long.', 'danger');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                    return;
                }
                
                if (password !== confirmPassword) {
                    showAlert('Passwords do not match.', 'danger');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                    return;
                }
            }
            
            const formData = {
                fname: document.getElementById('fname').value.trim(),
                lname: document.getElementById('lname').value.trim(),
                email: document.getElementById('email').value.trim(),
                city: document.getElementById('city').options[document.getElementById('city').selectedIndex].text,
                barangay: document.getElementById('barangay').value,
                provider_id: parseInt(document.getElementById('provider_id').value),
                password: password,
                confirm_password: confirmPassword
            };
            
            try {
                const response = await fetch('settings/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message || 'Profile updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1500);
                } else {
                    showAlert(result.error || 'Failed to update profile.', 'danger');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        });

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }
    </script>
</body>
</html>
