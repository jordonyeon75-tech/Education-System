<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/db_config.php';
require_once '../lib/authlib.php';

if (!isLoggedIn() || !isTeacher()) {
    header('Location: ../common/login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Fetch available courses for the teacher
$sqlCourses = "SELECT id, course_name FROM course WHERE teacher_id = ?";
$stmtCourses = $conn->prepare($sqlCourses);
if (!$stmtCourses) {
    die("SQL Error: " . $conn->error);
}
$stmtCourses->bind_param("i", $teacher_id);
$stmtCourses->execute();
$resultCourses = $stmtCourses->get_result();
$courses = $resultCourses->fetch_all(MYSQLI_ASSOC);

// Handle form submission for file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $fileName = null;
    $filePath = null; // Initialize to avoid undefined variable warning

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        $uploadDir = __DIR__ . '/../uploads/';  
        $fileName = time() . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            die('File upload failed.');
        }
    }

    // Check if file upload was successful before proceeding with the DB insert
    if ($filePath) {
        // Insert into the database
        $stmt = $conn->prepare("INSERT INTO course_material (course_id, user_id, description, file, date, updated_date, updated_by) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }
        $stmt->bind_param("iisssi", $course_id, $teacher_id, $description, $fileName, $date, $teacher_id);

        if ($stmt->execute()) {
            $message = "Material uploaded successfully!";
        } else {
            $message = "Failed to upload material: " . $stmt->error;
        }
    } else {
        $message = "No file uploaded.";
    }
}

// Fetch uploaded materials for the selected course (AJAX will call this)
if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    $sqlMaterials = "SELECT cm.*, c.course_name FROM course_material cm 
                     INNER JOIN course c ON cm.course_id = c.id 
                     WHERE c.teacher_id = ? AND cm.course_id = ?";
    $stmtMaterials = $conn->prepare($sqlMaterials);
    if (!$stmtMaterials) {
        die("SQL Error: " . $conn->error);
    }
    $stmtMaterials->bind_param("ii", $teacher_id, $course_id);
    $stmtMaterials->execute();
    $resultMaterials = $stmtMaterials->get_result();
    $materials = $resultMaterials->fetch_all(MYSQLI_ASSOC);

    // Generate HTML for the materials table
    $output = '<table border="1" class="datatable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>File</th>
                        <th>Uploaded At</th>
                    </tr>
                </thead>
                <thead>
            <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                <td colspan="100"></td>

            </tr>
            </thead>
                <tbody>';
    if (count($materials) > 0) {
        foreach ($materials as $material) {
            $output .= '<tr>
                        <td>' . $material['course_name'] . '</td>
                        <td>' . $material['date'] . '</td>
                        <td>' . $material['description'] . '</td>
                        <td>';
            if (!empty($material['file'])) {
                $output .= '<a href="../uploads/' . $material['file'] . '" target="_blank" class="AdminFormBtn">Download</a>';
            } else {
                $output .= 'No file uploaded';
            }
            $output .= '</td>
                        <td>' . $material['updated_date'] . '</td>
                    </tr>';
        }
    } else {
        $output .= '<tr><td colspan="5">No materials uploaded yet for this course.</td></tr>';
    }
    $output .= '</tbody></table>';

    echo $output; // Return the table HTML to the AJAX call
    exit;
}
?>

<?php include '../common/admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload and View Course Materials</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">
        <!-- edit everything from here -->
    <h1>Upload and View Course Materials</h1>

    <!-- Display success or error message -->
    <?php if (isset($message)): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <div class ="AdminForm">    
        <!-- Upload Form -->
        <form action="course_material.php" method="POST" enctype="multipart/form-data">
            <label for="course">Course:</label>
            <select name="course_id" id="course" required>
                <option value="">--Select Course--</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="date">Date:</label>
            <input type="date" name="date" id="date" required>
            <br><br>

            <label for="description">Description:</label>
            <textarea name="description" id="description" placeholder="Enter material description" required></textarea>
            <br><br>

            <label for="file">Upload File:</label>
            <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar" required>
            <br><br>

            <button type="submit" class="AdminFormBtn">Upload Material</button>
        </form>
    </div>
    <!-- Display Uploaded Materials -->
    <h2>Your Uploaded Materials</h2>
    <div class="AdminTablePhone">
        <div id="materials-table" >
            <!-- AJAX-loaded materials will be shown here -->
        </div>
    </div>
    </div>
</div>
<!--end  Main Content -->
    <script>
        // Fetch and display materials when a course is selected
        $('#course').change(function() {
            var courseId = $(this).val();
            if (courseId) {
                $.ajax({
                    url: 'course_material.php',  // This is the same page
                    type: 'GET',
                    data: { course_id: courseId },  // Send the selected course ID
                    success: function(response) {
                        // Replace the existing table content with the new table
                        $('#materials-table').html(response);
                    },
                    error: function() {
                        alert('Error loading materials');
                    }
                });
            } else {
                $('#materials-table').html('<p>Please select a course to view materials.</p>');
            }
        });
    </script>

</body>
</html>
