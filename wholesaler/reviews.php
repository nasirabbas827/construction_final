<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "wholesaler") {
    header("location: ../index.php");
    exit;
}

$wholesaler_id = $_SESSION["id"];

// Fetch wholesaler name
$wholesaler_sql = $conn->prepare("SELECT name FROM users WHERE id = ?");
$wholesaler_sql->bind_param("i", $wholesaler_id);
$wholesaler_sql->execute();
$wholesaler_result = $wholesaler_sql->get_result();
$wholesaler = $wholesaler_result->fetch_assoc();

// Fetch all reviews for this wholesaler
$review_sql = $conn->prepare("
    SELECT r.*, u.name AS reviewer_name, u.role AS reviewer_role
    FROM reviews r
    JOIN users u ON r.id = u.id
    WHERE r.supplier_id = ?
    ORDER BY r.created_at DESC
");
$review_sql->bind_param("i", $wholesaler_id);
$review_sql->execute();
$reviews = $review_sql->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reviews - <?php echo htmlspecialchars($wholesaler['name']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .star {
            color: gold;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3 class="text-center mb-4">Reviews for <?php echo htmlspecialchars($wholesaler['name']); ?></h3>

    <?php if ($reviews->num_rows > 0) { ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Reviewer Name</th>
                        <th>Role</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $reviews->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['reviewer_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($row['reviewer_role'])); ?></td>
                            <td>
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $row['rating'] ? "<span class='star'>&#9733;</span>" : "<span class='star' style='color:#ccc;'>&#9733;</span>";
                                }
                                ?>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($row['comment'])); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="alert alert-info text-center">No reviews yet for this wholesaler.</div>
    <?php } ?>

   
</div>

</body>
</html>
