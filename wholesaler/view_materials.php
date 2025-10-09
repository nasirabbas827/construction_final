<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$supplier_id = $_SESSION["id"];

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM materials WHERE material_id = $delete_id AND supplier_id = $supplier_id");
    echo "<script>alert('Material deleted successfully!'); window.location='view_materials.php';</script>";
    exit;
}

// Search filter
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = "SELECT * FROM materials WHERE supplier_id = $supplier_id AND name LIKE '%$search%' ORDER BY created_at DESC";
} else {
    $query = "SELECT * FROM materials WHERE supplier_id = $supplier_id ORDER BY created_at DESC";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Materials - Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .low-stock { background-color: #f8d7da !important; color: #721c24; font-weight: bold; }
        img.rounded { object-fit: cover; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Your Added Materials</h3>
        <a href="add_material.php" class="btn btn-primary">Add New Material</a>
    </div>

    <!-- Search bar -->
    <form method="get" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-2" placeholder="Search materials..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>

    <table class="table table-bordered table-hover text-center">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Wholesale</th>
                <th>Retail</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
                <th>Added On</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { 
                $isLowStock = $row['stock_quantity'] <= $row['low_stock_threshold'];
            ?>
            <tr class="<?php echo $isLowStock ? 'low-stock' : ''; ?>">
                <td><?php echo $row['material_id']; ?></td>
                <td>
                    <?php if ($row['image']) { ?>
                        <img src="../<?php echo $row['image']; ?>" width="60" height="60" class="rounded">
                    <?php } else { echo "No Image"; } ?>
                </td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo number_format($row['wholesale_price'], 2); ?></td>
                <td><?php echo number_format($row['retail_price'], 2); ?></td>
                <td><?php echo $row['stock_quantity']; ?></td>
                <td>
                    <span class="badge badge-<?php echo ($row['status'] == 'active') ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>
                <td>
                    <a href="edit_material.php?id=<?php echo $row['material_id']; ?>" class="m-2 btn btn-sm btn-warning">Edit</a>
                    <a href="view_materials.php?delete=<?php echo $row['material_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');">Delete</a>
                </td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
