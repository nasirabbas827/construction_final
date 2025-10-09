<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

// Handle search
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $safe_search = mysqli_real_escape_string($conn, $search);
    $query = "SELECT m.*
              FROM materials m
              JOIN users s ON m.supplier_id = s.id
              WHERE m.status='active' 
              AND (m.name LIKE '%$safe_search%' OR m.category LIKE '%$safe_search%')";
} else {
    $query = "SELECT m.*
              FROM materials m
              JOIN users s ON m.supplier_id = s.id
              WHERE m.status='active'";
}

$result = mysqli_query($conn, $query);

// Check if query failed for error handling (optional but recommended)
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Material Catalog </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
         .material-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            overflow: hidden; /* Ensures image and card match border radius */
        }
        .material-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .material-img {
            height: 200px; /* Slightly taller image */
            object-fit: cover;
            width: 100%;
            border-bottom: 1px solid #eee;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.5rem;
        }
        .card-text strong {
            color: #495057;
        }
        .price-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: #007bff; /* Primary color for price */
            margin-bottom: 0.75rem;
        }
        .action-buttons {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #ced4da;
        }
        .search-container {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    
    <div class="text-center mb-5 search-container">
        <h2 class="mb-3 text-primary"><i class="fas fa-boxes mr-2"></i>Explore Construction Materials</h2>

        <form method="GET" class="form-inline justify-content-center">
            <div class="input-group" style="width: 450px; max-width: 100%;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-control form-control-lg" placeholder="Search by name, category, or supplier" aria-label="Search materials">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
            <?php if (!empty($search)) { ?>
                <a href="dashboard.php" class="btn btn-secondary ml-3 mt-2 mt-sm-0">Clear Search</a>
            <?php } ?>
        </form>
    </div>

    <div class="row">
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card material-card">
                        <img src="../<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top material-img" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="card-text mb-2 text-muted small"><i class="fas fa-industry mr-1"></i> Supplier: **<?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?>**</p>
                            
                            <p class="price-text">
                                Rs. <?php echo number_format($row['retail_price'], 2); ?>
                                <span class="text-muted small">/ <?php echo htmlspecialchars($row['unit_of_measure']); ?></span>
                            </p>

                            <p class="card-text mb-1">
                                <i class="fas fa-tag text-secondary mr-1"></i> 
                                <strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?>
                            </p>
                            
                            <p class="card-text mb-3">
                                <i class="fas fa-cubes text-secondary mr-1"></i> 
                                <strong>Stock:</strong> 
                                <span class="badge badge-<?php echo ($row['stock_quantity'] <= $row['low_stock_threshold']) ? 'danger' : 'success'; ?> p-2">
                                    <?php 
                                    echo ($row['stock_quantity'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'In Stock'; 
                                    echo " ({$row['stock_quantity']} {$row['unit_of_measure']})";
                                    ?>
                                </span>
                            </p>
                            
                            <div class="text-center action-buttons">
                                <a href="material_details.php?id=<?php echo $row['material_id']; ?>" 
                                   class="btn btn-info btn-block mb-2">
                                   <i class="fas fa-eye mr-1"></i> View Details
                                </a>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="feedback.php?supplier_id=<?php echo $row['supplier_id']; ?>&material_id=<?php echo $row['material_id']; ?>" 
                                       class="btn btn-outline-warning btn-sm flex-grow-1 mr-1">
                                       <i class="fas fa-comment-dots mr-1"></i> Feedback
                                    </a>
                                    <a href="bulk_order.php?material_id=<?php echo $row['material_id']; ?>" 
                                       class="btn btn-success btn-sm flex-grow-1 ml-1">
                                       <i class="fas fa-truck-loading mr-1"></i> Bulk Order
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12 text-center mt-4">
                <div class="alert alert-warning p-4 shadow">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> No Materials Found!</h4>
                    <p class="mb-0">Your search for "**<?php echo htmlspecialchars($search); ?>**" did not return any active materials. Try broadening your search.</p>
                </div>
                <?php if (!empty($search)) { ?>
                    <a href="dashboard.php" class="btn btn-primary mt-3"><i class="fas fa-redo"></i> Browse All Materials</a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>