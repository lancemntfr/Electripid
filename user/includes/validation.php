<?php
    /**
     * Validation functions for user registration and password reset
     */

    /**
     * Validates password strength and match
     * Validates: minimum length (8), uppercase & lowercase letters, at least one number
     * @param string $password The password to validate
     * @param string $confirm_password The confirmation password
     * @return array Returns array with 'valid' boolean and 'error' string
     */
    function validatePassword($password, $confirm_password) {
        // Check if passwords are empty
        if (empty($password) || empty($confirm_password)) {
            return ['valid' => false, 'error' => 'All fields are required.'];
        }

        // Check password length
        if (strlen($password) < 8) {
            return ['valid' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        // Check for uppercase and lowercase letters
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        if (!$hasUppercase || !$hasLowercase) {
            return ['valid' => false, 'error' => 'Password must contain both uppercase and lowercase letters.'];
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one number.'];
        }

        // Check if passwords match
        if ($password !== $confirm_password) {
            return ['valid' => false, 'error' => 'Passwords do not match.'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Validates signup form data
     * @param array $data Array containing: fname, lname, email, password, confirm_password, city, barangay, provider_id, terms
     * @param mysqli $conn Database connection for email uniqueness check
     * @return array Returns array with 'valid' boolean and 'error' string
     */
    function validateSignupData($data, $conn) {
        $fname = trim($data['fname'] ?? '');
        $lname = trim($data['lname'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        $city = trim($data['city'] ?? '');
        $barangay = trim($data['barangay'] ?? '');
        $provider_id = intval($data['provider_id'] ?? 0);
        $terms = isset($data['terms']);

        // Check required fields
        if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
            return ['valid' => false, 'error' => 'Please fill in all required fields.'];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Please enter a valid email address.'];
        }

        // Validate password
        $passwordValidation = validatePassword($password, $confirm_password);
        if (!$passwordValidation['valid']) {
            return $passwordValidation;
        }

        // Check terms agreement
        if (!$terms) {
            return ['valid' => false, 'error' => 'You must agree to the Terms of Service and Privacy Policy.'];
        }

        // Check provider selection
        if ($provider_id <= 0) {
            return ['valid' => false, 'error' => 'Please select an electricity provider.'];
        }

        // Check email uniqueness
        $email = mysqli_real_escape_string($conn, $email);
        $check_query = "SELECT user_id FROM USER WHERE email = '$email'";
        $check_result = executeQuery($check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            return ['valid' => false, 'error' => 'This email address is already taken. You cannot receive a verification code for an email that is already registered. Please use a different email address or <a href="login.php">login here</a>.'];
        }

        return ['valid' => true, 'error' => ''];
    }
?>
