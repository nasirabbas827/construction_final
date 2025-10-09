<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}


// Total Users by Role and Status
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'wholesaler' THEN 1 ELSE 0 END) as wholesalers,
        SUM(CASE WHEN role = 'retailer' THEN 1 ELSE 0 END) as retailers,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customers,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM users
");
$stmt->execute();
$user_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Total Materials (Active vs Inactive)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_materials,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_materials,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_materials
    FROM materials
");
$stmt->execute();
$material_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Total Orders with Payment and Order Status
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders
");
$stmt->execute();
$order_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Recent Supplier Bids
$stmt = $conn->prepare("
    SELECT sb.bid_id, sb.bid_price, sb.status, bo.quantity, m.name, u.name as supplier_name
    FROM supplier_bids sb
    JOIN bulk_orders bo ON sb.bulk_order_id = bo.order_id
    JOIN materials m ON bo.material_id = m.material_id
    JOIN users u ON sb.supplier_id = u.id
    ORDER BY sb.created_at DESC LIMIT 5
");
$stmt->execute();
$bids_result = $stmt->get_result();
$stmt->close();

// Recent Bulk Orders
$stmt = $conn->prepare("
    SELECT bo.order_id, bo.quantity, m.name, u.name as customer_name, bo.created_at
    FROM bulk_orders bo
    JOIN materials m ON bo.material_id = m.material_id
    JOIN users u ON bo.customer_id = u.id
    ORDER BY bo.created_at DESC LIMIT 5
");
$stmt->execute();
$bulk_orders_result = $stmt->get_result();
$stmt->close();

// Recent System Notifications
$stmt = $conn->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$notifications_result = $stmt->get_result();
$stmt->close();

// Recent Chat Messages
$stmt = $conn->prepare("
    SELECT cm.message, cm.sent_at, u1.name as sender, u2.name as receiver
    FROM chat_messages cm
    JOIN users u1 ON cm.sender_id = u1.id
    JOIN users u2 ON cm.receiver_id = u2.id
    ORDER BY cm.sent_at DESC LIMIT 5
");
$stmt->execute();
$chat_messages_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Beautiful B4</title>
    
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
<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <div class="card welcome-card mb-5 shadow">
        <div class="card-body text-center py-4">
            <h1 class="mb-1 font-weight-bold">Admin Dashboard</h1>
            <p class="lead mb-0">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"] ?? 'Admin'); ?>!</strong> Monitor and manage system activities.</p>
        </div>
    </div>

    <div class="row">
        <?php
        $stats = [
            ['title' => 'Total Users', 'value' => $user_stats['total_users'], 'icon' => 'fas fa-users', 'color_class' => 'bg-primary', 'subtitle' => "W: {$user_stats['wholesalers']} | R: {$user_stats['retailers']} | C: {$user_stats['customers']}"],
            ['title' => 'Total Materials', 'value' => $material_stats['total_materials'], 'icon' => 'fas fa-boxes', 'color_class' => 'bg-success', 'subtitle' => "Active: {$material_stats['active_materials']} | Inactive: {$material_stats['inactive_materials']}"],
            ['title' => 'Total Orders', 'value' => $order_stats['total_orders'], 'icon' => 'fas fa-shopping-cart', 'color_class' => 'bg-info'],
            ['title' => 'Pending Approvals', 'value' => $user_stats['pending'], 'icon' => 'fas fa-user-clock', 'color_class' => 'bg-warning'],
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
                    <h5 class="card-title mb-0"><i class="fas fa-bell mr-2"></i>Recent System Notifications</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($notifications_result->num_rows > 0) {
                        while ($row = $notifications_result->fetch_assoc()) { ?>
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
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-truck mr-2"></i>Recent Bulk Orders</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($bulk_orders_result->num_rows > 0) {
                        while ($row = $bulk_orders_result->fetch_assoc()) { ?>
                            <div class="alert alert-light border border-info alert-dismissible fade show" role="alert">
                                <h6 class="mb-0 font-weight-bold text-info">Bulk Order #<?php echo $row['order_id']; ?></h6>
                                <small>Material: <strong><?php echo htmlspecialchars($row['name']); ?></strong> | Quantity: <strong><?php echo number_format($row['quantity'], 2); ?></strong></small><br>
                                <small>Customer: <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></small><br>
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
                    <h5 class="card-title mb-0"><i class="fas fa-gavel mr-2"></i>Recent Supplier Bids</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($bids_result->num_rows > 0) {
                        while ($row = $bids_result->fetch_assoc()) {
                            $status_class = match(strtolower($row['status'])) {
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'info',
                            };
                            ?>
                            <div class="alert alert-<?php echo $status_class; ?> alert-dismissible fade show" role="alert">
                                <h6 class="mb-0 font-weight-bold">Bid #<?php echo $row['bid_id']; ?>: <strong><?php echo ucfirst($row['status']); ?></strong></h6>
                                <small>Material: <strong><?php echo htmlspecialchars($row['name']); ?></strong> | Quantity: <strong><?php echo number_format($row['quantity'], 2); ?></strong></small><br>
                                <small>Supplier: <strong><?php echo htmlspecialchars($row['supplier_name']); ?></strong> | Bid Price: <strong>Rs. <?php echo number_format($row['bid_price'], 2); ?></strong></small>
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

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-comments mr-2"></i>Recent Chat Messages</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($chat_messages_result->num_rows > 0) {
                        while ($row = $chat_messages_result->fetch_assoc()) { ?>
                            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading mb-0 font-weight-bold">From: <?php echo htmlspecialchars($row['sender']); ?> to <?php echo htmlspecialchars($row['receiver']); ?></h6>
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
    if (<?php echo $order_stats['total_orders']; ?> === 0) {
        document.getElementById('orderChart').style.display = 'none';
        document.getElementById('orderChart').insertAdjacentHTML('afterend', '<p class="text-muted text-center">No order data available.</p>');
    }
</script>
</body>
</html>