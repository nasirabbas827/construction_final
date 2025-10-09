<?php
session_start();
include('config.php'); // Assuming 'config.php' establishes $conn

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];


// Fetch total materials count
$materials_count = $conn->query("SELECT COUNT(*) as total FROM materials WHERE supplier_id = $user_id")->fetch_assoc()['total'];

// Fetch low stock materials
$low_stock_query = $conn->query("SELECT name, stock_quantity, low_stock_threshold FROM materials WHERE supplier_id = $user_id AND stock_quantity <= low_stock_threshold");
$low_stock = [];
if ($low_stock_query) {
    while ($row = $low_stock_query->fetch_assoc()) {
        $low_stock[] = $row;
    }
}

// Fetch total coupons count
$coupons_count = $conn->query("SELECT COUNT(*) as total FROM coupons WHERE sender_id = $user_id")->fetch_assoc()['total'];

// Fetch total orders for wholesaler's materials
$orders_count = $conn->query("
    SELECT COUNT(DISTINCT o.order_id) as total 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN materials m ON oi.material_id = m.material_id
    WHERE m.supplier_id = $user_id
")->fetch_assoc()['total'];

// Fetch payment stats for the wholesaler's materials
$payment_summary = [
    'paid' => ['total_orders' => 0, 'total_amount' => 0],
    'pending' => ['total_orders' => 0, 'total_amount' => 0],
    'failed' => ['total_orders' => 0, 'total_amount' => 0],
];
$payments_query = $conn->query("
    SELECT o.payment_status, COUNT(DISTINCT o.order_id) AS total_orders, SUM(o.total_amount) AS total_amount
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN materials m ON oi.material_id = m.material_id
    WHERE m.supplier_id = $user_id
    GROUP BY o.payment_status
");
if ($payments_query && $payments_query->num_rows > 0) {
    while ($row = $payments_query->fetch_assoc()) {
        $status = strtolower($row['payment_status']);
        if (isset($payment_summary[$status])) {
            $payment_summary[$status]['total_orders'] = $row['total_orders'];
            $payment_summary[$status]['total_amount'] = $row['total_amount'];
        }
    }
}

// Fetch total credit payments
$credit_payments = $conn->query("
    SELECT COUNT(DISTINCT cp.order_id) as total, SUM(cp.total_credit) as amount
    FROM credit_payments cp
    JOIN orders o ON cp.order_id = o.order_id
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN materials m ON oi.material_id = m.material_id
    WHERE m.supplier_id = $user_id
")->fetch_assoc();

// Fetch reviews stats
$reviews = $conn->query("
    SELECT COUNT(*) as total, AVG(rating) as avg_rating
    FROM reviews WHERE supplier_id = $user_id
")->fetch_assoc();
$reviews['avg_rating'] = $reviews['avg_rating'] ?? 0;

// Fetch recent notifications
$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");

// Fetch recent bulk orders
$bulk_orders = $conn->query("
    SELECT bo.order_id, bo.quantity, m.name, bo.created_at
    FROM bulk_orders bo
    JOIN materials m ON bo.material_id = m.material_id
    WHERE m.supplier_id = $user_id
    ORDER BY bo.created_at DESC LIMIT 5
");

// Fetch recent chat messages
$chat_messages = $conn->query("
    SELECT cm.message, cm.sent_at, u.name as sender
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.receiver_id = $user_id
    ORDER BY cm.sent_at DESC LIMIT 5
");

// Fetch recent bids
$bids = $conn->query("
    SELECT sb.bid_id, sb.bid_price, sb.status, bo.quantity, m.name
    FROM supplier_bids sb
    JOIN bulk_orders bo ON sb.bulk_order_id = bo.order_id
    JOIN materials m ON bo.material_id = m.material_id
    WHERE m.supplier_id = $user_id
    ORDER BY sb.created_at DESC LIMIT 5
");
// --- End Data Fetching ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wholesaler Dashboard | Beautiful B4</title>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <style>
        :root {
            --primary-color: #007bff; /* Bootstrap 4 primary blue */
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --bg-light: #f4f6f9;
            --card-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- Welcome Card Styling --- */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), #6610f2); /* Blue to Purple gradient */
            color: white;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }

        /* --- Stats Card Styling --- */
        .card-stats {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        }
        
        /* Floating Icon Header */
        .card-stats .card-header-icon {
            background: linear-gradient(45deg, var(--primary-color), #00c4ff);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            position: absolute;
            top: -20px;
            right: 20px;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.14), 0 7px 10px -5px rgba(0, 123, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-stats .card-body {
            padding-top: 2rem;
        }
        .card-stats .stats-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #343a40;
            line-height: 1;
        }
        .card-stats .stats-title {
            color: var(--secondary-color);
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* --- General Card Styling --- */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            /* Scrollbar styling */
        }
        .notification-list::-webkit-scrollbar {
            width: 8px;
        }
        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 123, 255, 0.5);
            border-radius: 10px;
        }

        /* Alert/List item styles */
        .alert {
            margin-bottom: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border-left: 5px solid;
        }
        .alert-warning { border-left-color: var(--warning-color); }
        .alert-info { border-left-color: var(--primary-color); }
        .alert-success { border-left-color: var(--success-color); }
        .alert-danger { border-left-color: var(--danger-color); }
        .alert small {
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container-fluid mt-4">
    <div class="card welcome-card mb-5 shadow">
        <div class="card-body text-center py-4">
            <h1 class="mb-1 font-weight-bold">Wholesaler Dashboard</h1>
            <p class="lead mb-0">Welcome back, **<?php echo htmlspecialchars($_SESSION["name"] ?? 'User'); ?>!** Manage your materials and orders.</p>
        </div>
    </div>

    <div class="row">
        <?php
        $stats = [
            ['title' => 'Total Materials', 'value' => $materials_count, 'icon' => 'fas fa-boxes', 'color_class' => 'bg-primary'],
            ['title' => 'Total Orders', 'value' => $orders_count, 'icon' => 'fas fa-shopping-cart', 'color_class' => 'bg-success'],
            ['title' => 'Total Coupons', 'value' => $coupons_count, 'icon' => 'fas fa-ticket-alt', 'color_class' => 'bg-info'],
            ['title' => 'Average Rating', 'value' => number_format($reviews['avg_rating'], 1) . '/5', 'icon' => 'fas fa-star', 'color_class' => 'bg-warning', 'subtitle' => "({$reviews['total']} reviews)"],
        ];
        
        foreach ($stats as $stat) {
            echo '
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-stats h-100">
                    <div class="card-body position-relative">
                        <div class="card-header-icon">
                            <i class="' . $stat['icon'] . ' fa-2x"></i>
                        </div>
                        <p class="stats-title mb-0">' . $stat['title'] . '</p>
                        <p class="stats-value mb-1">' . $stat['value'] . '</p>
                        ' . (isset($stat['subtitle']) ? '<small class="text-muted">' . $stat['subtitle'] . '</small>' : '') . '
                    </div>
                </div>
            </div>';
        }
        ?>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave mr-2"></i>Payment Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div style="max-height: 250px;"><canvas id="paymentChart"></canvas></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Order Payment Summary:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge badge-success">Paid Orders:</span> **<?php echo $payment_summary['paid']['total_orders']; ?>** (Rs. <?php echo number_format($payment_summary['paid']['total_amount'], 2); ?>)</li>
                                <li><span class="badge badge-warning">Pending Orders:</span> **<?php echo $payment_summary['pending']['total_orders']; ?>** (Rs. <?php echo number_format($payment_summary['pending']['total_amount'], 2); ?>)</li>
                                <li><span class="badge badge-danger">Failed Orders:</span> **<?php echo $payment_summary['failed']['total_orders']; ?>** (Rs. <?php echo number_format($payment_summary['failed']['total_amount'], 2); ?>)</li>
                            </ul>
                            
                            <hr>
                            
                            <h6 class="font-weight-bold"><i class="fas fa-credit-card mr-1 text-primary"></i>Total Credit Payments:</h6>
                            <p class="mb-0">**<?php echo $credit_payments['total']; ?>** transactions totaling **Rs. <?php echo number_format($credit_payments['amount'] ?? 0, 2); ?>**</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Critical Stock Alerts</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if (!empty($low_stock)) { ?>
                        <?php foreach ($low_stock as $row) { ?>
                            <div class="alert alert-warning alert-dismissible fade show mb-2" role="alert">
                                <strong>Low Stock: <?php echo htmlspecialchars($row['name']); ?></strong><br>
                                Current Stock: **<?php echo $row['stock_quantity']; ?>** (Threshold: <?php echo $row['low_stock_threshold']; ?>)
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="alert alert-success">All good! No low stock alerts currently.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-bell mr-2"></i>Recent System Notifications</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($notifications->num_rows > 0) {
                        while ($row = $notifications->fetch_assoc()) { ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading mb-0 font-weight-bold"><?php echo htmlspecialchars($row['title']); ?></h6>
                                <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></small>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="alert alert-secondary">No recent system notifications.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-comments mr-2"></i>Recent Chat Messages</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($chat_messages->num_rows > 0) {
                        while ($row = $chat_messages->fetch_assoc()) { ?>
                            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading mb-0 font-weight-bold">From: <?php echo htmlspecialchars($row['sender']); ?></h6>
                                <p class="mb-0 small text-truncate"><?php echo htmlspecialchars($row['message']); ?></p>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i><?php echo date('d M Y, h:i A', strtotime($row['sent_at'])); ?></small>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="alert alert-secondary">No recent messages.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-truck mr-2"></i>Recent Bulk Orders</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($bulk_orders->num_rows > 0) {
                        while ($row = $bulk_orders->fetch_assoc()) { ?>
                            <div class="alert alert-light border border-info fade show" role="alert">
                                <h6 class="mb-0 font-weight-bold text-info">Bulk Order #<?php echo $row['order_id']; ?></h6>
                                <small>Material: **<?php echo htmlspecialchars($row['name']); ?>** | Quantity: **<?php echo $row['quantity']; ?>**</small><br>
                                <small class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></small>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="alert alert-secondary">No recent bulk orders.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-gavel mr-2"></i>Recent Bids</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($bids->num_rows > 0) {
                        while ($row = $bids->fetch_assoc()) {
                            $status_class = match(strtolower($row['status'])) {
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'info',
                            };
                            ?>
                            <div class="alert alert-<?php echo $status_class; ?> alert-dismissible fade show" role="alert">
                                <h6 class="mb-0 font-weight-bold">Bid #<?php echo $row['bid_id']; ?>: **<?php echo ucfirst($row['status']); ?>**</h6>
                                <small>Material: **<?php echo htmlspecialchars($row['name']); ?>** | Quantity: **<?php echo $row['quantity']; ?>**</small><br>
                                <small>Bid Price: **Rs. <?php echo number_format($row['bid_price'], 2); ?>**</small>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="alert alert-secondary">No recent bids.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Payment Status Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Pending', 'Failed'],
            datasets: [{
                data: [
                    <?php echo $payment_summary['paid']['total_orders']; ?>,
                    <?php echo $payment_summary['pending']['total_orders']; ?>,
                    <?php echo $payment_summary['failed']['total_orders']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'], // Bootstrap success, warning, danger
                hoverOffset: 4,
                borderColor: '#f8f9fa',
                borderWidth: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += context.parsed + ' orders';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>