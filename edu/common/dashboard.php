<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db_config.php';
require_once '../lib/authlib.php';  // Ensure this is included once at the top

// Query for notices (using prepared statements for security)
$sql = "SELECT id, title, message, image, created_at FROM notice_board ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("SQL query failed: " . $conn->error);
}

// Check if the user is logged in 
if (!isTeacher() && !isStudent()) {
    header("Location: ../common/login.php");
    exit();
}

// Include sidebar based on user role
if (isLoggedIn()) {
    // Check the role and include the appropriate sidebar
    include isTeacher() ? '../common/admin_sidebar.php' : '../common/sidebar.php';
} else {
    // Default to student sidebar if not logged in
    include '../common/sidebar.php';
}

?>

<body>
<?php 
if (isTeacher()) {
    echo '<div class="AdminContents" id="AdminContents">';
    echo '<div class="ContentsCon dashboard-container">';
} elseif (isStudent()) {
    echo '<div class="StudentContents" id="StudentContents">';
    echo '<div class="ContentsCon">';
}
?>

            <h1 class="dashboard-title">Dashboard</h1>
            <h2 class="section-title">Latest Notices</h2>

            <?php if ($result->num_rows > 0): ?>
                <div class="notices-container">
                    <ul class="notices-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="notice-card">
                                <h3 class="notice-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="notice-message"><?php echo htmlspecialchars($row['message']); ?></p>
                                <?php
                                if (!empty($row['image'])) {
                                    $imagePath = '../images/' . $row['image'];
                                    echo '<img class="notice-image" src="' . htmlspecialchars($imagePath) . '" alt="Notice Image" onclick="showFullScreen(this)">';
                                } else {
                                    echo '<p class="no-image">No image available</p>';
                                }
                                ?>
                                <p class="notice-date"><small>Posted on: <?php echo htmlspecialchars($row['created_at']); ?></small></p>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="no-notices">No notices available at the moment.</p>
            <?php endif; ?>
        </div>

        <!-- Full-screen image viewer -->
        <div id="imageModal" class="image-modal" onclick="closeFullScreen()">
            <img id="modalImage" class="modal-content" alt="Full Image">
        </div>
    </div>

    <script>
        function showFullScreen(img) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = img.src;
        }

        function closeFullScreen() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
