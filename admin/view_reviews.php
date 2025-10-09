<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Delete review
if (isset($_GET['delete'])) {
    $review_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE review_id = $review_id");
    header("Location: view_reviews.php?msg=deleted");
    exit;
}

// Fetch all reviews
$query = "
    SELECT r.*, 
           u.name AS customer_name,
           s.name AS supplier_name
    FROM reviews r
    JOIN users u ON r.id = u.id
    JOIN users s ON r.supplier_id = s.id
    ORDER BY r.review_id DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Reviews - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this review?");
        }
    </script>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">All Reviews</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">Review deleted successfully.</div>
    <?php endif; ?>

    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['review_id'] ?></td>
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span style="color:<?= $i <= $row['rating'] ? '#f0ad4e' : '#ccc' ?>">&#9733;</span>
                        <?php endfor; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="?delete=<?= $row['review_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted">No reviews found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
