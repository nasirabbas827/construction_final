<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];

// Validate order_id
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Invalid request.");
}
$order_id = intval($_GET['order_id']);

// Verify that this order belongs to the logged-in customer
$verify_stmt = $conn->prepare("SELECT * FROM bulk_orders WHERE order_id = ? AND customer_id = ?");
$verify_stmt->bind_param("ii", $order_id, $customer_id);
$verify_stmt->execute();
$order_res = $verify_stmt->get_result();
if ($order_res->num_rows === 0) {
    die("Unauthorized access to order.");
}
$order = $order_res->fetch_assoc();
$verify_stmt->close();

// Handle bid status update (accept/reject)
if (isset($_GET['update_bid_status']) && isset($_GET['status'])) {
    $bid_id = intval($_GET['update_bid_status']);
    $new_status = $_GET['status'] === 'accept' ? 'accepted' : 'rejected';

    // Only one accepted bid per order
    if ($new_status === 'accepted') {
        // Reject all other bids for this order first
        $conn->query("UPDATE supplier_bids SET status = 'rejected' WHERE bulk_order_id = $order_id");
    }

    $stmt = $conn->prepare("UPDATE supplier_bids SET status = ? WHERE bid_id = ? AND bulk_order_id = ?");
    $stmt->bind_param("sii", $new_status, $bid_id, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['bid_success'] = "Bid status updated successfully.";
    } else {
        $_SESSION['bid_error'] = "Failed to update bid status.";
    }
    $stmt->close();

    header("Location: view_bids.php?order_id=$order_id");
    exit;
}

// Fetch all bids for this order
$bids_stmt = $conn->prepare("
    SELECT sb.*, u.name AS supplier_name 
    FROM supplier_bids sb
    JOIN users u ON sb.supplier_id = u.id
    WHERE sb.bulk_order_id = ?
    ORDER BY sb.bid_price ASC
");
$bids_stmt->bind_param("i", $order_id);
$bids_stmt->execute();
$bids = $bids_stmt->get_result();
$bids_stmt->close();

// Flash messages
$success = $_SESSION['bid_success'] ?? '';
$error = $_SESSION['bid_error'] ?? '';
unset($_SESSION['bid_success'], $_SESSION['bid_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Bids</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="mb-4">Bids for Order #<?php echo $order_id; ?></h3>
            <p><strong>Material:</strong> <?php echo htmlspecialchars($order['material_id']); ?> | 
               <strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?></p>

            <?php if (!empty($success)) { ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>
            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>

            <?php if ($bids->num_rows > 0) { ?>
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Bid ID</th>
                            <th>Supplier Name</th>
                            <th>Bid Price</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bid = $bids->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $bid['bid_id']; ?></td>
                                <td><?php echo htmlspecialchars($bid['supplier_name']); ?></td>
                                <td>Rs. <?php echo number_format($bid['bid_price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                        echo $bid['status'] === 'pending' ? 'warning' :
                                             ($bid['status'] === 'accepted' ? 'success' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($bid['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($bid['created_at'])); ?></td>
                                <td>
                                    <?php if ($bid['status'] === 'pending') { ?>
                                        <a href="?order_id=<?php echo $order_id; ?>&update_bid_status=<?php echo $bid['bid_id']; ?>&status=accept"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Accept this bid?');">Accept</a>
                                        <a href="?order_id=<?php echo $order_id; ?>&update_bid_status=<?php echo $bid['bid_id']; ?>&status=reject"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Reject this bid?');">Reject</a>
                                    <?php } else { ?>
                                        <em>No actions</em>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info">No bids have been placed yet for this order.</div>
            <?php } ?>

            <a href="bulk_order.php" class="btn btn-secondary mt-3">Back to Orders</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
