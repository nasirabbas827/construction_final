<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$supplier_id = $_SESSION["id"];

// Fetch orders related to wholesaler's materials
$sql = "
    SELECT 
        o.order_id, 
        o.id AS customer_id,
        u.name AS customer_name,
        o.total_amount,
        o.payment_status,
        o.order_status,
        o.created_at
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN materials m ON oi.material_id = m.material_id
    JOIN users u ON o.id = u.id
    WHERE m.supplier_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Orders - Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
            <h3 class="text-center mb-4">All Orders for Your Materials</h3>

            <table class="table table-bordered table-hover text-center">
                <thead class="thead-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Placed On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td>#<?php echo $row['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td>Rs. <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo ($row['payment_status'] == 'paid') ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($row['payment_status']); ?>
                            </span>
                        </td>
                        <td>
                            <select class="form-control form-control-sm order-status" data-id="<?php echo $row['order_id']; ?>">
                                <option value="processing" <?php if($row['order_status']=='processing') echo 'selected'; ?>>Processing</option>
                                <option value="shipped" <?php if($row['order_status']=='shipped') echo 'selected'; ?>>Shipped</option>
                                <option value="delivered" <?php if($row['order_status']=='delivered') echo 'selected'; ?>>Delivered</option>
                                <option value="cancelled" <?php if($row['order_status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
                            </select>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="generate_invoice.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary">Generate Receipt</a>
                            <a href="order_details.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-info">Order Details</a>
                            <a href="view_payment_details.php?order_id=<?php echo $row['order_id']; ?>" class="mt-2 btn btn-sm btn-success">View Payment</a>
                        <a href="credit_payment.php?order_id=<?php echo $row['order_id']; ?>" class="mt-2 btn btn-sm btn-warning">Credit Payment</a>
</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).on('change', '.order-status', function(){
    var order_id = $(this).data('id');
    var new_status = $(this).val();

    if(confirm("Are you sure you want to update the order status to '" + new_status + "'?")) {
        $.ajax({
            url: 'update_order_status.php',
            type: 'POST',
            data: {order_id: order_id, order_status: new_status},
            success: function(response){
                alert(response);
                location.reload();
            }
        });
    } else {
        location.reload();
    }
});
</script>

</body>
</html>
