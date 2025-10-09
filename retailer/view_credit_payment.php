<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}

// Fetch credit payments for this order
$stmt = $conn->prepare("
    SELECT cp.credit_id, cp.total_credit, u.name AS supplier_name
    FROM credit_payments cp
    JOIN orders o ON cp.order_id = o.order_id
    JOIN users u ON cp.id = u.id
    WHERE cp.order_id = ? AND o.id = ?
    ORDER BY cp.credit_id DESC
");
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$credits = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Payments for Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3 class="text-center mb-4">Credit Payments for Order #<?php echo $order_id; ?></h3>

    <?php if ($credits->num_rows > 0) { ?>
        <table class="table table-bordered table-hover text-center">
            <thead class="thead-dark">
                <tr>
                    <th>Credit ID</th>
                    <th>Supplier</th>
                    <th>Total Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $credits->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['credit_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td>Rs. <?php echo number_format($row['total_credit'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="alert alert-info text-center">No credit payments found for this order.</div>
    <?php } ?>

    <div class="text-center mt-3">
        <a href="view_orders.php" class="btn btn-secondary">Back to Orders</a>
    </div>
</div>

</body>
</html>
