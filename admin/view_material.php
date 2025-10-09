<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Update material status
if (isset($_POST['update_status'])) {
    $material_id = intval($_POST['material_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE materials SET status=? WHERE material_id=?");
    $stmt->bind_param("si", $status, $material_id);
    $stmt->execute();
    $stmt->close();
}

// Delete operation
if (isset($_GET['delete'])) {
    $material_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM materials WHERE material_id = $material_id");
}

$result = mysqli_query($conn, "SELECT m.*, u.name AS supplier_name FROM materials m LEFT JOIN users u ON m.supplier_id = u.id ORDER BY m.material_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Materials</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">All Materials</h2>
    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Supplier</th>
                <th>Category</th>
                <th>Wholesale Price</th>
                <th>Retail Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['material_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['supplier_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td>Rs. <?= number_format($row['wholesale_price'], 2) ?></td>
                <td>Rs. <?= number_format($row['retail_price'], 2) ?></td>
                <td><?= $row['stock_quantity'] . ' ' . $row['unit_of_measure'] ?></td>
                <td>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="material_id" value="<?= $row['material_id'] ?>">
                        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="active" <?= $row['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $row['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <noscript><button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button></noscript>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </td>
                <td>
                    <a href="?delete=<?= $row['material_id'] ?>" onclick="return confirm('Delete this material?')" class="btn btn-sm btn-danger mt-1">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
