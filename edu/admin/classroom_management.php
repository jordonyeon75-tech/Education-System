<?php
// Include necessary files
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
require_once '../lib/compresslib.php';

// Check if the user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Redirect to the login page or homepage if the user is not logged in or not an admin
    header('Location: ../common/login.php');
    exit();
}

// Fetch the available courses from the course table (this happens regardless of POST/GET)
$sql = "SELECT id, course_name FROM course";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching courses: " . $conn->error);
}

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch existing classroom records for display
$classroom_sql = "SELECT c.id, c.course_id, c.venue, c.start_time, c.end_time, c.class_date, cr.course_name 
                  FROM classroom c
                  JOIN course cr ON c.course_id = cr.id";
$classroom_result = $conn->query($classroom_sql);

if (!$classroom_result) {
    die("Error fetching classroom records: " . $conn->error);
}

$classroom_records = [];
while ($row = $classroom_result->fetch_assoc()) {
    $classroom_records[] = $row;
}

// Handle POST requests (Create, Update, Delete operations)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Ensure the fields for create and update operations are set
        $class_date = isset($_POST['class_date']) ? $_POST['class_date'] : null;
        $course_id = isset($_POST['course_ids']) ? $_POST['course_ids'] : null;
        $venue = isset($_POST['venue']) ? $_POST['venue'] : null;
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;


        // CREATE Operation
        if ($_POST['action'] == 'create') {

            // Prepare SQL query to insert the classroom record
            $stmt = $conn->prepare("INSERT INTO classroom (course_id, venue, class_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)");

            // Bind parameters for the insert
            $stmt->bind_param("issss", $course_id, $venue, $class_date, $start_time, $end_time); // "isssss" for int, string, string, string, string, string

            // Execute the query and check if it succeeds
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Classroom record created successfully']);
                exit(); // Exit after successful creation
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create classroom record']);
                exit(); // Exit if creation fails
            }
        }

        // UPDATE Operation
        if ($_POST['action'] == 'update') {
            $classroom_id = isset($_POST['classroom_id']) ? $_POST['classroom_id'] : null;
            $new_course_id = isset($_POST['new_course_id']) ? $_POST['new_course_id'] : null;
            $class_date = isset($_POST['class_date']) ? $_POST['class_date'] : null;
            $venue = isset($_POST['venue']) ? $_POST['venue'] : null;
            $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
            $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;

            // Check if required fields are provided
            if (!$classroom_id || !$new_course_id || !$venue || !$class_date || !$start_time || !$end_time) {
                echo json_encode(['status' => 'error', 'message' => 'Classroom ID, Course, Venue, Date, Start Time, and End Time are required']);
                exit(); // Exit if any required field is missing
            }

            // Prepare and execute the update query
            $stmt = $conn->prepare("UPDATE classroom SET course_id = ?, venue = ?, class_date = ?, start_time = ?, end_time = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("issssi", $new_course_id, $venue, $class_date, $start_time, $end_time, $classroom_id);

            // Execute the statement and check for success
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Classroom record updated successfully']);
                exit(); // Exit after successful update
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update classroom record']);
                exit(); // Exit if update fails
            }
        }


        // Delete Operation
        if ($_POST['action'] == 'delete') {
            $id = $_POST['id']; // Get the notice ID from the form

            // Now, delete the record from the database
            $stmt = $conn->prepare("DELETE FROM classroom WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Classroom deleted successfully']);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete classroom']);
                exit();
            }
        }
    }
    exit();
}

?>
<?php
include '../common/admin_sidebar.php';
?>

