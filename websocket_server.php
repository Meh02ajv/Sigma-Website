<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $conn;
    protected $processedMessages;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->processedMessages = [];
        global $conn;
        $this->conn = $conn;
        // Verify database connection
        if ($this->conn->connect_error) {
            echo "Database connection failed: " . $this->conn->connect_error . "\n";
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) {
            echo "Invalid message format: $msg\n";
            $this->sendError($from, "Invalid message format", $data['message_id'] ?? null);
            return;
        }

        if (isset($data['type']) && $data['type'] === 'set_user_id') {
            $from->user_id = (int)$data['user_id'];
            echo "User ID set for connection {$from->resourceId}: {$from->user_id}\n";
            return;
        }

        if (!isset($data['sender_id'], $data['recipient_id'], $data['content'], $data['sent_at'], $data['message_id'])) {
            echo "Missing required message fields: " . json_encode($data) . "\n";
            $this->sendError($from, "Missing required message fields", $data['message_id'] ?? null);
            return;
        }

        $message_id = $data['message_id'];
        if (in_array($message_id, $this->processedMessages)) {
            echo "Duplicate message ignored: $message_id\n";
            return;
        }
        $this->processedMessages[] = $message_id;
        if (count($this->processedMessages) > 1000) {
            array_shift($this->processedMessages);
        }

        $sender_id = (int)$data['sender_id'];
        $recipient_id = (int)$data['recipient_id'];
        $content = sanitize($data['content']);
        $sent_at = date('Y-m-d H:i:s', strtotime($data['sent_at']));
        if (!$sent_at || $sent_at === '1970-01-01 00:00:00') {
            echo "Invalid sent_at format: {$data['sent_at']}\n";
            $this->sendError($from, "Invalid sent_at format", $message_id);
            return;
        }

        if (empty($content)) {
            echo "Empty message content\n";
            $this->sendError($from, "Empty message content", $message_id);
            return;
        }

        // Validate sender_id and recipient_id
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE id IN (?, ?)");
        if (!$stmt) {
            echo "Failed to prepare user query: " . $this->conn->error . "\n";
            $this->sendError($from, "Database error: " . $this->conn->error, $message_id);
            return;
        }
        $stmt->bind_param("ii", $sender_id, $recipient_id);
        if (!$stmt->execute()) {
            echo "Failed to execute user query: " . $stmt->error . "\n";
            $this->sendError($from, "Database error: " . $stmt->error, $message_id);
            $stmt->close();
            return;
        }
        $result = $stmt->get_result();
        if ($result->num_rows !== 2) {
            echo "Invalid sender or recipient ID: sender=$sender_id, recipient=$recipient_id\n";
            $this->sendError($from, "Invalid sender or recipient ID", $message_id);
            $stmt->close();
            return;
        }
        $stmt->close();

        // Check if message_id column exists
        $result = $this->conn->query("SHOW COLUMNS FROM discussion LIKE 'message_id'");
        $has_message_id = $result->num_rows > 0;
        $query = $has_message_id
            ? "INSERT INTO discussion (sender_id, recipient_id, content, sent_at, is_read, message_id) VALUES (?, ?, ?, ?, 0, ?)"
            : "INSERT INTO discussion (sender_id, recipient_id, content, sent_at, is_read) VALUES (?, ?, ?, ?, 0)";

        // Store message
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            echo "Failed to prepare insert query: " . $this->conn->error . "\n";
            $this->sendError($from, "Database error: " . $this->conn->error, $message_id);
            return;
        }
        if ($has_message_id) {
            $stmt->bind_param("iisss", $sender_id, $recipient_id, $content, $sent_at, $message_id);
        } else {
            $stmt->bind_param("iiss", $sender_id, $recipient_id, $content, $sent_at);
        }
        if ($stmt->execute()) {
            echo "Message stored: from $sender_id to $recipient_id, content='$content', sent_at=$sent_at, message_id=$message_id\n";
        } else {
            $error = $stmt->error;
            echo "Failed to store message: $error\n";
            $this->sendError($from, "Failed to store message: $error", $message_id);
            $stmt->close();
            return;
        }
        $stmt->close();

        // Broadcast message
        $message = [
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'content' => $content,
            'sent_at' => $sent_at,
            'message_id' => $message_id
        ];

        foreach ($this->clients as $client) {
            if (!isset($client->user_id)) {
                echo "Skipping client {$client->resourceId}: no user_id set\n";
                continue;
            }
            if ($client->user_id === $sender_id || $client->user_id === $recipient_id) {
                try {
                    $client->send(json_encode($message));
                    echo "Message sent to user {$client->user_id}: " . json_encode($message) . "\n";
                } catch (\Exception $e) {
                    echo "Error sending message to user {$client->user_id}: {$e->getMessage()}\n";
                }
            }
        }
    }

    protected function sendError(ConnectionInterface $conn, $errorMsg, $messageId = null) {
        $error = [
            'type' => 'error',
            'error' => $errorMsg
        ];
        if ($messageId) {
            $error['message_id'] = $messageId;
        }
        try {
            $conn->send(json_encode($error));
        } catch (\Exception $e) {
            echo "Error sending error message to client {$conn->resourceId}: {$e->getMessage()}\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();
?>