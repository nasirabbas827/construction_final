<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION['id'];
$wholesaler_id = isset($_GET['wholesaler_id']) ? intval($_GET['wholesaler_id']) : 0;

// Fetch all wholesalers for sidebar
$wholesalers_query = $conn->query("SELECT id, name, email FROM users WHERE role = 'wholesaler' ORDER BY name ASC");

// If a wholesaler is selected
$wholesaler = null;
$messages = [];
if ($wholesaler_id > 0) {
    $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ? AND role = 'wholesaler'");
    $stmt->bind_param("i", $wholesaler_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wholesaler = $result->fetch_assoc();
    $stmt->close();

    // Handle sending message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
        $message = trim($_POST['message']);
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $customer_id, $wholesaler_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    // Fetch chat history
    $msg_query = $conn->prepare("
        SELECT c.*, s.name AS sender_name 
        FROM chat_messages c
        JOIN users s ON c.sender_id = s.id
        WHERE (c.sender_id = ? AND c.receiver_id = ?)
           OR (c.sender_id = ? AND c.receiver_id = ?)
        ORDER BY c.sent_at ASC
    ");
    $msg_query->bind_param("iiii", $customer_id, $wholesaler_id, $wholesaler_id, $customer_id);
    $msg_query->execute();
    $messages = $msg_query->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .chat-container {
            display: flex;
            height: 90vh;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .sidebar {
            width: 25%;
            background-color: #fff;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 15px;
        }
        .chat-area {
            flex-grow: 1;
            background-color: #fff;
            border-radius: 8px;
            margin-left: 15px;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .chat-box {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .message {
            margin: 10px 0;
            padding: 10px 15px;
            border-radius: 15px;
            display: inline-block;
            max-width: 75%;
        }
        .sent {
            background-color: #007bff;
            color: white;
            float: right;
            clear: both;
        }
        .received {
            background-color: #e9ecef;
            float: left;
            clear: both;
        }
        .chat-input {
            padding: 15px;
            border-top: 1px solid #ddd;
        }
        .wholesaler-item {
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .wholesaler-item:hover {
            background-color: #f1f1f1;
        }
        .active-wholesaler {
            background-color: #007bff;
            color: white;
        }
    </style>
    <script>
        setInterval(() => {
            const chatBox = document.querySelector(".chat-box");
            if (chatBox && window.location.search.includes("wholesaler_id")) {
                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(html, "text/html");
                        const newChat = newDoc.querySelector(".chat-box").innerHTML;
                        chatBox.innerHTML = newChat;
                    });
            }
        }, 5000);
    </script>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container-fluid">
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h5>Wholesalers</h5>
            <hr>
            <?php while ($w = $wholesalers_query->fetch_assoc()) { ?>
                <a href="?wholesaler_id=<?php echo $w['id']; ?>" 
                   class="d-block wholesaler-item <?php echo ($w['id'] == $wholesaler_id) ? 'active-wholesaler' : ''; ?>">
                    <strong><?php echo htmlspecialchars($w['name']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($w['email']); ?></small>
                </a>
            <?php } ?>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($wholesaler) { ?>
                <div class="chat-header">
                    <h5>Chat with <?php echo htmlspecialchars($wholesaler['name']); ?> (<?php echo ucfirst($wholesaler['role']); ?>)</h5>
                    <small><?php echo htmlspecialchars($wholesaler['email']); ?></small>
                </div>

                <div class="chat-box">
                    <?php while ($msg = $messages->fetch_assoc()) { ?>
                        <div class="message <?php echo ($msg['sender_id'] == $customer_id) ? 'sent' : 'received'; ?>">
                            <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong><br>
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?><br>
                            <small class="text-muted" style="font-size: 11px;"><?php echo date('d M Y, h:i A', strtotime($msg['sent_at'])); ?></small>
                        </div>
                    <?php } ?>
                </div>

                <form method="POST" class="chat-input d-flex">
                    <input type="text" name="message" class="form-control mr-2" placeholder="Type your message..." required>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            <?php } else { ?>
                <div class="d-flex justify-content-center align-items-center flex-column h-100 text-muted">
                    <h4>Select a wholesaler to start chat</h4>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
