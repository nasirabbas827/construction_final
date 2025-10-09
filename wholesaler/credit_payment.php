<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$supplier_id = $_SESSION["id"];
$success = $error = "";
$edit_credit = false;
$edit_id = 0;
$total_credit = "";

// Handle Add/Edit Credit Payment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $edit_id = isset($_POST['credit_id']) ? intval($_POST['credit_id']) : 0;
    $order_id = intval($_POST['order_id']);
    $total_credit = floatval($_POST['total_credit']);

    if ($total_credit <= 0) {
        $error = "Please enter a valid credit amount.";
    } else {
        if ($edit_id > 0) {
            // Edit
            $stmt = $conn->prepare("UPDATE credit_payments SET total_credit=? WHERE credit_id=?");
            $stmt->bind_param("di", $total_credit, $edit_id);
            if ($stmt->execute()) {
                $success = "Credit payment updated successfully.";
            } else {
                $error = "Failed to update credit payment.";
            }
            $stmt->close();
        } else {
            // Add
            $stmt = $conn->prepare("INSERT INTO credit_payments (id, order_id, total_credit) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $supplier_id, $order_id, $total_credit);
            if ($stmt->execute()) {
                $success = "Credit payment added successfully.";
            } else {
                $error = "Failed to add credit payment.";
            }
            $stmt->close();
        }
    }
}

// Handle Edit request
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM credit_payments WHERE credit_id=? AND id=?");
    $stmt->bind_param("ii", $edit_id, $supplier_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $edit_credit = true;
        $row = $res->fetch_assoc();
        $total_credit = $row['total_credit'];
        $order_id = $row['order_id'];
    } else {
        $error = "Credit payment not found or unauthorized.";
    }
    $stmt->close();
}

// PRE-SELECT order if order_id is in URL for ADD mode
if (!$edit_credit && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM credit_payments WHERE credit_id=? AND id=?");
    $stmt->bind_param("ii", $delete_id, $supplier_id);
    if ($stmt->execute()) {
        $success = "Credit payment deleted successfully.";
    } else {
        $error = "Failed to delete credit payment.";
    }
    $stmt->close();
}

// Fetch all credit payments
$stmt = $conn->prepare("
    SELECT cp.*, o.total_amount, u.name AS customer_name
    FROM credit_payments cp
    JOIN orders o ON cp.order_id = o.order_id
    JOIN users u ON o.id = u.id
    WHERE cp.id = ?
    ORDER BY cp.credit_id DESC
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$credits = $stmt->get_result();
$stmt->close();

// Fetch orders to populate select dropdown
$order_stmt = $conn->prepare("
    SELECT o.order_id, u.name AS customer_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN materials m ON oi.material_id = m.material_id
    JOIN users u ON o.id = u.id
    WHERE m.supplier_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$order_stmt->bind_param("i", $supplier_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();
$order_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Payments - Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow mb-4">
        <div class="card-body">
            <h3 class="text-center mb-4"><?php echo $edit_credit ? "Edit Credit Payment" : "Add Credit Payment"; ?></h3>

            <?php if ($success) { ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>
            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>

            <form method="POST" class="mb-5">
                <input type="hidden" name="credit_id" value="<?php echo $edit_credit ? $edit_id : ''; ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Order</label>
                        <select name="order_id" class="form-control" required>
                            <option value="">Select Order</option>
                            <?php while ($o = $orders->fetch_assoc()) { ?>
                                <option value="<?php echo $o['order_id']; ?>" <?php if(isset($order_id) && $order_id == $o['order_id']) echo 'selected'; ?>>
                                    #<?php echo $o['order_id']; ?> - <?php echo htmlspecialchars($o['customer_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Total Credit</label>
                        <input type="number" step="0.01" name="total_credit" class="form-control" value="<?php echo $total_credit; ?>" required>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary"><?php echo $edit_credit ? "Update" : "Add"; ?> Credit</button>
                    <?php if ($edit_credit) { ?>
                        <a href="credit_payment.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php } ?>
                </div>
            </form>

            <h4>All Credit Payments</h4>
            <?php if ($credits->num_rows > 0) { ?>
                <table class="table table-bordered mt-3">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total Credit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $credits->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['credit_id']; ?></td>
                                <td>#<?php echo $row['order_id']; ?> (Rs. <?php echo number_format($row['total_amount'], 2); ?>)</td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td>Rs. <?php echo number_format($row['total_credit'], 2); ?></td>
                                <td>
                                    <a href="credit_payment.php?edit_id=<?php echo $row['credit_id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="credit_payment.php?delete_id=<?php echo $row['credit_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this credit payment?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info mt-3">No credit payments found.</div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
