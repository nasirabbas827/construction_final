<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];
$msg = "";

if (!isset($_GET['order_id'])) {
    die("Order not specified.");
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$order_sql = "SELECT * FROM orders WHERE order_id = ? AND id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die("Invalid order or unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST["payment_method"];
    $amount = $order['total_amount'];
    $receipt_path = null;

    // Handle receipt upload
    if (!empty($_FILES["receipt"]["name"])) {
        $target_dir = "../uploads/receipts/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . "_" . basename($_FILES["receipt"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
            $receipt_path = "../uploads/receipts/" . $file_name;
        }
    }

    // Insert payment record
    $insert_sql = "INSERT INTO payments (order_id, customer_id, payment_method, amount, receipt) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt2, "iisss", $order_id, $customer_id, $payment_method, $amount, $receipt_path);

    if (mysqli_stmt_execute($stmt2)) {
        // Update order payment status
        $update_sql = "UPDATE orders SET payment_status = 'paid' WHERE order_id = ?";
        $stmt3 = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt3, "i", $order_id);
        mysqli_stmt_execute($stmt3);

        $msg = '<div class="alert alert-success text-center">Payment recorded successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger text-center">Error processing payment. Please try again.</div>';
    }

    mysqli_stmt_close($stmt2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Now - Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Pay for Order #<?php echo $order_id; ?></h3>
            <?php echo $msg; ?>

            <div class="mb-4">
                <h5>Order Summary</h5>
                <p><strong>Total Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Current Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                <p><strong>Order Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="upi">UPI</option>
                            <option value="cod">Cash on Delivery</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Amount</label>
                        <input type="text" class="form-control" value="<?php echo number_format($order['total_amount'], 2); ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Payment Receipt (optional)</label>
                    <input type="file" name="receipt" class="form-control-file">
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success">Submit Payment</button>
                    <a href="view_orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
