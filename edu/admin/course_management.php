<?php
// Include database configuration and authentication logic
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
require_once '../lib/compresslib.php';
require_once '../lib/courselib.php';

// Check if the user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Redirect to the login page or homepage if the user is not logged in or not an admin
    header('Location: ../common/login.php');
    exit();
}


// Fetch teachers from the database
$teacher_sql = "SELECT * FROM user WHERE role_id = 2";  // Assuming role_id = 2 is for teachers
$teacher_result = $conn->query($teacher_sql);

$teachers = [];  // Initialize an empty array to store teachers
if ($teacher_result->num_rows > 0) {
    while ($teacher = $teacher_result->fetch_assoc()) {
        $teachers[] = $teacher;  // Add each teacher to the $teachers array
    }
} else {
    echo "No teachers found.";  // Handle case where no teachers are found
}



// Fetch the notices from the database (this should happen regardless of POST/GET)
$sql = "SELECT c.*, u.first_name, u.last_name 
        FROM course c
        LEFT JOIN user u ON c.teacher_id = u.id";
$result = $conn->query($sql);


// Check if the query was successful
if (!$result) {
    die("Error: " . $conn->error);  // If there is an error in the query
}


// Create Operation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Ensure required fields are set
        $course_name = isset($_POST['course_name']) ? $_POST['course_name'] : null;

        $image = isset($_POST['image']) ? $_POST['image'] : null;
        $course_description = isset($_POST['course_description']) ? $_POST['course_description'] : null;
        $course_fee = isset($_POST['course_fee']) ? $_POST['course_fee'] : null;
        $teacher_id = isset($_POST['teacher_id']) ? $_POST['teacher_id'] : null; // Assuming teacher_id is passed via the form
        $status = isset($_POST['status']) ? $_POST['status'] : null; // Assuming 'active' or 'inactive'


        if ($_POST['action'] == 'create') {

            $image = handleImageUpload($_FILES['image']);

            // Call the function to generate the course code
            $course_code = generateCourseCode($conn, $course_name);


            if ($image !== null || !isset($_FILES['image'])) {
                // If image was uploaded or no image is provided, proceed with the insert
                $stmt = null;

                // Prepare the SQL query to insert the course into the database
                if ($image) {

                    // If the image image was uploaded, include it in the insert
                    $stmt = $conn->prepare("INSERT INTO course (course_name, course_code, image, course_description, course_fee, teacher_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssis", $course_name, $course_code, $image, $course_description, $course_fee, $teacher_id, $status);
                } else {

                    // If no image is uploaded, exclude it from the insert
                    $stmt = $conn->prepare("INSERT INTO course (course_name, course_code, course_description, course_fee, teacher_id, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $course_name, $course_code, $course_description, $course_fee, $teacher_id, $status);
                }


                // Execute the query
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Course created successfully']);
                    exit(); // Exit after successful creation
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create course']);
                    exit(); // Exit if creation fails
                }
            } else {
                // If required fields are missing or status is invalid
                echo json_encode(['status' => 'error', 'message' => 'Invalid image. Course not created.']);
                exit(); // Exit if validation fails
            }
        }
    }


    // Update Operation
    if ($_POST['action'] == 'update') {
        $id = $_POST['id']; // Get the notice ID from the forms

        $image = null; // Initialize image variable to null

        // Check if the image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle image upload (returns the file name or an error message)
            $image = handleImageUpload($_FILES['image']);


            // If the image upload fails, return an error message
            if ($image === null) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image. Notice not updated.']);
                exit(); // Exit if image is invalid
            }
        }

        // Prepare the update query
        if ($image) {
            // If image is uploaded, include it in the update query
            $stmt = $conn->prepare("UPDATE course SET  image = ?, course_description = ?, course_fee = ?, teacher_id = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssssi", $image, $course_description, $course_fee, $teacher_id, $status, $id);
        } else {
            // If no image is uploaded, just update the text fields
            $stmt = $conn->prepare("UPDATE course SET course_description = ?, course_fee = ?, teacher_id = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssi", $course_description, $course_fee, $teacher_id, $status, $id);
        }
        // Execute the update query
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Course updated successfully']);
            exit(); // Exit after successful update
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update course']);
            exit(); // Exit if update fails
        }
    }
}
?>



<?php
include '../common/admin_sidebar.php';
?>


