<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$supplier_id = $_SESSION["id"];
$msg = "";

// Validate material ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: view_materials.php");
    exit;
}

$material_id = intval($_GET['id']);

// Fetch existing material data
$sql = "SELECT * FROM materials WHERE material_id = ? AND supplier_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $material_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$material = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$material) {
    die("Material not found or unauthorized access.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $wholesale_price = $_POST["wholesale_price"];
    $retail_price = $_POST["retail_price"];
    $stock_quantity = $_POST["stock_quantity"];
    $low_stock_threshold = $_POST["low_stock_threshold"];
    $unit_of_measure = $_POST["unit_of_measure"];
    $min_order_quantity = $_POST["min_order_quantity"];
    $bulk_discount_percentage = $_POST["bulk_discount_percentage"];
    $supplier_notes = trim($_POST["supplier_notes"]);
    $status = $_POST["status"];

    // Image handling
    $image_path = $material['image'];
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = "uploads/" . $file_name;
        }
    }

    // Update query
    $update_sql = "UPDATE materials SET 
        name=?, category=?, description=?, wholesale_price=?, retail_price=?, stock_quantity=?, 
        low_stock_threshold=?, unit_of_measure=?, min_order_quantity=?, bulk_discount_percentage=?, 
        image=?, status=?, supplier_notes=? 
        WHERE material_id=? AND supplier_id=?";
    
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param(
        $stmt,
        "sssddiiisdsssii",
        $name,
        $category,
        $description,
        $wholesale_price,
        $retail_price,
        $stock_quantity,
        $low_stock_threshold,
        $unit_of_measure,
        $min_order_quantity,
        $bulk_discount_percentage,
        $image_path,
        $status,
        $supplier_notes,
        $material_id,
        $supplier_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $msg = '<div class="alert alert-success text-center">Material updated successfully!</div>';
        // Refresh material data
        $result = mysqli_query($conn, "SELECT * FROM materials WHERE material_id = $material_id AND supplier_id = $supplier_id");
        $material = mysqli_fetch_assoc($result);
    } else {
        $msg = '<div class="alert alert-danger text-center">Error updating material. Please try again.</div>';
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Material - Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Edit Material</h3>
            <?php echo $msg; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Material Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($material['name']); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <?php
                            $categories = ['cement', 'bricks', 'steel', 'wood', 'tiles', 'sand', 'gravel', 'concrete', 'glass', 'plumbing', 'electrical', 'paint', 'roofing', 'insulation', 'other'];
                            foreach ($categories as $cat) {
                                $selected = ($cat == $material['category']) ? 'selected' : '';
                                echo "<option value='$cat' $selected>" . ucfirst($cat) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($material['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Wholesale Price</label>
                        <input type="number" step="0.01" name="wholesale_price" class="form-control" value="<?php echo $material['wholesale_price']; ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Retail Price</label>
                        <input type="number" step="0.01" name="retail_price" class="form-control" value="<?php echo $material['retail_price']; ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?php echo $material['stock_quantity']; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control" value="<?php echo $material['low_stock_threshold']; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Unit of Measure</label>
                        <select name="unit_of_measure" class="form-control">
                            <?php
                            $units = ['kg', 'ton', 'piece', 'square_meter', 'cubic_meter', 'liter', 'meter', 'bundle', 'other'];
                            foreach ($units as $unit) {
                                $selected = ($unit == $material['unit_of_measure']) ? 'selected' : '';
                                echo "<option value='$unit' $selected>" . ucfirst(str_replace('_', ' ', $unit)) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Min Order Quantity</label>
                        <input type="number" name="min_order_quantity" class="form-control" value="<?php echo $material['min_order_quantity']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Bulk Discount (%)</label>
                        <input type="number" step="0.01" name="bulk_discount_percentage" class="form-control" value="<?php echo $material['bulk_discount_percentage']; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($material['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($material['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Material Image</label><br>
                        <?php if (!empty($material['image'])): ?>
                            <img src="../<?php echo $material['image']; ?>" alt="Material Image" width="80" class="mb-2 rounded"><br>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control-file">
                    </div>
                </div>

                <div class="form-group">
                    <label>Supplier Notes</label>
                    <textarea name="supplier_notes" class="form-control" rows="2"><?php echo htmlspecialchars($material['supplier_notes']); ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-success">Update Material</button>
                    <a href="view_materials.php" class="btn btn-secondary">Back to Materials</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
