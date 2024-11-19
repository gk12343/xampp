<?php
session_start(); // Start the session to identify the logged-in user

// Database connection settings
$host = 'localhost';
$port = 3406;  // Correct port
$main_dbname = 'login_system'; // The main database that holds user info
$username = 'root';
$password = '';

try {
    // Connect to the main database (where user info is stored)
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$main_dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the user is logged in
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    // Get the logged-in user's username
    $user_dbname = 'user_' . $_SESSION['username']; // Example: user_ganesh1
    $menuData = json_decode(file_get_contents('php://input'), true)['menu']; // Get menu data from the request

    // Debugging: Log the menu data to see what is being received
    if (!$menuData) {
        echo json_encode(['success' => false, 'message' => 'No menu data received']);
        exit;
    }

    // Check if the menu data is valid
    foreach ($menuData as $menuItem) {
        // Make sure Menu and Price are provided
        if (empty($menuItem['Menu']) || empty($menuItem['price'])) {
            echo json_encode(['success' => false, 'message' => 'Menu or price cannot be empty']);
            exit;
        }
    }

    // Connect to the user's specific database
    $userDbConnection = new PDO("mysql:host=$host;port=$port;dbname=$user_dbname", $username, $password);
    $userDbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the SQL query to insert menu items into the user's menu table
    $stmt = $userDbConnection->prepare("INSERT INTO menu (Menu, Submenu, Price) VALUES (:Menu, :Submenu, :Price)");

    // Start a transaction for atomicity
    $userDbConnection->beginTransaction();

    // Loop through the menu items and insert them into the user's menu table
    foreach ($menuData as $menuItem) {
        // Debugging: Check values before executing the query
        if (empty($menuItem['Menu'])) {
            echo json_encode(['success' => false, 'message' => 'Menu name is missing for some items']);
            exit;
        }

        // Bind parameters for the SQL insert
        $stmt->bindParam(':Menu', $menuItem['Menu']);
        $stmt->bindParam(':Submenu', $menuItem['Submenu']);
        $stmt->bindParam(':Price', $menuItem['price']);
        $stmt->execute();
    }

    // Commit the transaction
    $userDbConnection->commit();

    // Return a success response
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // If there's an error with the database connection or query, display it
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
