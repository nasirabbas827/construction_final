<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$wholesaler_id = $_SESSION['id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;

// Fetch all users (customers, retailers, wholesalers)
$users_query = $conn->query("
    SELECT id, name, email, role 
    FROM users 
    WHERE id != $wholesaler_id AND (role = 'customer' OR role = 'retailer' OR role = 'wholesaler')
    ORDER BY role, name ASC
");

// If a receiver is selected
$receiver = null;
$messages = [];
if ($receiver_id > 0) {
    $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receiver = $result->fetch_assoc();
    $stmt->close();

    // Handle sending message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
        $message = trim($_POST['message']);
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $wholesaler_id, $receiver_id, $message);
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
    $msg_query->bind_param("iiii", $wholesaler_id, $receiver_id, $receiver_id, $wholesaler_id);
    $msg_query->execute();
    $messages = $msg_query->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wholesaler Chat Panel</title>
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
        .user-item {
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .user-item:hover {
            background-color: #f1f1f1;
        }
        .active-user {
            background-color: #007bff;
            color: white;
        }
        .role-badge {
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 5px;
            background-color: #6c757d;
            color: white;
        }
    </style>
    <script>
        setInterval(() => {
            const chatBox = document.querySelector(".chat-box");
            if (chatBox && window.location.search.includes("receiver_id")) {
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
            <h5>Available Users</h5>
            <hr>
            <?php while ($u = $users_query->fetch_assoc()) { ?>
                <a href="?receiver_id=<?php echo $u['id']; ?>" 
                   class="d-block user-item <?php echo ($u['id'] == $receiver_id) ? 'active-user' : ''; ?>">
                    <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                    <span class="role-badge"><?php echo ucfirst($u['role']); ?></span><br>
                    <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                </a>
            <?php } ?>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($receiver) { ?>
                <div class="chat-header">
                    <h5>Chat with <?php echo htmlspecialchars($receiver['name']); ?> (<?php echo ucfirst($receiver['role']); ?>)</h5>
                    <small><?php echo htmlspecialchars($receiver['email']); ?></small>
                </div>

                <div class="chat-box">
                    <?php while ($msg = $messages->fetch_assoc()) { ?>
                        <div class="message <?php echo ($msg['sender_id'] == $wholesaler_id) ? 'sent' : 'received'; ?>">
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
                    <h4>Select a user to start chat</h4>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
