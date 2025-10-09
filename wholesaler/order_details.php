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

// Fetch order items
$sql = "
    SELECT m.name, m.category, oi.quantity, oi.price, oi.subtotal
    FROM order_items oi
    JOIN materials m ON oi.material_id = m.material_id
    WHERE oi.order_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <h3 class="mb-4 text-center">Order Details - #<?php echo $order_id; ?></h3>

    <table class="table table-bordered text-center">
        <thead class="thead-dark">
            <tr>
                <th>Material Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Price (Rs)</th>
                <th>Subtotal (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo number_format($row['subtotal'], 2); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="text-center">
        <a href="view_orders.php" class="btn btn-secondary mt-3">Back to Orders</a>
    </div>
</div>
</body>
</html>
