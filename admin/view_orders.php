<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Date filter
$where = "";
if (isset($_GET['from_date']) && isset($_GET['to_date']) && $_GET['from_date'] != "" && $_GET['to_date'] != "") {
    $from = $_GET['from_date'];
    $to = $_GET['to_date'];
    $where = "WHERE DATE(o.created_at) BETWEEN '$from' AND '$to'";
}

$query = "
    SELECT o.*, u.name AS customer_name,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count,
    (SELECT COUNT(*) FROM payments p WHERE p.order_id = o.order_id) AS payment_count
    FROM orders o
    JOIN users u ON o.id = u.id
    $where
    ORDER BY o.order_id DESC
";
$result = mysqli_query($conn, $query);

// Total order stats
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'];
$total_paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE payment_status='paid'"))['total'];
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE payment_status='pending'"))['total'];
$total_failed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE payment_status='failed'"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Orders - Admin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        function printTable() {
            var printContent = document.getElementById('orderTable').outerHTML;
            var win = window.open('', '', 'height=700,width=900');
            win.document.write('<html><head><title>Print Orders</title></head><body>');
            win.document.write('<h2>Orders Report</h2>');
            win.document.write(printContent);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
    </script>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">All Orders</h2>

    <div class="row mb-3">
        <div class="col-md-3"><strong>Total Orders:</strong> <?= $total_orders ?></div>
        <div class="col-md-3 text-success"><strong>Paid:</strong> <?= $total_paid ?></div>
        <div class="col-md-3 text-warning"><strong>Pending:</strong> <?= $total_pending ?></div>
        <div class="col-md-3 text-danger"><strong>Failed:</strong> <?= $total_failed ?></div>
    </div>

    <form method="GET" class="form-inline mb-3">
        <label class="mr-2">From:</label>
        <input type="date" name="from_date" class="form-control mr-3" value="<?= $_GET['from_date'] ?? '' ?>">
        <label class="mr-2">To:</label>
        <input type="date" name="to_date" class="form-control mr-3" value="<?= $_GET['to_date'] ?? '' ?>">
        <button type="submit" class="btn btn-primary mr-2">Search</button>
        <button type="button" class="btn btn-success" onclick="printTable()">Print Orders</button>
    </form>

    <table class="table table-bordered table-striped" id="orderTable">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
                <th>Order Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td>Rs. <?= number_format($row['total_amount'], 2) ?></td>
                <td>
                    <span class="badge badge-<?= 
                        $row['payment_status'] == 'paid' ? 'success' : 
                        ($row['payment_status'] == 'pending' ? 'warning' : 'danger') ?>">
                        <?= ucfirst($row['payment_status']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge-info"><?= ucfirst($row['order_status']) ?></span>
                </td>
                <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                <td>
                    <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
