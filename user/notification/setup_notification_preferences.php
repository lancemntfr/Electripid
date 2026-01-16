<?php
require_once __DIR__ . '/../../connect.php';

function setupNotificationPreferencesTable() {
    global $conn;

    $create_table_query = "
        CREATE TABLE IF NOT EXISTS NOTIFICATION_PREFERENCES (
            user_id INT PRIMARY KEY,
            email_alerts BOOLEAN DEFAULT TRUE,
            sms_alerts BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES USER(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $result = executeQuery($create_table_query);

    if ($result) {
        echo "NOTIFICATION_PREFERENCES table created successfully.\n";

        // Insert default preferences for existing users
        $insert_defaults_query = "
            INSERT IGNORE INTO NOTIFICATION_PREFERENCES (user_id, email_alerts, sms_alerts)
            SELECT user_id, TRUE, TRUE FROM USER
        ";

        $insert_result = executeQuery($insert_defaults_query);

        if ($insert_result) {
            echo "Default notification preferences inserted for existing users.\n";
        } else {
            echo "Warning: Could not insert default preferences for existing users.\n";
        }

        return true;
    } else {
        echo "Error creating NOTIFICATION_PREFERENCES table: " . $conn->error . "\n";
        return false;
    }
}

// Run the setup
if (setupNotificationPreferencesTable()) {
    echo "Notification preferences setup completed successfully.\n";
} else {
    echo "Notification preferences setup failed.\n";
}
?>