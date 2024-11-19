<?php

session_start();

// Database connection settings for the main database (where user info is stored)
$host = 'localhost';
$port = 3406;
$main_dbname = 'login_system'; // Main database containing user info
$username = 'root';
$password = '';

try {
    // Connect to the main database (where users are stored)
    //echo "Connecting to the main database...\n"; // Debugging message
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$main_dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   // echo "Connected to the main database successfully.\n"; // Debugging message

    // Get user registration data
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get data from the form (ensure you use POST method to submit the form)
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hash the password
        $email = $_POST['email'];

        echo "Checking if the username exists...\n"; // Debugging message

        // Check if the username already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "Username already taken!";
            exit;
        }

        echo "Inserting user into the database...\n"; // Debugging message

        // Insert user into the main database
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        echo "User inserted successfully.\n"; // Debugging message

        // Now create the user's specific database
        $user_dbname = 'user_' . $username;  // Example: user_ganesh1
        echo "Creating the user's database: $user_dbname\n"; // Debugging message
        $pdo->exec("CREATE DATABASE `$user_dbname`");

        // Create the 'menu' table inside the user's database with the new structure
        $pdo->exec("USE `$user_dbname`");

        // Create the 'menu' table with name, submenu, and price columns
        $createTableSQL = "CREATE TABLE IF NOT EXISTS menu (
            id INT AUTO_INCREMENT PRIMARY KEY,
            Menu VARCHAR(255) NOT NULL,
            Submenu VARCHAR(255),
            Price DECIMAL(10, 2) NOT NULL
        )";
        $pdo->exec($createTableSQL);

        echo "Menu table created successfully.\n"; // Debugging message

        // Store the user info in the session
        $_SESSION['username'] = $username;

        echo "Registration successful!";
    }

} catch (PDOException $e) {
    // If there's an error with the database connection or query, display it
    echo "Error: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form action="register.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>
