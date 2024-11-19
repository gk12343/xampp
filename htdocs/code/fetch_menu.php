<?php
session_start();  // Start the session to access user session data

// Check if the user is logged in (this assumes you have a session variable for the username)
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Get the logged-in user's username (e.g., "ganesh")
$username = $_SESSION['username'];

// Construct the user-specific database name (e.g., user_ganesh)
$user_dbname = 'user_' . $username;

// Database connection settings
$host = 'localhost';
$port = 3406;  // Correct port
$dbname = 'login_system';  // The main database that holds user info (optional, not used in query)
$username_db = 'root';  // Database username
$password_db = '';  // Database password

try {
    // Connect to the user's specific database dynamically
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$user_dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the query to fetch data from the menu table
    $stmt = $pdo->prepare("SELECT * FROM menu");  // Assuming your table is named 'menu'
    $stmt->execute();

    // Fetch all results
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	 // Remove the 'id' field from each menu item in the array
    foreach ($menuItems as &$menuItem) {
        unset($menuItem['id']);  // Remove the 'id' field from the current menu item
		
		 // Ensure the 'price' field is a number (float or integer)
        if (isset($menuItem['Price'])) {
            // Convert price to a numeric type (float or int)
            $menuItem['Price'] = (int)$menuItem['Price'];  // Convert to float if you want decimals
			
			
            // Or, if you want to remove decimals, use (int)$menuItem['price']
        }
		
		
    }

    // Return the results as JSON
    header('Content-Type: application/json');
    echo json_encode($menuItems);

} catch (PDOException $e) {
    // Handle errors (e.g., database connection failure)
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