<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">
        <!-- edit everything from here -->
        <h1>Course Management</h1>

        <!-- Create New Notice Form -->
        <button class="AdminFormBtn" onclick="toggleCreateForm()">
            Create Notice
        </button>

        <div class="AdminForm" id="createFrom" style="display: none;">
            <h3>Create New Course</h3>
            <form id="createCourseForm" enctype="multipart/form-data">
                <!-- Course Name -->
                <input type="text" id="course_name" placeholder="Course Name" required>



                <!-- Course Image -->
                <input type="file" id="image" accept="image/*" required>

                <!-- Course Description -->
                <textarea id="course_description" placeholder="Course Description" required></textarea>

                <!-- Course Fee -->
                <input type="number" id="course_fee" placeholder="Course Fee" step="0.01" required>

                <!-- Teacher Dropdown (Populate dynamically from the database) -->
                <label for="teacher">Select Teacher:</label>
                <select id="teacher" required>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['first_name'] . " " . $teacher['last_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Status -->
                <label for="status">Status:</label>
                <select id="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Submit Button -->
                <button type="submit" class="AdminFormBtn">Create Course</button>
            </form>
        </div>

        <h3>Existing Courses</h3>
        <input type="text" id="searchInputCourse" class="AdminSearch" placeholder="Search..." />
        <div class="AdminTablePhone">
            <table id="courseTable" class="datatable table table-striped" border="1">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Course Name</th>
                        <th>Course Code</th>
                        <th>Image</th>
                        <th>Course Description</th>
                        <th>Course Fee</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <thead>
                    <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                        <td colspan="100"></td>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while ($course = $result->fetch_assoc()): ?>
                        <tr id="course-<?php echo $course['id']; ?>">
                            <td><?php echo $counter; ?></td>
                            <td id="course-name-<?php echo $course['id']; ?>" data-column="course_name"><?php echo $course['course_name']; ?></td>
                            <td id="course-code-<?php echo $course['id']; ?>" data-column="course_code"><?php echo $course['course_code']; ?></td>
                            <td id="course-image-<?php echo $course['id']; ?>" data-column="image">
                                <?php
                                if (!empty($course['image'])) {
                                    $imagePath = '../images/' . $course['image'];
                                    echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Course Image" width="100" height="100">';
                                } else {
                                    echo 'No image available';
                                }
                                ?>
                            </td>
                            <td id="course-description-<?php echo $course['id']; ?>" data-column="course_description"><?php echo $course['course_description']; ?></td>
                            <td id="course-fee-<?php echo $course['id']; ?>" data-column="course_fee"><?php echo $course['course_fee']; ?></td>
                            <td id="course-teacher-<?php echo $course['id']; ?>" data-column="teacher"><?php echo $course['first_name'] . " " . $course['last_name']; ?></td>
                            <td id="course-status-<?php echo $course['id']; ?>" data-column="status"><?php echo $course['status']; ?></td>
                            <td>
                                <button onclick="editCourse(<?php echo $course['id']; ?>); document.getElementById('editCourseModal').scrollIntoView({ behavior: 'smooth' });">Edit</button>

                            </td>
                        </tr>
                    <?php
                        $counter++;
                    endwhile; ?>
                </tbody>

            </table>
        </div>
        <div id="paginationCourse" class="AdminTableBolowBtn"></div>

        <!-- Edit Course Modal (Hidden by default) -->
        <div id="editCourseModal" style="display:none;" class="AdminForm">
            <h3>Edit Course</h3>
            <form id="editCourseForm" enctype="multipart/form-data" onsubmit="updateCourse(event)">
                <!-- Hidden field for course ID -->
                <input type="hidden" id="editCourseId">

                <!-- Course Name Input -->
                <label for="editCourseName">Course Name:</label>
                <textarea id="editCourseName" placeholder="Course Name" required readonly></textarea>


                <!-- Course Description Input -->
                <label for="editCourseDescription">Course Description:</label>
                <textarea id="editCourseDescription" placeholder="Course Description" required></textarea>

                <!-- Course Fee Input -->
                <label for="editCourseFee">Course Fee:</label>
                <input type="number" id="editCourseFee" placeholder="Course Fee" step="0.01" required>

                <!-- Teacher Selection Dropdown -->
                <label for="editTeacher">Select Teacher:</label>
                <select id="editTeacher" required>
                    <!-- Teachers will be populated dynamically -->
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['first_name'] . " " . $teacher['last_name']; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Status Selection -->
                <label for="editStatus">Status:</label>
                <select id="editStatus" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Course Image Input (Optional) -->
                <label for="editImage">Change Image:</label>
                <input type="file" id="editImage" name="image" accept="image/*">

                <!-- Display current image (optional) -->
                <div id="currentImageContainer" style="display:none;">
                    <label>Current Image:</label>
                    <img id="currentImage" src="" alt="Current Course Image" width="100">
                </div>

                <!-- Action Buttons -->
                <button type="submit" class="AdminFormBtn">Update Course</button>
                <button type="button" onclick="closeEditModal()" class="AdminFormBtn">Cancel</button>
            </form>
        </div>



        <?php include '../common/footer.php'; ?>
    </div>
</div>
<!--end  Main Content -->
<script>
    //show create from
    function toggleCreateForm() {
        const formDiv = document.getElementById('createFrom');
        if (formDiv.style.display === 'none') {
            formDiv.style.display = 'block';
        } else {
            formDiv.style.display = 'none';
        }
    }

    // Create Course via AJAX
    document.getElementById('createCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();


        var courseName = document.getElementById('course_name').value;
        var image = document.getElementById('image').files[0]; // If image is used
        var courseDescription = document.getElementById('course_description').value;
        var courseFee = document.getElementById('course_fee').value;
        var teacherId = document.getElementById('teacher').value;
        var status = document.getElementById('status').value;

        // Client-side validation for course fields (letters and spaces only)
        var namePattern = /^[A-Za-z\s]+$/;
        if (!namePattern.test(courseName)) {
            alert("Course name should only contain letters and spaces.");
            return;
        }

        var data = new FormData();
        data.append('action', 'create');
        data.append('course_name', courseName);
        data.append('course_description', courseDescription);
        data.append('course_fee', courseFee);
        data.append('teacher_id', teacherId);
        data.append('status', status);
        if (image) {
            data.append('image', image);
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'course_management.php', true);
        xhr.onload = function() {
            var response = JSON.parse(xhr.responseText);
            alert(response.message);
            if (response.status === 'success') {
                location.reload();
            }
        };
        xhr.send(data);
    });

    function editCourse(id) {
        // Find the course row by id
        var courseRow = document.getElementById('course-' + id);

        // Use the unique IDs or data attributes for each column
        var courseName = document.getElementById('course-name-' + id).innerText;
        var courseCode = document.getElementById('course-code-' + id).innerText;
        var courseDescription = document.getElementById('course-description-' + id).innerText;
        var courseFee = document.getElementById('course-fee-' + id).innerText;
        var teacherName = document.getElementById('course-teacher-' + id).innerText;
        var status = document.getElementById('course-status-' + id).innerText;

        // Set the values in the edit form
        document.getElementById('editCourseId').value = id;
        document.getElementById('editCourseName').value = courseName; // Set course name
        document.getElementById('editCourseDescription').value = courseDescription;
        document.getElementById('editCourseFee').value = courseFee;

        // Set teacher in the dropdown
        var teacherSelect = document.getElementById('editTeacher');
        for (var i = 0; i < teacherSelect.options.length; i++) {
            if (teacherSelect.options[i].innerText === teacherName) {
                teacherSelect.selectedIndex = i;
                break;
            }
        }

        // Set status in the dropdown
        document.getElementById('editStatus').value = status.toLowerCase();

        // Display the current image in the form if available
        var imageCell = document.getElementById('course-image-' + id).querySelector('img');
        var currentImageContainer = document.getElementById('currentImageContainer');
        var currentImage = document.getElementById('currentImage');

        if (imageCell) {
            currentImageContainer.style.display = 'block';
            currentImage.src = imageCell.src; // Set image path
        } else {
            currentImageContainer.style.display = 'none'; // Hide image if none
        }

        // Show the modal
        document.getElementById('editCourseModal').style.display = 'block';
    }

    // Function to update the course via AJAX
    function updateCourse(event) {
        event.preventDefault(); // Prevent form submission

        // Get form values from the modal
        var id = document.getElementById('editCourseId').value;
        var courseName = document.getElementById('editCourseName').value; // Get course name
        var courseDescription = document.getElementById('editCourseDescription').value;
        var courseFee = document.getElementById('editCourseFee').value;
        var teacherId = document.getElementById('editTeacher').value;
        var status = document.getElementById('editStatus').value;
        var image = document.getElementById('editImage').files[0]; // If image is selected

        // Prepare the FormData object for AJAX
        var formData = new FormData();
        formData.append('action', 'update');
        formData.append('id', id);
        formData.append('course_name', courseName); // Append course name
        formData.append('course_description', courseDescription);
        formData.append('course_fee', courseFee);
        formData.append('teacher_id', teacherId);
        formData.append('status', status);

        // Append image if available
        if (image) {
            formData.append('image', image);
        }

        // Send the AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'course_management.php', true);
        xhr.onload = function() {
            var response = JSON.parse(xhr.responseText);
            alert(response.message); // Show response message
            if (response.status === 'success') {
                location.reload(); // Reload the page after success
            }
        };
        xhr.send(formData);
    }
</script>

</body>

</html>