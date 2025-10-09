<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$supplier_id = $_SESSION["id"];
$msg = "";

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

    // Handle image upload
    $image_path = null;
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

    $sql = "INSERT INTO materials (supplier_id, name, category, description, wholesale_price, retail_price, stock_quantity, low_stock_threshold, unit_of_measure, min_order_quantity, bulk_discount_percentage, image, status, supplier_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssddiiisdsss", $supplier_id, $name, $category, $description, $wholesale_price, $retail_price, $stock_quantity, $low_stock_threshold, $unit_of_measure, $min_order_quantity, $bulk_discount_percentage, $image_path, $status, $supplier_notes);

    if (mysqli_stmt_execute($stmt)) {
        $msg = '<div class="alert alert-success text-center">Material added successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger text-center">Error adding material. Please try again.</div>';
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Material - Wholesaler</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="text-center mb-4">Add New Material</h3>
            <?php echo $msg; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Material Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="cement">Cement</option>
                            <option value="bricks">Bricks</option>
                            <option value="steel">Steel</option>
                            <option value="wood">Wood</option>
                            <option value="tiles">Tiles</option>
                            <option value="sand">Sand</option>
                            <option value="gravel">Gravel</option>
                            <option value="concrete">Concrete</option>
                            <option value="glass">Glass</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="paint">Paint</option>
                            <option value="roofing">Roofing</option>
                            <option value="insulation">Insulation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Wholesale Price</label>
                        <input type="number" step="0.01" name="wholesale_price" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Retail Price</label>
                        <input type="number" step="0.01" name="retail_price" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Unit of Measure</label>
                        <select name="unit_of_measure" class="form-control">
                            <option value="kg">Kg</option>
                            <option value="ton">Ton</option>
                            <option value="piece">Piece</option>
                            <option value="square_meter">Square Meter</option>
                            <option value="cubic_meter">Cubic Meter</option>
                            <option value="liter">Liter</option>
                            <option value="meter">Meter</option>
                            <option value="bundle">Bundle</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Min Order Quantity</label>
                        <input type="number" name="min_order_quantity" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Bulk Discount (%)</label>
                        <input type="number" step="0.01" name="bulk_discount_percentage" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Material Image</label>
                        <input type="file" name="image" class="form-control-file">
                    </div>
                </div>

                <div class="form-group">
                    <label>Supplier Notes</label>
                    <textarea name="supplier_notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Add Material</button>
                    <a href="view_materials.php" class="btn btn-secondary">View Materials</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
