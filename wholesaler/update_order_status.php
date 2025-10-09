<?php
include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = intval($_POST['order_id']);
    $order_status = $_POST['order_status'];

    // Update order status
    $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $order_status, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Order status updated successfully!";

        // Fetch order and user info to send email
        $query = "
            SELECT o.order_id, o.total_amount, o.location, u.name, u.email
            FROM orders o
            JOIN users u ON o.id = u.id
            WHERE o.order_id = ?
        ";
        $stmt_user = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt_user, "i", $order_id);
        mysqli_stmt_execute($stmt_user);
        $result = mysqli_stmt_get_result($stmt_user);

        if ($result && mysqli_num_rows($result) > 0) {
            $order = mysqli_fetch_assoc($result);
            $user_email = $order['email'];
            $user_name = $order['name'];
            $total_amount = $order['total_amount'];
            $location = $order['location'];

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com';
                $mail->Password = 'ddgc zhft gohg bjmz';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nasiryt.827@gmail.com', 'Construction Material');
                $mail->addAddress($user_email, $user_name);

                $mail->isHTML(true);
                $mail->Subject = "Order Update #$order_id";
                $mail->Body = "
                    <h3>Dear $user_name,</h3>
                    <p>Your order <strong>#$order_id</strong> status has been updated.</p>
                    <ul>
                        <li><strong>Status:</strong> $order_status</li>
                        <li><strong>Total Amount:</strong> Rs. " . number_format($total_amount, 2) . "</li>
                        <li><strong>Delivery Location:</strong> $location</li>
                    </ul>
                    <p>Thank you for shopping with us.</p>
                    <p>Regards,<br>Construction Material</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "Order updated but email could not be sent. Mailer Error: " . $mail->ErrorInfo;
            }
        }

    } else {
        echo "Failed to update status.";
    }
}
?>
