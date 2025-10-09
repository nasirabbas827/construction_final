<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['order_id'])) {
    die("Order ID missing.");
}

$order_id = intval($_GET['order_id']);

// Fetch payment info
$sql = "
    SELECT 
        p.*, 
        u.name AS customer_name, 
        u.email 
    FROM payments p
    JOIN users u ON p.customer_id = u.id
    WHERE p.order_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Payment Details</h3>

            <?php if ($payment) { ?>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($payment['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($payment['email']); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
                <p><strong>Amount:</strong> Rs. <?php echo number_format($payment['amount'], 2); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($payment['payment_date'])); ?></p>

                <?php if (!empty($payment['receipt'])) { ?>
                    <p><strong>Receipt:</strong></p>
                    <a href="../uploads/receipt/<?php echo htmlspecialchars($payment['receipt']); ?>" 
                       class="btn btn-primary" 
                       download>
                       Download Receipt
                    </a>
                <?php } else { ?>
                    <p class="text-danger">No receipt uploaded.</p>
                <?php } ?>
            <?php } else { ?>
                <p class="text-center text-danger">No payment found for this order.</p>
            <?php } ?>

            <div class="text-center mt-3">
                <a href="view_orders.php" class="btn btn-secondary">Back to Orders</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
