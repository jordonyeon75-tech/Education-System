<?php
require_once '../config/db_config.php'; // Database connection
include '../lib/authlib.php'; // Authentication library

// Redirect if user is not logged in or not a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../common/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch only enrolled courses with approved status
$stmt = $conn->prepare("
    SELECT c.id AS course_id, c.course_name, c.course_description, c.image
    FROM enrollment e
    JOIN course c ON e.course_id = c.id
    WHERE e.stu_id = ? AND e.status = 'Approved'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
include '../common/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolled Courses</title>
    <style>
.course-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 columns */
    gap: 20px; /* Space between the cards */
    margin: 20px;
}

.course-card {
    background-color: #FBF4DF; /* Fixed color */
    border: 1px solid #ccc;
    border-radius: 15px;
    padding: 15px;
    height: 250px; /* Set a consistent height */
    display: flex;
    flex-direction: column;
    text-align: left;
    box-sizing: border-box;
    position: relative;
    overflow: hidden; /* Hide anything that goes out of bounds */
}

/* The background image */
.course-card.with-image {
    background-position: bottom right; /* Position the image at the bottom-right corner */
    background-repeat: no-repeat; /* Don't repeat the image */
    height: 200px; /* Adjusted height for the background image section */
    padding-top: 30px; /* Space above the background image */
    padding-bottom: 30px; /* Space below the background image */
}

/* Style for the link (course title) */
.course-card .course-link {
    font-weight: bold;
    font-size: 1.2em;
    text-decoration: none;
    margin-bottom: 10px;
    z-index: 2; /* Ensure the title is on top of the image */
}

/* Style for the course description */
.course-card .course-description {
    font-size: 0.9em;
    margin-bottom: 10px;
    color: #555;
    z-index: 2; /* Ensure description is above the image */
}

/* Image inside course card (for fallback) */
.course-card .course-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    margin-top: 10px;
    display: none; /* Hide the image since it's now a background */
}

/* Responsive Design */
@media only screen and (max-width: 1024px) {
    .course-container {
        grid-template-columns: repeat(2, 1fr); /* 2 columns for tablets */
    }

    .course-card {
        height: 220px; /* Adjust height for tablets */
    }
}

@media only screen and (max-width: 600px) {
    .course-container {
        grid-template-columns: 1fr; /* 1 column for mobile devices */
    }

    .course-card {
        height: 200px; /* Adjust height for mobile */
    }
}


    </style>
</head>
<body>
<!-- Main Content -->
<div class="StudentContents" id="StudentContents">
    <div class="ContentsCon">
        <h2>Your Enrolled Courses</h2>
        <div class="course-container">
            <?php while ($row = $result->fetch_assoc()):
                $course_name = $row['course_name'];
                $description = $row['course_description'];
                $image = $row['image'] ? '../images/' . $row['image'] : '../images/default.jpg'; // Path to course image
                ?>
                <div class="course-card with-image" style="background-image: url('<?php echo htmlspecialchars($image); ?>');">
                    <a class="course-link" href="stu_materials.php?course_id=<?php echo $row['course_id']; ?>">
                        <?php echo htmlspecialchars($course_name); ?>
                    </a>
                    <p class="course-description"><?php echo htmlspecialchars($description); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
