<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "wholesaler") {
    header("Location: ../index.php");
    exit();
}

$supplier_id = $_SESSION["id"];
$bid_message = "";

// Handle new bid submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $bid_price = floatval($_POST['bid_price']);

    if ($bid_price > 0) {
        // Check if this wholesaler already placed a bid
        $check = $conn->prepare("SELECT * FROM supplier_bids WHERE supplier_id = ? AND bulk_order_id = ?");
        $check->bind_param("ii", $supplier_id, $order_id);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $bid_message = "<div class='alert alert-warning'>You have already placed a bid for this order.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO supplier_bids (supplier_id, bulk_order_id, bid_price) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $supplier_id, $order_id, $bid_price);
            if ($stmt->execute()) {
                $bid_message = "<div class='alert alert-success'>Bid placed successfully!</div>";
            } else {
                $bid_message = "<div class='alert alert-danger'>Error placing bid.</div>";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $bid_message = "<div class='alert alert-danger'>Please enter a valid bid price.</div>";
    }
}

// Fetch all open bulk orders
$order_sql = "
    SELECT bo.*, u.name AS customer_name, m.name AS material_name
    FROM bulk_orders bo
    JOIN users u ON bo.customer_id = u.id
    JOIN materials m ON bo.material_id = m.material_id
    WHERE bo.status = 'open'
    ORDER BY bo.created_at DESC
";
$orders = $conn->query($order_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Bulk Orders & Bids</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Open Bulk Orders</h3>

            <?php echo $bid_message; ?>

            <?php if ($orders->num_rows > 0) { ?>
                <?php while ($order = $orders->fetch_assoc()) { ?>
                    <div class="border p-3 mb-4 rounded">
                        <h5>Order #<?php echo $order['order_id']; ?> - <?php echo htmlspecialchars($order['material_name']); ?></h5>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge badge-<?php echo $order['status'] === 'open' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>

                        <!-- Bid Form -->
                        <form method="POST" class="form-inline mb-3">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <div class="form-group mr-2">
                                <input type="number" step="0.01" name="bid_price" class="form-control" placeholder="Enter bid price" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Place Bid</button>
                        </form>

                        <!-- View Other Bids -->
                        <?php
                        $bid_sql = $conn->prepare("
                            SELECT sb.*, u.name AS supplier_name 
                            FROM supplier_bids sb
                            JOIN users u ON sb.supplier_id = u.id
                            WHERE sb.bulk_order_id = ?
                            ORDER BY sb.created_at DESC
                        ");
                        $bid_sql->bind_param("i", $order['order_id']);
                        $bid_sql->execute();
                        $bids = $bid_sql->get_result();
                        ?>

                        <h6>All Bids:</h6>
                        <?php if ($bids->num_rows > 0) { ?>
                            <table class="table table-sm table-bordered mt-2">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Bid Price</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($bid = $bids->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bid['supplier_name']); ?></td>
                                            <td><?php echo number_format($bid['bid_price'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    echo $bid['status'] == 'accepted' ? 'success' :
                                                        ($bid['status'] == 'rejected' ? 'danger' : 'secondary');
                                                ?>">
                                                    <?php echo ucfirst($bid['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($bid['created_at'])); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <div class="alert alert-light p-2">No bids yet for this order.</div>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="alert alert-info text-center">No open bulk orders available.</div>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
