<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Order ID not specified.");
}

$order_id = intval($_GET['id']);

// Fetch order info
$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT o.*, u.name AS customer_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.id = u.id
    WHERE o.order_id = $order_id
"));

// Fetch order items
$items = mysqli_query($conn, "
    SELECT oi.*, m.name AS material_name
    FROM order_items oi
    JOIN materials m ON oi.material_id = m.material_id
    WHERE oi.order_id = $order_id
");

// Fetch payment info
$payments = mysqli_query($conn, "
    SELECT * FROM payments WHERE order_id = $order_id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order #<?= $order_id ?> Details</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        function printOrder() {
            var printContent = document.getElementById('printSection').innerHTML;
            var win = window.open('', '', 'height=700,width=900');
            win.document.write('<html><head><title>Print Order</title></head><body>');
            win.document.write('<h2>Order #<?= $order_id ?> Details</h2>');
            win.document.write(printContent);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
    </script>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-4" id="printSection">
        <button type="button" class="btn btn-success float-right" onclick="printOrder()">Print Orders</button>
    <h3>Order #<?= $order_id ?> Details </h3>
    <hr>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
    <p><strong>Location:</strong> <?= htmlspecialchars($order['location']) ?></p>
    <p><strong>Order Status:</strong> <?= ucfirst($order['order_status']) ?></p>
    <p><strong>Payment Status:</strong> <?= ucfirst($order['payment_status']) ?></p>
    <p><strong>Order Date:</strong> <?= date('Y-m-d', strtotime($order['created_at'])) ?></p>

    <h5 class="mt-4">Order Items</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Material</th>
                <th>Quantity</th>
                <th>Price (Rs.)</th>
                <th>Subtotal (Rs.)</th>
            </tr>
        </thead>
        <tbody>
        <?php while($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?= htmlspecialchars($item['material_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['price'], 2) ?></td>
                <td><?= number_format($item['subtotal'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h5 class="mt-4">Payment Details</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Method</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
        <?php while($p = mysqli_fetch_assoc($payments)): ?>
            <tr>
                <td><?= ucfirst($p['payment_method']) ?></td>
                <td>Rs. <?= number_format($p['amount'], 2) ?></td>
                <td><?= date('Y-m-d', strtotime($p['payment_date'])) ?></td>
                <td>
                    <?php if ($p['receipt']): ?>
                        <a href="<?= htmlspecialchars($p['receipt']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h4 class="text-right mt-3">Total Amount: Rs. <?= number_format($order['total_amount'], 2) ?></h4>
</div>


</body>
</html>
