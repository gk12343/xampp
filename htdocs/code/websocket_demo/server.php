<?php
require dirname(__DIR__) . '\websocket_demo\vendor\autoload.php'; // Adjust path if necessary

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\App;

class WebSocketServer implements MessageComponentInterface {
    protected $clients; // Holds all connected clients
    protected $userConnections = []; // Associating userId to connections

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    // When a new connection is opened
    public function onOpen(ConnectionInterface $conn) {
        echo "New connection! ({$conn->resourceId})\n";
        // Optionally, you can store a connection in a map here if needed
    }

    // When a message is received
    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Message received: $msg\n";

        // Decode the incoming JSON message
        $data = json_decode($msg, true);

        // Check if the message contains the necessary fields
        if (isset($data['userId']) && isset($data['message'])) {
            // Store the connection for the userId if it's the first time connecting
            if (!isset($this->userConnections[$data['userId']])) {
                $this->userConnections[$data['userId']] = $from;
            }

            // Send the message to the particular user (using the userId)
            $this->sendToUser($data['userId'], $data['message']);
        }
    }

    // When a connection is closed
    public function onClose(ConnectionInterface $conn) {
        echo "Connection closed: {$conn->resourceId}\n";
        // Remove the user from the connections when the connection is closed
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                break;
            }
        }
    }

    // When an error occurs
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    // Function to send a message to a particular user
    private function sendToUser($userId, $message) {
        // Check if the userId exists in the map
        if (isset($this->userConnections[$userId])) {
            // Send the message to the connection associated with this userId
            $this->userConnections[$userId]->send(json_encode([
                'message' => $message,
                'from' => 'user ' . $userId
            ]));
        }
    }
}

// Start the WebSocket server on localhost:8080
$server = new App('localhost', 8080);
$server->route('/chat', new WebSocketServer, array('*'));
$server->run();
