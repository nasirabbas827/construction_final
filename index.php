<?php
include('config.php');
session_start();

// Handle material search
$search = "";
$search_query = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_query = "%$search%";
    $stmt = $conn->prepare("SELECT material_id, name, category, retail_price, stock_quantity, unit_of_measure, image, low_stock_threshold 
                            FROM materials 
                            WHERE status = 'active' 
                            AND (name LIKE ? OR category LIKE ?)");
    $stmt->bind_param("ss", $search_query, $search_query);
} else {
    $stmt = $conn->prepare("SELECT material_id, name, category, retail_price, stock_quantity, unit_of_measure, image, low_stock_threshold 
                            FROM materials 
                            WHERE status = 'active'");
}
$stmt->execute();
$material_result = $stmt->get_result();
$stmt->close();

// Fetch material category distribution for chart
$stmt = $conn->prepare("
    SELECT category, COUNT(*) as count 
    FROM materials 
    WHERE status = 'active' 
    GROUP BY category
");
$stmt->execute();
$category_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent coupons
$stmt = $conn->prepare("SELECT code, description, discount_percent, expire 
                        FROM coupons 
                        WHERE expire >= CURDATE() OR expire IS NULL 
                        LIMIT 5");
$stmt->execute();
$coupons_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConstructoHub - Construction Materials Marketplace</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #ff6f61;
            --highlight: #6f42c1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
        }
        .navbar {
            background-color: var(--dark);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar-brand:hover, .nav-link:hover {
            color: var(--accent) !important;
        }
        .hero-section {
            height: 600px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./images/hotel.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 0 30px 30px;
            animation: fadeIn 1.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hero-section .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            padding: 12px 30px;
            font-size: 1.2rem;
            transition: transform 0.3s, background-color 0.3s;
        }
        .hero-section .btn-primary:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
            transform: scale(1.05);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .search-form {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            margin-bottom: 40px;
            border-left: 5px solid var(--accent);
        }
        .section {
            padding: 80px 0;
        }
        .section-title {
            color: var(--highlight);
            margin-bottom: 40px;
            font-weight: 700;
            position: relative;
        }
        .section-title::after {
            content: '';
            width: 60px;
            height: 4px;
            background-color: var(--accent);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .feature-icon {
            font-size: 3em;
            color: var(--accent);
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .card:hover .feature-icon {
            color: var(--highlight);
        }
        .chart-container {
            max-width: 450px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            border-top: 4px solid var(--accent);
        }
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        .btn-success:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 8px rgba(255, 111, 97, 0.3);
        }
        .alert {
            margin-bottom: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border-left: 5px solid var(--accent);
        }
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }
        .notification-list::-webkit-scrollbar {
            width: 8px;
        }
        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 123, 255, 0.5);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="display-4 fw-bold">ConstructoHub</h1>
        <p class="lead mb-4">Your One-Stop Marketplace for Construction Materials</p>
        <?php if (!isset($_SESSION['id'])): ?>
            <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt mr-2"></i>Login to Explore</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard</a>
        <?php endif; ?>
    </div>

    <!-- About Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">About ConstructoHub</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="lead">ConstructoHub is a premier platform connecting wholesalers, retailers, and customers in the construction industry. Browse a wide range of materials, place bulk orders, and take advantage of exclusive coupons.</p>
                    <p>Our mission is to streamline the procurement process, ensuring quality materials, competitive prices, and seamless transactions for all users.</p>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h4 class="text-center mb-4">Material Category Distribution</h4>
                        <canvas id="materialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Key Features</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-boxes feature-icon"></i>
                            <h5 class="card-title">Browse Materials</h5>
                            <p class="card-text">Explore a wide variety of construction materials from trusted suppliers.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-truck feature-icon"></i>
                            <h5 class="card-title">Bulk Orders</h5>
                            <p class="card-text">Place bulk orders and negotiate with suppliers for the best deals.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-ticket-alt feature-icon"></i>
                            <h5 class="card-title">Exclusive Coupons</h5>
                            <p class="card-text">Save more with exclusive discounts and coupons on your purchases.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Materials Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">Construction Materials</h2>
            <form method="get" class="search-form mb-5">
                <div class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by material name or category" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search mr-2"></i>Search</button>
                    </div>
                </div>
            </form>

            <?php if ($material_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($material = $material_result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($material['image'] ?? './images/default.jpg'); ?>" class="card-img-top" alt="Material Image">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($material['name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($material['category']); ?><br>
                                        <strong>Price:</strong> Rs. <?php echo number_format($material['retail_price'], 2); ?><br>
                                        <strong>Stock:</strong> <?php echo $material['stock_quantity']; ?> <?php echo htmlspecialchars($material['unit_of_measure']); ?><br>
                                        <strong>Status:</strong> 
                                        <span class="badge badge-<?php echo ($material['stock_quantity'] <= $material['low_stock_threshold']) ? 'danger' : 'success'; ?>">
                                            <?php echo ($material['stock_quantity'] <= $material['low_stock_threshold']) ? 'Low Stock' : 'Available'; ?>
                                        </span>
                                    </p>
                                    <?php if (isset($_SESSION['id'])): ?>
                                        <a href="material_details.php?id=<?php echo $material['material_id']; ?>" class="btn btn-info btn-sm">View Details</a>
                                        <a href="bulk_order.php?material_id=<?php echo $material['material_id']; ?>" class="btn btn-success btn-sm">Bulk Order</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">No materials found for the given search criteria.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coupons Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Available Coupons</h2>
            <div class="notification-list">
                <?php if ($coupons_result->num_rows > 0): ?>
                    <?php while ($coupon = $coupons_result->fetch_assoc()): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading mb-0 font-weight-bold">Coupon: <?php echo htmlspecialchars($coupon['code']); ?></h6>
                            <p class="mb-0 small"><?php echo htmlspecialchars($coupon['description'] ?? 'No description'); ?></p>
                            <small>Discount: <strong><?php echo $coupon['discount_percent']; ?>%</strong> | Expires: <strong><?php echo $coupon['expire'] ? date('d M Y', strtotime($coupon['expire'])) : 'No expiry'; ?></strong></small>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-secondary text-center">No active coupons available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© 2025 ConstructoHub. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Material Category Chart
        const ctx = document.getElementById('materialChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($category_data, 'category')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($category_data, 'count')); ?>],
                    backgroundColor: ['#007bff', '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#ff6f61', '#6f42c1', '#6610f2', '#fd7e14', '#20c997', '#0d6efd', '#198754', '#0dcaf0', '#adb5bd'],
                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14,
                                family: 'Segoe UI'
                            },
                            color: 'var(--dark)'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'var(--dark)',
                        titleFont: { size: 16 },
                        bodyFont: { size: 14 }
                    }
                }
            }
        });

        // Fallback for empty chart
        if (<?php echo count($category_data); ?> === 0) {
            document.getElementById('materialChart').style.display = 'none';
            document.getElementById('materialChart').insertAdjacentHTML('afterend', '<p class="text-muted text-center">No material data available.</p>');
        }
    </script>
</body>
</html>