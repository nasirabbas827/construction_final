<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$material_id = intval($_GET['id']);

// Fetch material with supplier info
// Used prepared statement for better security, even though intval was used above
$stmt = $conn->prepare("SELECT m.*, u.name AS supplier_name, u.email AS supplier_email, u.phone AS supplier_phone
                        FROM materials m 
                        LEFT JOIN users u ON m.supplier_id = u.id 
                        WHERE m.material_id = ? AND m.status='active'");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    die("<div class='container mt-5'><div class='alert alert-danger text-center'>Material not found or is inactive!</div></div>");
}

$material = $result->fetch_assoc();

// Determine stock status and corresponding class
$is_low_stock = $material['stock_quantity'] <= $material['low_stock_threshold'];
$stock_status_text = $is_low_stock ? 'Low Stock' : 'In Stock';
$stock_status_class = $is_low_stock ? 'badge-danger' : 'badge-success';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($material['name']); ?> - Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>

        .detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .material-image {
            width: 100%;
            height: 400px; /* Enhanced size */
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .price-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .price-display small {
            font-size: 60%;
            font-weight: 400;
            color: #6c757d;
        }
        .info-block {
            padding: 10px 0;
            border-bottom: 1px dotted #dee2e6;
        }
        .info-block i {
            color: #007bff;
            width: 20px;
            text-align: center;
        }
        .supplier-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
            font-size: 1.1rem;
            padding: 10px 30px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #1e7e34;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
        <div class="row detail-card">
        
        <div class="col-lg-5 mb-4">
            <img src="../<?php echo htmlspecialchars($material['image']); ?>" alt="Material Image" class="material-image">
        </div>

        <div class="col-lg-7">
            <h1 class="mb-3 text-primary"><?php echo htmlspecialchars($material['name']); ?></h1>
            
            <div class="price-display">
                Rs. <?php echo number_format($material['retail_price'], 2); ?>
                <small>/ <?php echo htmlspecialchars($material['unit_of_measure']); ?></small>
            </div>
            
            <h5 class="mt-4 mb-2">Product Description</h5>
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($material['description'])); ?></p>

            <div class="row mt-4">
                <div class="col-md-6 info-block">
                    <p class="mb-0">
                        <i class="fas fa-tag mr-2"></i> <strong>Category:</strong> <?php echo htmlspecialchars($material['category']); ?>
                    </p>
                </div>
                <div class="col-md-6 info-block">
                    <p class="mb-0">
                        <i class="fas fa-truck-moving mr-2"></i> <strong>Min Order:</strong> <?php echo $material['min_order_quantity']; ?>
                    </p>
                </div>
                <div class="col-md-6 info-block">
                    <p class="mb-0">
                        <i class="fas fa-boxes mr-2"></i> <strong>Current Stock:</strong> 
                        <?php echo $material['stock_quantity']; ?>
                        <span class="badge <?php echo $stock_status_class; ?> ml-2"><?php echo $stock_status_text; ?></span>
                    </p>
                </div>
                <div class="col-md-6 info-block">
                    <p class="mb-0">
                        <i class="fas fa-percent mr-2"></i> <strong>Bulk Discount:</strong> <?php echo $material['bulk_discount_percentage']; ?>%
                    </p>
                </div>
                <div class="col-md-6 info-block">
                    <p class="mb-0">
                        <i class="fas fa-money-bill-wave mr-2"></i> <strong>Wholesale Price:</strong> Rs. <?php echo number_format($material['wholesale_price'], 2); ?>
                    </p>
                </div>
            </div>

            <div class="supplier-box">
                <h6 class="mb-2 text-dark"><i class="fas fa-industry mr-2"></i>Sold By: **<?php echo htmlspecialchars($material['company_name'] ?? $material['supplier_name']); ?>**</h6>
                <small class="d-block text-muted">
                    <i class="fas fa-envelope mr-1"></i> Email: <?php echo htmlspecialchars($material['supplier_email']); ?>
                    <?php if (!empty($material['supplier_phone'])): ?>
                    | <i class="fas fa-phone mr-1"></i> Phone: <?php echo htmlspecialchars($material['supplier_phone']); ?>
                    <?php endif; ?>
                </small>
            </div>

            <div class="mt-4 pt-3 text-center border-top">
                <a href="order_now.php?material_id=<?php echo $material['material_id']; ?>" class="btn btn-primary mr-3">
                    <i class="fas fa-shopping-cart mr-2"></i> Order Now
                </a>
                <a href="bulk_order.php?material_id=<?php echo $material['material_id']; ?>" class="btn btn-outline-success">
                    <i class="fas fa-warehouse mr-2"></i> Request Bulk Quote
                </a>
            </div>
        </div>
        
        <div class="col-12 mt-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-secondary"><i class="fas fa-sticky-note mr-2"></i>Supplier Notes:</h5>
                    <p class="card-text text-muted small"><?php echo nl2br(htmlspecialchars($material['supplier_notes'] ?? 'No special notes provided by the supplier.')); ?></p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>