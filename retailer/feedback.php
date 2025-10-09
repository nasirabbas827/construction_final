<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;

if ($supplier_id <= 0) {
    die("<div class='container mt-5'><div class='alert alert-danger text-center'>Invalid supplier specified.</div></div>");
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        // Check if the user has already submitted a review for this supplier
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE supplier_id = ? AND id = ?");
        $check_stmt->bind_param("ii", $supplier_id, $user_id);
        $check_stmt->execute();
        $check_stmt->bind_result($review_count);
        $check_stmt->fetch();
        $check_stmt->close();
        
        if ($review_count > 0) {
             $error = "You have already submitted a review for this supplier. You can only submit one.";
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (supplier_id, id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $supplier_id, $user_id, $rating, $comment);
            if ($stmt->execute()) {
                $success = "Your feedback has been submitted successfully! Thank you.";
            } else {
                $error = "Error submitting feedback. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please provide a valid rating (1-5) and a comment.";
    }
}

// Fetch supplier name and company name
$supplier_sql = $conn->prepare("SELECT name FROM users WHERE id = ?");
$supplier_sql->bind_param("i", $supplier_id);
$supplier_sql->execute();
$supplier_result = $supplier_sql->get_result();
$supplier = $supplier_result->fetch_assoc();
$supplier_display_name = htmlspecialchars($supplier['company_name'] ?? $supplier['name']);

// Fetch all reviews for this supplier
$review_sql = $conn->prepare("
    SELECT r.*, u.name AS reviewer_name 
    FROM reviews r 
    JOIN users u ON r.id = u.id 
    WHERE r.supplier_id = ? 
    ORDER BY r.created_at DESC
");
$review_sql->bind_param("i", $supplier_id);
$review_sql->execute();
$reviews = $review_sql->get_result();

// Calculate average rating
$avg_rating_sql = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE supplier_id = ?");
$avg_rating_sql->bind_param("i", $supplier_id);
$avg_rating_sql->execute();
$avg_result = $avg_rating_sql->get_result();
$avg_data = $avg_result->fetch_assoc();
$avg_rating = number_format($avg_data['avg_rating'] ?? 0, 1);
$total_reviews = $avg_data['total_reviews'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback for <?php echo $supplier_display_name; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
               .feedback-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
        }

        /* Interactive Star Rating */
        .star-widget .star-icon {
            font-size: 36px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-widget .star-icon:hover, 
        .star-widget .star-icon.selected {
            color: var(--star-color);
        }
        
        /* Review Box Styling */
        .review-box {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .review-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .reviewer-header {
            border-bottom: 1px solid #f8f9fa;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .review-rating {
            font-size: 1.5rem;
            color: var(--star-color);
        }
        .review-comment {
            flex-grow: 1;
            font-style: italic;
            color: #495057;
        }

        /* Summary Block */
        .summary-block {
            background-color: #f8f9fa;
            border-left: 5px solid var(--primary-color);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .avg-display {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="feedback-container">
        <div class="text-center mb-5">
            <h2 class="mb-1 text-primary"><i class="fas fa-comments mr-2"></i> Supplier Feedback Hub</h2>
            <h4 class="text-secondary">**<?php echo $supplier_display_name; ?>**</h4>
        </div>

        <?php if (isset($success)) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php } elseif (isset($error)) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php } ?>

        <div class="row">
            
            <div class="col-lg-4">
                <div class="summary-block h-100">
                    <h5 class="mb-3 text-dark"><i class="fas fa-chart-bar mr-2"></i>Overall Rating</h5>
                    <div class="text-center">
                        <div class="avg-display"><?php echo $avg_rating; ?></div>
                        <div class="review-rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++) { 
                                echo $i <= $avg_rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-muted"></i>';
                            } ?>
                        </div>
                        <p class="text-muted mb-0">Based on **<?php echo $total_reviews; ?>** reviews</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="review-box p-4 border border-info">
                    <h4 class="mb-3 text-info"><i class="fas fa-pencil-alt mr-2"></i>Add Your Feedback</h4>
                    <form method="POST">
                        <div class="form-group mb-4">
                            <label class="text-dark"><strong>Your Rating:</strong></label><br>
                            <div id="star-container" class="star-widget">
                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                    <i class="far fa-star star-icon" data-value="<?php echo $i; ?>"></i>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="rating" id="rating" required>
                        </div>

                        <div class="form-group">
                            <label class="text-dark"><strong>Your Comment:</strong></label>
                            <textarea name="comment" class="form-control" rows="4" required placeholder="Share your experience with this supplier..."></textarea>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane mr-2"></i> Submit Feedback</button>
                            <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel / Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <h4 class="mt-5 mb-4 border-bottom pb-2 text-dark"><i class="fas fa-clipboard-list mr-2"></i>Customer Reviews (<?php echo $total_reviews; ?>)</h4>

        <div class="row">
            <?php if ($reviews->num_rows > 0) { ?>
                <?php while ($row = $reviews->fetch_assoc()) { ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="review-box shadow-sm">
                            <div class="reviewer-header">
                                <h6 class="mb-0 text-primary"><i class="fas fa-user-circle mr-1"></i> **<?php echo htmlspecialchars($row['reviewer_name']); ?>**</h6>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i> Posted on <?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                            </div>
                            
                            <div class="review-rating mb-2">
                                <?php for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $row['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                } ?>
                            </div>
                            
                            <p class="review-comment small"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="col-12">
                    <div class="alert alert-info text-center mt-3">
                        <i class="fas fa-info-circle mr-2"></i> Be the first to leave a review for **<?php echo $supplier_display_name; ?>**!
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JavaScript for interactive rating stars
const stars = document.querySelectorAll('.star-icon');
const ratingInput = document.getElementById('rating');

stars.forEach(star => {
    star.addEventListener('click', () => {
        const value = star.getAttribute('data-value');
        ratingInput.value = value;
        
        // Remove 'selected' class and switch to 'far' (outline)
        stars.forEach(s => {
            s.classList.remove('selected', 'fas');
            s.classList.add('far');
        });
        
        // Add 'selected' class and switch to 'fas' (solid) up to the selected value
        for (let i = 0; i < value; i++) {
            stars[i].classList.add('selected', 'fas');
            stars[i].classList.remove('far');
        }
    });
    
    // Hover effects (optional, but enhances UI)
    star.addEventListener('mouseover', () => {
        const value = star.getAttribute('data-value');
        for (let i = 0; i < value; i++) {
            stars[i].style.color = 'var(--star-color)';
        }
    });

    star.addEventListener('mouseout', () => {
        stars.forEach(s => {
            if (!s.classList.contains('selected')) {
                s.style.color = '#ddd';
            }
        });
    });
});
</script>

</body>
</html>