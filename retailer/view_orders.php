<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];
$msg = "";

// Handle Delete Order (only if pending + processing)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $check_sql = "SELECT * FROM orders WHERE order_id = ? AND id = ? AND order_status = 'processing' AND payment_status = 'pending'";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        mysqli_query($conn, "DELETE FROM orders WHERE order_id = $delete_id");
        $msg = '<div class="alert alert-success text-center">Order deleted successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger text-center">Order cannot be deleted (Already processed or paid).</div>';
    }
}

// Fetch all orders of this customer
$sql = "SELECT * FROM orders WHERE id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .order-card { border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 25px; }
        .order-header { font-weight: bold; color: #007bff; }
        .action-btns button, .action-btns a { margin-right: 5px; margin-top: 5px; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3 class="text-center mb-4">My Orders</h3>
    <?php echo $msg; ?>

    <?php if (mysqli_num_rows($result) > 0) { ?>
        <?php while ($order = mysqli_fetch_assoc($result)) { ?>
            <div class="card order-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="order-header">Order ID: #<?php echo $order['order_id']; ?></p>
                            <p><strong>Placed On:</strong> <?php echo date("d M Y h:i A", strtotime($order['created_at'])); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($order['location']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Total:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-<?php 
                                    echo ($order['order_status'] == 'processing') ? 'info' : 
                                         (($order['order_status'] == 'shipped') ? 'primary' : 
                                         (($order['order_status'] == 'delivered') ? 'success' : 'danger')); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </p>
                            <p><strong>Payment:</strong> 
                                <span class="badge badge-<?php 
                                    echo ($order['payment_status'] == 'paid') ? 'success' : 
                                         (($order['payment_status'] == 'pending') ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right action-btns">
                            <?php if ($order['order_status'] == 'processing' && $order['payment_status'] == 'pending') { ?>
                                <a href="view_orders.php?delete_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-danger btn-sm delete-btn">Delete</a>
                            <?php } ?>

                            <a href="generate_invoice.php?order_id=<?php echo $order['order_id']; ?>" 
                               class="btn btn-secondary btn-sm">Generate Invoice</a>

                            <?php if ($order['payment_status'] == 'pending') { ?>
                                <a href="pay_now.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-success btn-sm">Pay Now</a>
                            <?php } ?>

                            <a href="view_credit_payment.php?order_id=<?php echo $order['order_id']; ?>" 
                               class="btn btn-info btn-sm">View Credit Payment</a>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <hr>
                    <h6>Items in this Order:</h6>
                    <?php
                    $items_sql = "
                        SELECT oi.*, m.name AS material_name 
                        FROM order_items oi
                        JOIN materials m ON oi.material_id = m.material_id
                        WHERE oi.order_id = {$order['order_id']}
                    ";
                    $items_result = mysqli_query($conn, $items_sql);
                    if (mysqli_num_rows($items_result) > 0) {
                        echo '<table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Material</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>';
                        while ($item = mysqli_fetch_assoc($items_result)) {
                            echo "<tr>
                                <td>{$item['material_name']}</td>
                                <td>{$item['quantity']}</td>
                                <td>Rs. " . number_format($item['price'], 2) . "</td>
                                <td>Rs. " . number_format($item['subtotal'], 2) . "</td>
                            </tr>";
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p class="text-muted">No items found.</p>';
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="alert alert-info text-center">No orders found.</div>
    <?php } ?>
</div>

<script>
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this order?')) {
                e.preventDefault();
            }
        });
    });
</script>

</body>
</html>
