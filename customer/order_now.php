<?php
session_start();  
include('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$customer_id = $_SESSION["id"];
$msg = "";
$material = null;

if (!isset($_GET['material_id']) || empty($_GET['material_id'])) {
    header("location: dashboard.php");
    exit;
}

$material_id = intval($_GET['material_id']);
$material_query = "SELECT * FROM materials WHERE material_id = $material_id";
$result = mysqli_query($conn, $material_query);
if (mysqli_num_rows($result) > 0) {
    $material = mysqli_fetch_assoc($result);
} else {
    die("<div class='container mt-5'><div class='alert alert-danger text-center'>Material not found!</div></div>");
}

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = intval($_POST['quantity']);
    $location = trim($_POST['location']);

    if ($quantity <= 0) {
        $msg = '<div class="alert alert-danger text-center">Please enter a valid quantity.</div>';
    } else {
        $price = $material['retail_price'];
        $min_qty = $material['min_order_quantity'];
        $discount_percentage = $material['bulk_discount_percentage'];

        $subtotal = $quantity * $price;
        $discount = 0;

        if ($quantity >= $min_qty && $discount_percentage > 0) {
            $discount = ($subtotal * $discount_percentage) / 100;
        }

        $total_amount = $subtotal - $discount;

        // Insert into orders table
        $sql_order = "INSERT INTO orders (id, total_amount, location) VALUES (?, ?, ?)";
        $stmt_order = mysqli_prepare($conn, $sql_order);
        mysqli_stmt_bind_param($stmt_order, "ids", $customer_id, $total_amount, $location);
        if (mysqli_stmt_execute($stmt_order)) {
            $order_id = mysqli_insert_id($conn);

            // Insert order item
            $sql_item = "INSERT INTO order_items (order_id, material_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = mysqli_prepare($conn, $sql_item);
            mysqli_stmt_bind_param($stmt_item, "iiidd", $order_id, $material_id, $quantity, $price, $subtotal);
            mysqli_stmt_execute($stmt_item);

            // Reduce stock
            $new_stock = $material['stock_quantity'] - $quantity;
            if ($new_stock < 0) $new_stock = 0;
            mysqli_query($conn, "UPDATE materials SET stock_quantity = $new_stock WHERE material_id = $material_id");

            $msg = '<div class="alert alert-success text-center">Order placed successfully!</div>';

            // Fetch user email
            $user_query = "SELECT email, name FROM users WHERE id = $customer_id";
            $user_result = mysqli_query($conn, $user_query);
            if ($user_result && mysqli_num_rows($user_result) > 0) {
                $user = mysqli_fetch_assoc($user_result);
                $user_email = $user['email'];
                $user_name = $user['name'];

                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'nasiryt.827@gmail.com'; // SMTP username
                    $mail->Password = 'ddgc zhft gohg bjmz'; // SMTP password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('nasiryt.827@gmail.com', 'Construction Material');
                    $mail->addAddress($user_email, $user_name);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "Order Confirmation #$order_id";
                    $mail->Body = "
                        <h3>Dear $user_name,</h3>
                        <p>Thank you for your order. Here are the details:</p>
                        <ul>
                            <li><strong>Material:</strong> {$material['name']}</li>
                            <li><strong>Quantity:</strong> $quantity {$material['unit_of_measure']}</li>
                            <li><strong>Price per unit:</strong> Rs. " . number_format($price, 2) . "</li>
                            <li><strong>Subtotal:</strong> Rs. " . number_format($subtotal, 2) . "</li>
                            <li><strong>Discount:</strong> Rs. " . number_format($discount, 2) . "</li>
                            <li><strong>Total Amount:</strong> Rs. " . number_format($total_amount, 2) . "</li>
                            <li><strong>Delivery Location:</strong> $location</li>
                        </ul>
                        <p>We will notify you once your order is shipped.</p>
                        <p>Regards,<br>Construction Material</p>
                    ";

                    $mail->send();
                    $msg .= '<div class="alert alert-success text-center mt-2">Confirmation email sent to your email.</div>';
                } catch (Exception $e) {
                    $msg .= '<div class="alert alert-warning text-center mt-2">Order placed, but email could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
                }
            }
        } else {
            $msg = '<div class="alert alert-danger text-center">Error placing order. Please try again.</div>';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Now - <?php echo htmlspecialchars($material['name']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .material-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .order-card {
            border-radius: 10px;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .price-box p {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card order-card">
        <div class="card-body">
            <h3 class="text-center mb-4">Order Material</h3>
            <?php echo $msg; ?>

            <div class="row">
                <div class="col-md-5">
                    <img src="../<?php echo $material['image']; ?>" alt="Material Image" class="material-image">
                </div>

                <div class="col-md-7">
                    <h4><?php echo htmlspecialchars($material['name']); ?></h4>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($material['category']); ?></p>
                    <p><strong>Price:</strong> Rs. <?php echo number_format($material['retail_price'], 2); ?> per <?php echo $material['unit_of_measure']; ?></p>
                    <p><strong>Available Stock:</strong> <?php echo $material['stock_quantity'] . " " . $material['unit_of_measure']; ?></p>
                    <p><strong>Bulk Discount:</strong> <?php echo $material['bulk_discount_percentage']; ?>% (on <?php echo $material['min_order_quantity']; ?>+ units)</p>

                    <form action="" method="post" class="mt-4">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="<?php echo $material['stock_quantity']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Delivery Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Enter delivery address" required>
                        </div>

                        <div class="price-box mt-3">
                            <p><strong>Original Total:</strong> <span id="original_total">Rs. 0.00</span></p>
                            <p><strong>Discount:</strong> <span id="discount_display">Rs. 0.00</span></p>
                            <p><strong>Final Total:</strong> <span id="final_total">Rs. 0.00</span></p>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Place Order</button>
                            <a href="material_details.php?material_id=<?php echo $material_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const price = <?php echo $material['retail_price']; ?>;
    const minQty = <?php echo $material['min_order_quantity']; ?>;
    const discountPercent = <?php echo $material['bulk_discount_percentage']; ?>;

    const quantityInput = document.getElementById('quantity');
    const originalTotalDisplay = document.getElementById('original_total');
    const discountDisplay = document.getElementById('discount_display');
    const finalTotalDisplay = document.getElementById('final_total');

    quantityInput.addEventListener('input', () => {
        const qty = parseFloat(quantityInput.value) || 0;
        const originalTotal = qty * price;
        let discount = 0;

        if (qty >= minQty && discountPercent > 0) {
            discount = (originalTotal * discountPercent) / 100;
        }

        const finalTotal = originalTotal - discount;

        originalTotalDisplay.textContent = 'Rs. ' + originalTotal.toFixed(2);
        discountDisplay.textContent = 'Rs. ' + discount.toFixed(2);
        finalTotalDisplay.textContent = 'Rs. ' + finalTotal.toFixed(2);
    });
</script>

</body>
</html>
