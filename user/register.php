<?php
    session_start();
    require_once __DIR__ . '/../connect.php';
    require_once __DIR__ . '/verification/email/phpmailer.php';
    require_once __DIR__ . '/includes/validation.php';

    $error_message = '';
    $success_message = '';

    // Redirect if already logged in
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }

    // Get electricity providers
    $providers_query = "SELECT provider_id, provider_name FROM electricity_provider ORDER BY provider_name ASC";
    $providers_result = executeQuery($providers_query);
    $providers = [];
    if ($providers_result && $providers_result->num_rows > 0) {
        while ($row = $providers_result->fetch_assoc()) {
            $providers[] = $row;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

        $fname = trim($_POST['first_name'] ?? '');
        $lname = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cp_number = '';
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $provider_id = intval($_POST['provider_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        // Validate signup data
        $validation = validateSignupData([
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirm_password,
            'city' => $city,
            'barangay' => $barangay,
            'provider_id' => $provider_id,
            'terms' => $terms
        ], $conn);

        if (!$validation['valid']) {
            $error_message = $validation['error'];
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Generate 6-digit verification code
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // Save in session
            $_SESSION['pending_registration'] = [
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'cp_number' => $cp_number,
                'city' => $city,
                'barangay' => $barangay,
                'provider_id' => $provider_id,
                'password' => $hashed_password,
                'verification_code' => $verification_code,
                'expires_at' => $expires_at
            ];

            // Insert verification code in DB with NULL user_id
            $stmt = $conn->prepare("INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at) VALUES (NULL, 'email', ?, ?)");
            $stmt->bind_param("ss", $verification_code, $expires_at);
            $result = $stmt->execute();

            if (!$result) {
                $error_message = "Failed to create verification code. Please try again.";
            } else {
                // Store verification_id in session for better tracking
                $verification_id = $conn->insert_id;
                $_SESSION['pending_registration']['verification_id'] = $verification_id;

                // Send email
                $type = 'verification';
                sendVerificationEmail($email, $verification_code, $type);

                header('Location: verification/email/verify_email.php');
                exit;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electripid - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
        }
        .auth-container {
            max-width: 1200px;
            width: 100%;
            display: flex;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            min-height: 550px;
        }
        .form-section {
            flex: 1;
            background: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .welcome-section {
            flex: 0 0 45%;
            background: #1e88e5;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }
        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        .welcome-section p {
            font-size: 1rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        .welcome-section .login-link {
            color: #90caf9;
            text-decoration: underline;
            font-weight: 500;
        }
        .welcome-section .login-link:hover {
            color: #bbdefb;
        }
        .form-section h2 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        .form-control, .form-select {
            padding: 0.4rem 0.75rem;
            font-size: 0.9rem;
        }
        .mb-compact {
            margin-bottom: 0.75rem !important;
        }
        .logo-icon {
            background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
        }
        .eye-toggle {
            cursor: pointer;
        }
        .eye-toggle:hover {
            color: #1e88e5 !important;
        }
        .btn-signup {
            background: #1e88e5;
            border: none;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 5px;
            width: 100%;
            margin-top: 20px;
        }
        .btn-signup:hover {
            background: #1565c0;
        }
        .form-error-container {
            border: 2px solid #f8d7da;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 15px;
            background: #fff5f5;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #842029;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            .welcome-section {
                flex: 1;
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Left Section - Form (White Background) -->
        <div class="form-section">
            <h2>Sign Up</h2>
            
            <!-- Alert Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div id="formError"></div>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="register" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="row g-2 mb-compact">
                            <div class="col-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required placeholder="Name" autocomplete="given-name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required placeholder="Last name" autocomplete="family-name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-compact">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required placeholder="Email" autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="row g-2 mb-compact">
                            <div class="col-6">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-select" id="city" name="city" required>
                                    <option value="">Select city</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Barangay <span class="text-danger">*</span></label>
                                <select class="form-select" id="barangay" name="barangay" disabled required>
                                    <option value="">Select barangay</option>
                                </select>
                            </div>
                        </div>

                        <select class="form-select" name="provider_id" required>
                            <option value="">Select your provider</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= $provider['provider_id'] ?>" 
                                    <?= (isset($_POST['provider_id']) && $_POST['provider_id'] == $provider['provider_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($provider['provider_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-compact">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="password" class="form-control" name="password"  id="password" required minlength="8" placeholder="Password" onkeyup="checkPasswordStrength()" autocomplete="new-password">
                                <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                                <div id="lengthReq"><i class="bi bi-circle"></i> 8+ characters</div>
                                <div id="caseReq"><i class="bi bi-circle"></i> Upper & lowercase</div>
                                <div id="numberReq"><i class="bi bi-circle"></i> One number</div>
                            </div>
                        </div>
                        
                        <div class="mb-compact">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="password" class="form-control" name="confirm_password"  id="confirm_password" required placeholder="Re-enter password" onkeyup="checkPasswordMatch()" autocomplete="new-password">
                                <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="toggleConfirmPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="mt-1" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3 mt-2">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label small" for="terms">
                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-signup text-white" id="registerBtn">
                    SIGN UP
                </button>
            </form>
        </div>

        <div class="welcome-section">
            <h1>Let's Register<br>Account!</h1>
            <p>Enter your information to create an account.</p>
            <div>
                <p class="mb-0">Already have an account?</p>
                <a href="login.php" class="login-link">Log In</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            
            fetch('api_batangas.php')
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML += data.map(city => 
                        `<option value="${city.code}">${city.name}</option>`
                    ).join('');
                })
                .catch(() => {
                    citySelect.innerHTML = '<option>Error loading cities</option>';
                });

            citySelect.addEventListener('change', () => {
                const cityCode = citySelect.value;
                barangaySelect.innerHTML = '<option>Loading...</option>';
                barangaySelect.disabled = true;

                if (!cityCode) return;

                fetch(`api_batangas.php?city=${cityCode}`)
                    .then(res => res.json())
                    .then(data => {
                        barangaySelect.innerHTML = '<option value="">Select barangay</option>' +
                            (data.length ? data.map(brgy => 
                                `<option value="${brgy.code}">${brgy.name}</option>`
                            ).join('') : '<option>No barangays found</option>');
                        barangaySelect.disabled = false;
                    })
                    .catch(() => {
                        barangaySelect.innerHTML = '<option>Error loading barangays</option>';
                    });
            });

            function setupPasswordToggle(inputId, toggleId) {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
                const icon = toggle.querySelector('i');
                
                toggle.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                    input.focus();
                });
            }
            
            setupPasswordToggle('password', 'togglePassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');

            const firstNameField = document.querySelector('input[name="first_name"]');
            if (firstNameField && !firstNameField.value) {
                firstNameField.focus();
            }
        });

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
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.innerHTML = '';
            } else {
                const isMatch = password === confirmPassword;
                const icon = isMatch ? 'bi-check-circle' : 'bi-x-circle';
                const color = isMatch ? 'text-success' : 'text-danger';
                const text = isMatch ? 'Passwords match' : 'Passwords do not match';
                matchDiv.innerHTML = `<small class="${color}"><i class="bi ${icon} me-1"></i> ${text}</small>`;
            }
        }
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const terms = document.getElementById('terms').checked;
            const registerBtn = document.getElementById('registerBtn');
            const formError = document.getElementById('formError');

            if (formError) {
                formError.innerHTML = '';
            }
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required');
                document.querySelector('input[name="first_name"]').focus();
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                document.getElementById('password').focus();
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                if (formError) {
                    formError.innerHTML = '<div class="form-error-container"><i class="bi bi-exclamation-triangle-fill"></i><span>Passwords do not match. Please make sure both password fields are the same.</span></div>';
                    formError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    alert('Passwords do not match. Please make sure both password fields are the same.');
                }
                document.getElementById('confirm_password').focus();
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy');
                document.getElementById('terms').focus();
                return;
            }
            
            registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating account...';
            registerBtn.disabled = true;
        });
    </script>
</body>
</html>