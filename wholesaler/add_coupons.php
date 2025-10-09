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

$supplier_id = $_SESSION["id"];
$success = $error = "";
$edit_coupon = null;

// =====================
// HANDLE ADD / EDIT COUPON
// =====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $code = strtoupper(trim($_POST["code"]));
    $description = trim($_POST["description"]);
    $discount_percent = intval($_POST["discount_percent"]);
    $expire = $_POST["expire"];
    $coupon_id = isset($_POST["coupon_id"]) ? intval($_POST["coupon_id"]) : 0;

    if (empty($title) || empty($code) || $discount_percent < 1 || $discount_percent > 100) {
        $error = "Please provide a valid title, coupon code, and discount percent (1-100).";
    } else {
        // Check for duplicate code
        $check_sql = "SELECT coupon_id FROM coupons WHERE code = ? AND sender_id = ? " . ($coupon_id ? "AND coupon_id != ?" : "");
        $check_stmt = $conn->prepare($check_sql);
        if ($coupon_id) {
            $check_stmt->bind_param("iii", $code, $supplier_id, $coupon_id);
        } else {
            $check_stmt->bind_param("si", $code, $supplier_id);
        }
        $check_stmt->execute();
        $res = $check_stmt->get_result();
        if ($res->num_rows > 0) {
            $error = "Coupon code already exists.";
        } else {
            if ($coupon_id) {
                // Update existing coupon
                $update_stmt = $conn->prepare(
                    "UPDATE coupons SET title=?, code=?, description=?, discount_percent=?, expire=? WHERE coupon_id=? AND sender_id=?"
                );
                $update_stmt->bind_param("sssisii", $title, $code, $description, $discount_percent, $expire, $coupon_id, $supplier_id);
                $update_stmt->execute();
                if ($update_stmt->affected_rows > 0) {
                    $success = "Coupon updated successfully!";
                } else {
                    $error = "Failed to update coupon.";
                }
                $update_stmt->close();
            } else {
                // Insert new coupon
                $insert_stmt = $conn->prepare(
                    "INSERT INTO coupons (title, code, description, discount_percent, expire, sender_id) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $insert_stmt->bind_param("sssisi", $title, $code, $description, $discount_percent, $expire, $supplier_id);
                if ($insert_stmt->execute()) {
                    $success = "Coupon added successfully!";
                } else {
                    $error = "Failed to add coupon.";
                }
                $insert_stmt->close();
            }

            // =====================
            // SEND EMAIL TO ALL USERS
            // =====================
            if ($success) {
                $users_result = mysqli_query($conn, "SELECT name, email FROM users WHERE status='approved'");
                if ($users_result && mysqli_num_rows($users_result) > 0) {
                    while ($user = mysqli_fetch_assoc($users_result)) {
                        $user_name = $user['name'];
                        $user_email = $user['email'];

                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'nasiryt.827@gmail.com'; // your email
                            $mail->Password = 'ddgc zhft gohg bjmz'; // your app password
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;

                            $mail->setFrom('nasiryt.827@gmail.com', 'Construction Material');
                            $mail->addAddress($user_email, $user_name);

                            $mail->isHTML(true);
                            $mail->Subject = "New Coupon Available: {$title}";
                            $mail->Body = "
                                <h3>Dear $user_name,</h3>
                                <p>We have a new coupon for you!</p>
                                <ul>
                                    <li><strong>Title:</strong> {$title}</li>
                                    <li><strong>Code:</strong> {$code}</li>
                                    <li><strong>Discount:</strong> {$discount_percent}%</li>
                                    <li><strong>Description:</strong> {$description}</li>
                                    <li><strong>Valid Until:</strong> {$expire}</li>
                                </ul>
                                <p>Use this coupon on your next order to get a discount!</p>
                                <p>Regards,<br>Construction Material</p>
                            ";

                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Coupon email failed for {$user_email}: " . $mail->ErrorInfo);
                        }
                    }
                }
            }
        }
        $check_stmt->close();
    }
}

// =====================
// HANDLE DELETE
// =====================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM coupons WHERE coupon_id=? AND sender_id=?");
    $stmt->bind_param("ii", $delete_id, $supplier_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $success = "Coupon deleted successfully!";
    } else {
        $error = "Failed to delete coupon.";
    }
    $stmt->close();
}

// =====================
// HANDLE EDIT - fetch data
// =====================
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_id=? AND sender_id=?");
    $stmt->bind_param("ii", $edit_id, $supplier_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $edit_coupon = $res->fetch_assoc();
    } else {
        $error = "Coupon not found.";
    }
    $stmt->close();
}

// =====================
// FETCH ALL COUPONS
// =====================
$coupons_stmt = $conn->prepare("SELECT * FROM coupons WHERE sender_id=? ORDER BY expire ASC");
$coupons_stmt->bind_param("i", $supplier_id);
$coupons_stmt->execute();
$coupons = $coupons_stmt->get_result();
$coupons_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Coupons</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow mb-4">
        <div class="card-body">
            <h3 class="text-center mb-4"><?php echo $edit_coupon ? "Edit Offers & Coupon" : "Add New Offers & Coupon"; ?></h3>

            <?php if ($success) { ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>
            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>

            <!-- Add/Edit Form -->
            <form method="POST" class="mb-5">
                <input type="hidden" name="coupon_id" value="<?php echo $edit_coupon ? $edit_coupon['coupon_id'] : ''; ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo $edit_coupon ? htmlspecialchars($edit_coupon['title']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Coupon Code</label>
                        <input type="text" name="code" class="form-control" required value="<?php echo $edit_coupon ? htmlspecialchars($edit_coupon['code']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Discount Percent (%)</label>
                        <input type="number" name="discount_percent" class="form-control" min="1" max="100" required value="<?php echo $edit_coupon ? $edit_coupon['discount_percent'] : ''; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Expire Date</label>
                        <input type="date" name="expire" class="form-control" required value="<?php echo $edit_coupon ? $edit_coupon['expire'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"><?php echo $edit_coupon ? htmlspecialchars($edit_coupon['description']) : ''; ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary"><?php echo $edit_coupon ? "Update Coupon" : "Add Coupon"; ?></button>
                    <?php if($edit_coupon) { ?>
                        <a href="add_coupons.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php } ?>
                </div>
            </form>

            <h4>Existing Coupons</h4>
            <?php if ($coupons->num_rows > 0) { ?>
                <table class="table table-bordered mt-3">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Discount %</th>
                            <th>Description</th>
                            <th>Expire Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $coupons->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['coupon_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['code']); ?></td>
                                <td><?php echo $row['discount_percent']; ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo $row['expire']; ?></td>
                                <td>
                                    <a href="?edit_id=<?php echo $row['coupon_id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="?delete_id=<?php echo $row['coupon_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this coupon?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="alert alert-info mt-3">No coupons added yet.</div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
