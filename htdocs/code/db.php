<?php
// Function to connect to the database dynamically based on the username
function getDbConnection($username) {
    // Database credentials
    $host = 'localhost';
    $port = 3406;  // Use your specific port
    $main_dbname = 'login_system'; // This is where your main user info (like username, etc.) is stored
    $root_user = 'root';
    $root_password = '';

    try {
        // Connect to the main database
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$main_dbname", $root_user, $root_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user's individual database exists
        $user_dbname = 'user_' . $username;
        $pdo->exec("USE `$user_dbname`");

        return $pdo;
    } catch (PDOException $e) {
        die("Could not connect to the database: " . $e->getMessage());
    }
}
?>