<body>
<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">
        <!-- edit everything from here -->
        <h1>Classroom Management</h1>

        <!-- Create New Notice Form -->
        <button class="AdminFormBtn" onclick="toggleCreateForm()">
            Create Notice
        </button>

        <div class="AdminForm" id="createFrom" style="display: none;">
        <h3>Create New Classroom</h3>
        <form id="createForm">
            <label for="class_date">Select Date:</label>
            <input type="date" id="class_date" name="class_date" required>

            <label for="courses">Select Course:</label>
            <select id="courses" name="course_ids" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="venue">Enter Venue:</label>
            <input type="text" id="venue" name="venue" required>

            <!-- Start Time -->
            <label for="start_time">Start Time:</label>
            <input type="time" id="start_time" name="start_time" required>

            <!-- End Time -->
            <label for="end_time">End Time:</label>
            <input type="time" id="end_time" name="end_time" required>

            <button type="submit" id="submitBtn" class="AdminFormBtn">Create Classroom Record</button>
        </form>
        </div>

        <h3>Existing Classroom Records</h3>

        <label>Search</label>
        <input type="text" id="searchInputClassroom" class="AdminSearch" placeholder="Search..." />
        <div class="AdminTablePhone">
    <table id="classroomTable"class="datatable table table-striped" border="1">


            <thead>
                <tr>
                    <th>No</th> <!-- Added header for serial number -->
                    <th>Course</th>
                    <th>Venue</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Date</th>
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
                $counter = 1; // Initialize counter
                foreach ($classroom_records as $record): ?>
                    <tr id="record-<?php echo $record['id']; ?>"> <!-- Added unique ID to row -->
                        <td><?php echo $counter++; ?></td> <!-- Display counter and increment -->
                        <td id="title-<?php echo $record['id']; ?>" data-course-id="<?php echo $record['course_id']; ?>">
                            <?php echo $record['course_name']; ?>
                        </td>
                        <td id="venue-<?php echo $record['id']; ?>"><?php echo $record['venue']; ?></td> <!-- venue -->
                        <td id="start_time-<?php echo $record['id']; ?>"><?php echo $record['start_time']; ?></td> <!-- start_time -->
                        <td id="end_time-<?php echo $record['id']; ?>"><?php echo $record['end_time']; ?></td> <!-- end_time -->
                        <td id="message-<?php echo $record['id']; ?>"><?php echo $record['class_date']; ?></td> <!-- class_date -->
                        <td>
                            <button onclick="editClassroom(<?php echo $record['id']; ?>); document.getElementById('editModal').scrollIntoView({ behavior: 'smooth' });">Edit</button>
                            <button onclick="deleteClassroom(<?php echo $record['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
        </div>
        <div id="paginationClassroom" class="AdminTableBolowBtn"></div>


        <!-- Edit Classroom Modal -->
        <div id="editModal" style="display: none;" class="AdminForm">
            <h2>Edit Classroom Record</h2>
            <form id="editForm" onsubmit="event.preventDefault(); updateClassroom();"> <!-- Prevent default form submission -->
                <input type="hidden" id="editClassroomId" name="classroom_id">

                <!-- Edit Class Date -->
                <label for="editClassDate">Select Date:</label>
                <input type="date" id="editClassDate" name="class_date" required>

                <!-- Edit Course -->
                <label for="editCourses">Select Course:</label>
                <select id="editCourses" name="course_ids" required>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                    <?php endforeach; ?>
                </select>


                <!-- Edit Venue -->
                <label for="editVenue">Enter Venue:</label>
                <input type="text" id="editVenue" name="venue" required>

                <!-- Edit Start Time -->
                <label for="editStartTime">Start Time:</label>
                <input type="time" id="editStartTime" name="start_time" required>

                <!-- Edit End Time -->
                <label for="editEndTime">End Time:</label>
                <input type="time" id="editEndTime" name="end_time" required>

                <!-- Buttons -->
                <button type="submit" class="AdminFormBtn" >Update Classroom Record</button>
                <button type="button" onclick="closeEditModal()" class="AdminFormBtn" >Cancel</button>
            </form>
        </div>


        <?php
    include '../common/footer.php';
    ?>
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
       
        // Create Classroom via AJAX
        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var class_date = document.getElementById('class_date').value;
            var course_id = document.getElementById('courses').value;
            var venue = document.getElementById('venue').value;
            var start_time = document.getElementById('start_time').value;
            var end_time = document.getElementById('end_time').value;

            var data = new FormData();
            data.append('action', 'create');
            data.append('class_date', class_date);
            data.append('course_ids', course_id); // single course_id
            data.append('venue', venue);
            data.append('start_time', start_time); // Add start time
            data.append('end_time', end_time); // Add end time

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'classroom_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    location.reload(); // Refresh the page to show updated data
                }
            };
            xhr.send(data);
        });

        // Edit Classroom function
        function editClassroom(id) {
            // Accessing the elements by id using the format 'title-' + id
            var course_id = document.getElementById('title-' + id).getAttribute('data-course-id'); // 
            var class_date = document.getElementById('message-' + id).innerText;
            var venue = document.getElementById('venue-' + id).innerText;
            var start_time = document.getElementById('start_time-' + id).innerText;
            var end_time = document.getElementById('end_time-' + id).innerText;

            // Set the values in the form fields
            document.getElementById('editClassroomId').value = id;
            document.getElementById('editClassDate').value = class_date;
            document.getElementById('editCourses').value = course_id;
            document.getElementById('editVenue').value = venue;
            document.getElementById('editStartTime').value = start_time;
            document.getElementById('editEndTime').value = end_time;

            // Display the modal
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Update Classroom via AJAX
        function updateClassroom() {
            var id = document.getElementById('editClassroomId').value;
            var course_id = document.getElementById('editCourses').value;
            var class_date = document.getElementById('editClassDate').value;
            var venue = document.getElementById('editVenue').value;
            var start_time = document.getElementById('editStartTime').value;
            var end_time = document.getElementById('editEndTime').value;

            var data = new FormData();
            data.append('action', 'update');
            data.append('classroom_id', id);
            data.append('new_course_id', course_id);
            data.append('class_date', class_date);
            data.append('venue', venue);
            data.append('start_time', start_time); // Add start time to update request
            data.append('end_time', end_time); // Add end time to update request

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'classroom_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    document.getElementById('editModal').style.display = 'none';
                    location.reload(); // Refresh the page to show updated data
                }
            };
            xhr.send(data);
        }


        // Delete Classroom via AJAX
        function deleteClassroom(id) {
            var data = new FormData();
            data.append('action', 'delete');
            data.append('id', id); // Ensure the id is being passed correctly

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'classroom_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    document.getElementById('record-' + id).remove(); // Remove row from table
                }
            };
            xhr.send(data);
        }
    </script>


</body>

</html>