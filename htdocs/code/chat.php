<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Chat</title>
    <script type="text/javascript">
        var ws;

        // Connect to the WebSocket server using the username from PHP session
        function connect() {
            // Get the username from PHP session and pass it to WebSocket
            var username = <?php echo json_encode($_SESSION['username']); ?>;

            // Establish WebSocket connection (use query parameter for username)
            ws = new WebSocket('ws://localhost:8080/chat?username=' + username);
            
            ws.onopen = function() {
                console.log('WebSocket Connected as Username: ' + username);
            };

            ws.onmessage = function(event) {
                var message = event.data;
                var messageElement = document.createElement('p');
                messageElement.textContent = message;
                document.getElementById('messages').appendChild(messageElement);
            };

            ws.onerror = function(error) {
                console.error('WebSocket Error: ' + error);
            };

            ws.onclose = function() {
                console.log('WebSocket Closed');
            };
        }

        // Send a message via WebSocket
        function sendMessage() {
            var message = document.getElementById('message').value;
            ws.send(message);
            document.getElementById('message').value = '';
        }

        window.onload = connect;
    </script>
</head>
<body>
    <h1>Welcome, User: <?php echo $_SESSION['username']; ?></h1>
    <div id="messages"></div>
    <input type="text" id="message" placeholder="Type a message">
    <button onclick="sendMessage()">Send</button>
</body>
</html>
