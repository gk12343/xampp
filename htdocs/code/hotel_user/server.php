<?php
require dirname(__DIR__) . '\websocket_demo\vendor\autoload.php'; // Adjust path if necessary

// Disable deprecated warnings (temporary solution)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);


use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App;

class WebSocketServer implements MessageComponentInterface {
    protected $clients; // Holds all connected clients
    protected $userConnections = []; // Associating userId to connections
    protected $hotelConnections = []; // Associating hotelName to dashboard connections

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    // When a new connection is opened
    public function onOpen(ConnectionInterface $conn) {
        echo "New connection! ({$conn->resourceId})\n";
        
        // Wait for the first message from the client to know whether it's a hotel or user
        $conn->send(json_encode([
            'message' => 'Please register your role (user or hotel) with appropriate details.'
        ]));
    }

    // When a message is received
    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Message received: $msg\n";

        // Decode the incoming JSON message
        $data = json_decode($msg, true);

        // Check if the message is from a hotel registration request
        if (isset($data['action']) && $data['action'] === 'register_hotel' && isset($data['hotel_name'])) {
            // Register the hotel connection
            $this->hotelConnections[$data['hotel_name']] = $from;
            echo "Hotel dashboard '{$data['hotel_name']}' registered.\n";
            return; // Exit as no further processing is needed for registration
        }

        // Check if the message is from a user (order request)
        if (isset($data['hotel_name']) && isset($data['order_details'])) {
            // The user has sent an order for the hotel
            $this->routeOrderToHotel($data['hotel_name'], $data['order_details'], $from);
        }
    }

    // When a connection is closed
    public function onClose(ConnectionInterface $conn) {
        echo "Connection closed: {$conn->resourceId}\n";
        
        // Remove the user or hotel from the connections when the connection is closed
        foreach ($this->hotelConnections as $hotelName => $connection) {
            if ($connection === $conn) {
                unset($this->hotelConnections[$hotelName]);
                break;
            }
        }

        // Also remove users from the connections
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

    // Function to send the order to the hotel dashboard
    private function routeOrderToHotel($hotelName, $orderDetails, ConnectionInterface $from) {
        if (isset($this->hotelConnections[$hotelName])) {
            // Send the order to the hotel dashboard
            $this->hotelConnections[$hotelName]->send(json_encode([
				'hotel_name' =>$hotelName,
                'order_details' => $orderDetails,
                'message' => "New order for table: {$orderDetails['table_number']}",
                'user_connection' => $from->resourceId // Optionally include user connection id for easier communication
            ]));
            echo "Order routed to hotel: {$hotelName}\n";
        } else {
            // Hotel not found, send error to user
            $from->send(json_encode([
                'message' => "Hotel {$hotelName} not available at the moment. Please try again later."
            ]));
            echo "Error: Hotel {$hotelName} not registered\n";
        }
    }
}

// Start the WebSocket server on localhost:8080
$server = new App('localhost', 8080);
$server->route('/', new WebSocketServer, array('*'));
$server->run();
