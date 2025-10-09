<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];

// flash messages carried through redirects
if (isset($_SESSION['bulk_success'])) {
    $success = $_SESSION['bulk_success'];
    unset($_SESSION['bulk_success']);
}
if (isset($_SESSION['bulk_error'])) {
    $error = $_SESSION['bulk_error'];
    unset($_SESSION['bulk_error']);
}

/*
 Determine material_id:
 - Prefer POST (form submission includes hidden material_id)
 - Otherwise use GET (link from materials list)
*/
$material_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['material_id'])) {
    $material_id = intval($_POST['material_id']);
} elseif (isset($_GET['material_id'])) {
    $material_id = intval($_GET['material_id']);
}

// If material_id present, fetch material info (optional; used to show name in the form)
$material = null;
if ($material_id > 0) {
    $m_stmt = $conn->prepare("SELECT material_id, name FROM materials WHERE material_id = ?");
    $m_stmt->bind_param("i", $material_id);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result();
    $material = $m_res->fetch_assoc();
    $m_stmt->close();
}

/* =========================
   HANDLE NEW ORDER (POST)
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['place_order'])) {
    $quantity = floatval($_POST['quantity']);
    // material_id should be available from hidden input or GET
    if ($material_id <= 0) {
        $_SESSION['bulk_error'] = "No material selected. Please select a material from the dashboard.";
        $redirect = 'bulk_order.php';
        header("Location: $redirect");
        exit;
    }

    if ($quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO bulk_orders (customer_id, material_id, quantity, status) VALUES (?, ?, ?, 'open')");
        $stmt->bind_param("iid", $customer_id, $material_id, $quantity);
        if ($stmt->execute()) {
            // create notification
            $notif_title = "New Bulk Order";
            $notif_msg = "Customer ID $customer_id placed a bulk order for material ID $material_id (qty: $quantity).";
            $n_stmt = $conn->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
            $n_stmt->bind_param("ss", $notif_title, $notif_msg);
            $n_stmt->execute();
            $n_stmt->close();

            $_SESSION['bulk_success'] = "Bulk order placed successfully!";
        } else {
            $_SESSION['bulk_error'] = "Error placing order. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['bulk_error'] = "Please enter a valid quantity.";
    }

    // Redirect to avoid form resubmission
    $redirect = 'bulk_order.php' . ($material_id > 0 ? '?material_id=' . $material_id : '');
    header("Location: $redirect");
    exit;
}

/* =========================
   HANDLE DELETE ORDER (GET)
   ========================= */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM bulk_orders WHERE order_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $delete_id, $customer_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $_SESSION['bulk_success'] = "Order deleted successfully.";
    } else {
        $_SESSION['bulk_error'] = "Failed to delete order or unauthorized.";
    }
    $stmt->close();

    $redirect = 'bulk_order.php' . ($material_id > 0 ? '?material_id=' . $material_id : '');
    header("Location: $redirect");
    exit;
}

/* =========================
   HANDLE STATUS TOGGLE (GET)
   ========================= */
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['update_status']);
    // toggle only for customer's own order
    $stmt = $conn->prepare("UPDATE bulk_orders SET status = IF(status='open','closed','open') WHERE order_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $_SESSION['bulk_success'] = "Order status updated.";
    } else {
        $_SESSION['bulk_error'] = "Failed to update status or unauthorized.";
    }
    $stmt->close();

    $redirect = 'bulk_order.php' . ($material_id > 0 ? '?material_id=' . $material_id : '');
    header("Location: $redirect");
    exit;
}

/* =========================
   FETCH ALL CUSTOMER ORDERS
   ========================= */
$order_sql = $conn->prepare("
    SELECT bo.*, m.name AS material_name
    FROM bulk_orders bo
    JOIN materials m ON bo.material_id = m.material_id
    WHERE bo.customer_id = ?
    ORDER BY bo.created_at DESC
");
$order_sql->bind_param("i", $customer_id);
$order_sql->execute();
$orders = $order_sql->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Orders</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Manage Bulk Orders</h3>

            <?php if (!empty($success)) { echo "<div class='alert alert-success'>$success</div>"; } ?>
            <?php if (!empty($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>

            <!-- Add Order -->
            <?php if ($material) { ?>
                <form method="POST" class="mb-5">
                    <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                    <div class="form-group">
                        <label><strong>Material:</strong></label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($material['name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label><strong>Quantity (in units):</strong></label>
                        <input type="number" name="quantity" step="0.01" class="form-control" required>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
                </form>
            <?php } else { ?>
                <div class="alert alert-info">
                    No material selected. To place a bulk order click the <strong>Bulk Order</strong> button from a material page.
                    <a href="dashboard.php" class="btn btn-sm btn-secondary ml-2">Browse Materials</a>
                </div>
            <?php } ?>

            <h4>Your Bulk Orders</h4>
            <?php if ($orders->num_rows > 0) { ?>
                <table class="table table-bordered mt-3">
                    <thead class="thead-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Material</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $orders->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['material_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['status'] === 'open' ? 'warning' : 'success'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="?update_status=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-info">Toggle Status</a>
                                    <a href="?delete_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this order?');">Delete</a>
                                    <a href="view_bids.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-success">View Bids</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info mt-3">You have not placed any bulk orders yet.</div>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
