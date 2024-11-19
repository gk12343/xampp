<?php
// Include Ratchet classes
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $userSessions;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userSessions = [];
    }

    // When a new client connects
    public function onOpen(ConnectionInterface $conn) {
        // For simplicity, assume user information (like username) is passed via query string
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        
        if (isset($params['username'])) {
            $username = $params['username'];
            $this->userSessions[$username] = $conn;  // Associate the connection with the username
            echo "New connection from {$username}\n";
        } else {
            echo "Connection refused: No username provided.\n";
            $conn->close();  // Close the connection if no username is provided
        }

        // Add new connection to the clients storage
        $this->clients->attach($conn);
    }

    // When a message is received from a client
    public function onMessage(ConnectionInterface $from, $msg) {
        // Optionally: handle message logic here
        echo "Message from {$from->resourceId}: $msg\n";

        // For example, send the message to the same user (echo the message back)
        $from->send("You said: $msg");
    }

    // When a connection is closed
    public function onClose(ConnectionInterface $conn) {
        // Find which username is associated with this connection and remove it
        foreach ($this->userSessions as $username => $connection) {
            if ($connection === $conn) {
                unset($this->userSessions[$username]);
                echo "Connection with {$username} closed.\n";
                break;
            }
        }

        // Remove the connection from the client storage
        $this->clients->detach($conn);
    }

    // When there is an error with the connection
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        $conn->close();
    }
}

// Create the WebSocket server
$loop = Factory::create();
$server = IoServer::factory(
    new WsServer(
        new WebSocketServer()
    ),
    8080 // WebSocket server running on port 8080
);

// Start the server
echo "WebSocket server started on port 8080\n";
$server->run();
?>
