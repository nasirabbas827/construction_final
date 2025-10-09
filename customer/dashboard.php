<?php
session_start();
include('config.php'); // Assuming 'config.php' establishes $conn

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = filter_var($_SESSION["id"], FILTER_VALIDATE_INT);
if ($user_id === false) {
    header("location: ../index.php");
    exit;
}

// --- Data Fetching ---

// Total Orders and Payment Status
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
           SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending,
           SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed,
           SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing,
           SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
           SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
           SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Total Payments
$payment_count = $conn->prepare("
    SELECT COUNT(*) as total
    FROM payments WHERE customer_id = ?
");
$payment_count->bind_param("i", $user_id);
$payment_count->execute();
$payment_stats = $payment_count->get_result()->fetch_assoc();
$payment_count->close();

// Total Credit Payments
$credit_payments = $conn->prepare("
    SELECT COUNT(*) as total, SUM(cp.total_credit) as amount
    FROM credit_payments cp
    JOIN orders o ON cp.order_id = o.order_id
    WHERE o.id = ?
");
$credit_payments->bind_param("i", $user_id);
$credit_payments->execute();
$credit_stats = $credit_payments->get_result()->fetch_assoc();
$credit_payments->close();



// Recent Chat Messages
$chat_messages = $conn->prepare("
    SELECT cm.message, cm.sent_at, u.name as sender
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.receiver_id = ?
    ORDER BY cm.sent_at DESC LIMIT 5
");
$chat_messages->bind_param("i", $user_id);
$chat_messages->execute();
$chat_messages_result = $chat_messages->get_result();
$chat_messages->close();

// All Coupons
$coupons = $conn->prepare("SELECT code, description, discount_percent, expire FROM coupons WHERE expire >= CURDATE() OR expire IS NULL");
$coupons->execute();
$coupons_result = $coupons->get_result();
$coupons->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <style>
        :root {
            --primary-color: #007bff;
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

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), #6610f2);
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }

        .card-stats {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .card-stats .card-header-icon {
            background: linear-gradient(45deg, var(--primary-color), #00c4ff);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            position: absolute;
            top: -20px;
            right: 20px;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.14);
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

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
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
            <h1 class="mb-1 font-weight-bold">Customer Dashboard</h1>
            <p class="lead mb-0">Welcome back, <strong><?php echo htmlspecialchars($_SESSION["name"] ?? 'User'); ?>!</strong> Manage your orders and bids.</p>
        </div>
    </div>

    <div class="row">
        <?php
        $stats = [
            ['title' => 'Total Orders', 'value' => $order_stats['total'], 'icon' => 'fas fa-shopping-cart', 'color_class' => 'bg-primary'],
            ['title' => 'Total Payments', 'value' => $payment_stats['total'], 'icon' => 'fas fa-money-bill-wave', 'color_class' => 'bg-success'],
            ['title' => 'Credit Payments', 'value' => $credit_stats['total'], 'icon' => 'fas fa-credit-card', 'color_class' => 'bg-info', 'subtitle' => "(Rs. " . number_format($credit_stats['amount'] ?? 0, 2) . ")"],
            ['title' => 'Active Coupons', 'value' => $coupons_result->num_rows, 'icon' => 'fas fa-ticket-alt', 'color_class' => 'bg-warning'],
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
                    <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave mr-2"></i>Order & Payment Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div style="max-height: 250px;"><canvas id="orderChart"></canvas></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Order Status Summary:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge badge-primary">Processing:</span> <strong><?php echo $order_stats['processing']; ?></strong></li>
                                <li><span class="badge badge-info">Shipped:</span> <strong><?php echo $order_stats['shipped']; ?></strong></li>
                                <li><span class="badge badge-success">Delivered:</span> <strong><?php echo $order_stats['delivered']; ?></strong></li>
                                <li><span class="badge badge-danger">Cancelled:</span> <strong><?php echo $order_stats['cancelled']; ?></strong></li>
                            </ul>
                            <hr>
                            <h6 class="font-weight-bold">Payment Status Summary:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge badge-success">Paid:</span> <strong><?php echo $order_stats['paid']; ?></strong></li>
                                <li><span class="badge badge-warning">Pending:</span> <strong><?php echo $order_stats['pending']; ?></strong></li>
                                <li><span class="badge badge-danger">Failed:</span> <strong><?php echo $order_stats['failed']; ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-ticket-alt mr-2"></i>Available Coupons</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($coupons_result->num_rows > 0) {
                        while ($row = $coupons_result->fetch_assoc()) { ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading mb-0 font-weight-bold">Coupon: <?php echo htmlspecialchars($row['code']); ?></h6>
                                <p class="mb-0 small"><?php echo htmlspecialchars($row['description'] ?? 'No description'); ?></p>
                                <small>Discount: <strong><?php echo $row['discount_percent']; ?>%</strong> | Expires: <strong><?php echo $row['expire'] ? date('d M Y', strtotime($row['expire'])) : 'No expiry'; ?></strong></small>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="alert alert-secondary">No active coupons available.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Order Status Chart
    const orderCtx = document.getElementById('orderChart').getContext('2d');
    new Chart(orderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Processing', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $order_stats['processing']; ?>,
                    <?php echo $order_stats['shipped']; ?>,
                    <?php echo $order_stats['delivered']; ?>,
                    <?php echo $order_stats['cancelled']; ?>
                ],
                backgroundColor: ['#007bff', '#17a2b8', '#28a745', '#dc3545'],
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

    // Fallback for empty chart
    if (<?php echo $order_stats['total']; ?> === 0) {
        document.getElementById('orderChart').style.display = 'none';
        document.getElementById('orderChart').insertAdjacentHTML('afterend', '<p class="text-muted text-center">No order data available.</p>');
    }
</script>
</body>
</html>