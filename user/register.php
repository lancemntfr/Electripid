<?php
    session_start();
    require_once '../connect.php';

    $error_message = '';
    $success_message = '';

    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
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
        
        if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (!$terms) {
            $error_message = 'You must agree to the Terms of Service and Privacy Policy.';
        } elseif ($provider_id <= 0) {
            $error_message = 'Please select an electricity provider.';
        } else {
            $email = mysqli_real_escape_string($conn, $email);
            $check_query = "SELECT user_id FROM USER WHERE email = '$email'";
            $check_result = executeQuery($check_query);
            
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $error_message = 'Email address is already registered. Please use a different email or <a href="login.php">login here</a>.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $fname = mysqli_real_escape_string($conn, $fname);
                $lname = mysqli_real_escape_string($conn, $lname);
                $city = mysqli_real_escape_string($conn, $city);
                $barangay = mysqli_real_escape_string($conn, $barangay);
                $provider_id = mysqli_real_escape_string($conn, $provider_id);
                
                $insert_user_query = "INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, role, acc_status) VALUES ('$fname', '$lname', '$email', '$cp_number', '$city', '$barangay', '$hashed_password', 'user', 'active')";
                $user_result = executeQuery($insert_user_query);
                
                if ($user_result) {
                    $user_id = mysqli_insert_id($conn);
                    
                    $insert_household_query = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id', '$provider_id')";
                    $household_result = executeQuery($insert_household_query);
                    
                    if ($household_result) {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['fname'] = $fname;
                        $_SESSION['lname'] = $lname;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'user';
                        
                        $success_message = 'Registration successful! Redirecting to dashboard...';
                        
                        header('refresh:2;url=dashboard.php');
                    } else {
                        $error_message = 'Registration partially completed. Please contact support.';
                    }
                } else {
                    $error_message = 'Registration failed. Please try again later.';
                }
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
            padding: 15px;
        }
        .auth-card {
            max-width: 900px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .eye-toggle {
            cursor: pointer;
        }
        .eye-toggle:hover {
            color: #667eea !important;
        }
    </style>
</head>
<body>
    <div class="auth-card bg-white p-4 w-100">
        <!-- Header -->
        <div class="text-center mb-3">
            <div class="logo-icon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-white" style="width: 50px; height: 50px; font-size: 1.5rem;">⚡</div>
            <h3 class="text-primary mb-1">Create Your Account</h3>
            <p class="text-muted small mb-0">Join Electripid and start saving energy today!</p>
        </div>
        
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
        
        <!-- Registration Form -->
        <form method="POST" action="" id="registerForm">
            <input type="hidden" name="register" value="1">
            
            <!-- Two Column Layout -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Name Fields -->
                    <div class="row g-2 mb-compact">
                        <div class="col-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required placeholder="First name" autocomplete="given-name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required placeholder="Last name" autocomplete="family-name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-compact">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="your@email.com" autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <!-- Location Fields -->
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

                    <!-- Electricity Provider -->
                    <div class="mb-compact">
                        <label class="form-label">Electricity Provider <span class="text-danger">*</span></label>
                        <select class="form-select" name="provider_id" required>
                            <option value="">Select your provider</option>
                            <option value="1" <?php echo (isset($_POST['provider_id']) && $_POST['provider_id'] == '1') ? 'selected' : ''; ?>>Meralco</option>
                            <option value="2" <?php echo (isset($_POST['provider_id']) && $_POST['provider_id'] == '2') ? 'selected' : ''; ?>>Batelec I</option>
                            <option value="3" <?php echo (isset($_POST['provider_id']) && $_POST['provider_id'] == '3') ? 'selected' : ''; ?>>Batelec II</option>
                        </select>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Password -->
                    <div class="mb-compact">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" name="password" 
                                   id="password" required minlength="6"
                                   placeholder="Create password"
                                   onkeyup="checkPasswordStrength()"
                                   autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                            <div id="lengthReq"><i class="bi bi-circle"></i> 6+ characters</div>
                            <div id="caseReq"><i class="bi bi-circle"></i> Upper & lowercase</div>
                            <div id="numberReq"><i class="bi bi-circle"></i> One number</div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="mb-compact">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" name="confirm_password" 
                                   id="confirm_password" required
                                   placeholder="Re-enter password"
                                   onkeyup="checkPasswordMatch()"
                                   autocomplete="new-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="toggleConfirmPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="mt-1" id="passwordMatch"></div>
                    </div>
                </div>
            </div>

            <!-- Terms, Button, and Links - Centered -->
            <div class="text-center mt-3">
                <div class="form-check d-inline-block text-start mb-2">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label small" for="terms">
                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-75 py-2 mb-2" id="registerBtn">
                    <i class="bi bi-person-plus me-2"></i> Create Account
                </button>
                
                <div>
                    <p class="mb-1 small">Already have an account? 
                        <a href="login.php" class="text-decoration-none">Sign in here</a>
                    </p>
                    <p class="mb-0 small">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                        </a>
                    </p>
                </div>
            </div>
        </form>
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
            
            const hasLength = password.length >= 6;
            const hasCase = /([a-z].*[A-Z])|([A-Z].*[a-z])/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            lengthReq.innerHTML = `<i class="bi ${hasLength ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> 6+ characters`;
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
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required');
                document.querySelector('input[name="first_name"]').focus();
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                document.getElementById('password').focus();
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
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