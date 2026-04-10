<?php
require_once '../config/db_config.php'; // Database connection
include '../lib/authlib.php'; // Authentication library

// Redirect if user is not logged in or not a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../common/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Check if the student is enrolled in the selected course
$enroll_check_stmt = $conn->prepare("
    SELECT e.status
    FROM enrollment e
    WHERE e.stu_id = ? AND e.course_id = ? AND e.status = 'Approved'
");
$enroll_check_stmt->bind_param("ii", $student_id, $course_id);
$enroll_check_stmt->execute();
$enroll_check_stmt->store_result();

if ($enroll_check_stmt->num_rows === 0) {
    echo "<p>You are not enrolled in this course or the course does not exist.</p>";
    exit();
}

// Fetch course materials for the selected course
$materials_stmt = $conn->prepare("
    SELECT description, file
    FROM course_material
    WHERE course_id = ?
");
$materials_stmt->bind_param("i", $course_id);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
?>

<?php
include '../common/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials</title>
</head>
<body>
<!-- Main Content -->
<div class="StudentContents" id="StudentContents">
    <div class="ContentsCon">
        <h1>Course Materials</h1>
        <?php if ($materials_result->num_rows > 0): ?>
            <table class="classroom-table">
                <thead>
                <tr>
                    <th>Description</th>
                    <th>Download</th>
                </tr>
                </thead>
                <tbody class="classroom-body">
                <?php while ($row = $materials_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><a href="materials/<?php echo htmlspecialchars($row['file']); ?>" download>Download</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No materials available for this course.</p>
        <?php endif; ?>
    </div>
</div>
<!-- End Main Content -->
</body>
</html>

<?php
$materials_stmt->close();
$enroll_check_stmt->close();
$conn->close();
?>
