<?php
// Include database configuration and authentication logic
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
require_once '../lib/compresslib.php';

// Check if the user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Redirect to the login page or homepage if the user is not logged in or not an admin
    header('Location: ../common/login.php');
    exit();
}

// Fetch the notices from the database (this should happen regardless of POST/GET)
$sql = "SELECT * FROM notice_board";
$result = $conn->query($sql);

// Check if the query was successful
if (!$result) {
    die("Error: " . $conn->error);  // If there is an error in the query
}

// Handle Create, Update, Delete operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Ensure the title, message, and updated_by fields are set
        $title = isset($_POST['title']) ? $_POST['title'] : null;
        $message = isset($_POST['message']) ? $_POST['message'] : null;
        $image = isset($_POST['image']) ? $_POST['image'] : null;
        $updated_by = $_SESSION['user_id']; // Get the user ID from the session

        // Create Operation
        if ($_POST['action'] == 'create') {

            // Handle image upload (returns the exact file name or an error message)
            $image = handleImageUpload($_FILES['image']);  // Call the image upload function

            // Prepare the SQL query to insert the notice only if image is uploaded or no image is provided
            if ($image !== null || !isset($_FILES['image'])) {
                // If image was uploaded or no image is provided, proceed with the insert
                $stmt = null;
                if ($image) {
                    // If image was uploaded, include the image field in the insert query
                    $stmt = $conn->prepare("INSERT INTO notice_board (title, message, image, updated_by) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $title, $message, $image, $updated_by);
                } else {
                    // If no image is uploaded, just insert the text fields
                    $stmt = $conn->prepare("INSERT INTO notice_board (title, message, updated_by) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $title, $message, $updated_by);
                }

                // Execute the query
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Notice created successfully']);
                    exit(); // Exit after successful creation
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create notice']);
                    exit(); // Exit if creation fails
                }
            } else {
                // If the image is invalid, do not proceed with the insert and return an error
                echo json_encode(['status' => 'error', 'message' => 'Invalid image. Notice not created']);
                exit(); // Exit if image is invalid
            }
        }


        // Update Operation
        if ($_POST['action'] == 'update') {
            $id = $_POST['id']; // Get the notice ID from the form
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
                $stmt = $conn->prepare("UPDATE notice_board SET title = ?, message = ?, image = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->bind_param("sssii", $title, $message, $image, $updated_by, $id);
            } else {
                // If no image is uploaded, just update the text fields
                $stmt = $conn->prepare("UPDATE notice_board SET title = ?, message = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssii", $title, $message, $updated_by, $id);
            }

            // Execute the update query
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Notice updated successfully']);
                exit(); // Exit after successful update
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update notice']);
                exit(); // Exit if update fails
            }
        }


        // Delete Operation
        if ($_POST['action'] == 'delete') {
            $id = $_POST['id']; // Get the notice ID from the form

            // First, fetch the current image path/filename from the database
            $stmt = $conn->prepare("SELECT image FROM notice_board WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($image); // Assuming 'image' is the column holding the file name
            $stmt->fetch();

            // Check if the image exists and if it exists, delete it
            if ($image) {
                $uploadDir = __DIR__ . '/../images/'; // Path where images are stored
                $filePath = $uploadDir . $image;
            }

            // Now, delete the record from the database
            $stmt = $conn->prepare("DELETE FROM notice_board WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Notice deleted successfully']);
                exit(); // Exit after successful deletion
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete notice']);
                exit(); // Exit if deletion fails
            }
        }
    }
}
?>




<?php
include '../common/admin_sidebar.php';
?>


<body>
<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">
        <!-- edit everything from here -->
        <h1>Notice Board Management</h1>

        <!-- Create New Notice Form -->
        <button class="AdminFormBtn" onclick="toggleCreateForm()">
            Create Notice
        </button>

        <div class="AdminForm" id="createFrom" style="display: none;">
            <h3>Create New Notice</h3>
            <form id="createNoticeForm" enctype="multipart/form-data">
                <input type="text" id="title" placeholder="Title" required>
                <textarea id="message" placeholder="Message" required></textarea>
                <input type="file" id="image" accept="image/*">
                <button type="submit" class="AdminFormBtn">Create Notice</button>
            </form>
        </div>



        <h3>Existing Notices</h3>
        <input type="text" id="searchInputNotice" class="AdminSearch" placeholder="Search..." />
        <div class="AdminTablePhone">
            <table id="noticeTable"class="datatable table table-striped" border="1">
                <thead class="theadstick">
                    <tr>
                        <th>No</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Image</th>
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

            while ($notice = $result->fetch_assoc()): ?>
                <tr id="notice-<?php echo $notice['id']; ?>">
                    <!-- Display the counter instead of the ID -->
                    <td><?php echo $counter; ?></td>  <!-- This will display 1, 2, 3, ... -->

                    <!-- Display the actual ID in a hidden column, if needed -->

                    <td id="title-<?php echo $notice['id']; ?>"><?php echo $notice['title']; ?></td>
                    <td id="message-<?php echo $notice['id']; ?>"><?php echo $notice['message']; ?></td>
                    <td id="notice-image-<?php echo $notice['id']; ?>" data-column="image">
                                <?php
                                if (!empty($notice['image'])) {
                                    $imagePath = '../images/' . $notice['image'];
                                    echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Course Image" width="100" height="100">';
                                } else {
                                    echo 'No image available';
                                }
                                ?>
                            </td>

                    <td>
                        <button onclick="editNotice(<?php echo $notice['id']; ?>); document.getElementById('editNoticeForm').scrollIntoView({ behavior: 'smooth' });">Edit</button>
                        <button onclick="deleteNotice(<?php echo $notice['id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php
            $counter++; // Increment the counter for the next row
            endwhile; ?>
        </tbody>

            </table>
        </div>
        <div id="paginationNotice" class="AdminTableBolowBtn"></div>

        <!-- Edit Notice Form -->
        <div id="editNoticeForm" style="display:none;" class="AdminForm">
            <h3>Edit Notice</h3>
            <input type="hidden" id="editNoticeId">
            <input type="text" id="editTitle" placeholder="Title" required>
            <textarea id="editMessage" placeholder="Message" required></textarea>
            <input type="file" id="editImage" accept="image/*">

             <!-- Display current image (optional) -->
             <div id="currentImageContainer" style="display:none;">
                    <label>Current Image:</label>
                    <img id="currentImage" src="" alt="Current Notice Image" width="100">
                </div>

            <button onclick="updateNotice()" class="AdminFormBtn" > Update Notice</button>
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


        // Create Notice via AJAX
        document.getElementById('createNoticeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var title = document.getElementById('title').value;
            var message = document.getElementById('message').value;
            var image = document.getElementById('image').files[0];

            var data = new FormData();
            data.append('action', 'create');
            data.append('title', title);
            data.append('message', message);
            if (image) {
                data.append('image', image);
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'notice_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            };
            xhr.send(data);
        });

        // Edit Notice (Populate form with current notice data)
        function editNotice(id, imagePath) {
            var title = document.getElementById('title-' + id).innerText;
            var message = document.getElementById('message-' + id).innerText;

            // Set the values in the edit form
            document.getElementById('editNoticeId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editMessage').value = message;

            // Display the current image in the form if available
            var imageCell = document.getElementById('notice-image-' + id).querySelector('img');
            var currentImageContainer = document.getElementById('currentImageContainer');
            var currentImage = document.getElementById('currentImage');

            if (imageCell) {
                currentImageContainer.style.display = 'block';
                currentImage.src = imageCell.src; // Set image path
            } else {
                currentImageContainer.style.display = 'none'; // Hide image if none
            }


            document.getElementById('editNoticeForm').style.display = 'block'; // Show the form
        }


        // Update Notice via AJAX
        function updateNotice() {
            var id = document.getElementById('editNoticeId').value;
            var title = document.getElementById('editTitle').value;
            var message = document.getElementById('editMessage').value;
            var image = document.getElementById('editImage').files[0];

            var data = new FormData();
            data.append('action', 'update');
            data.append('id', id);
            data.append('title', title);
            data.append('message', message);
            if (image) {
                data.append('image', image);
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'notice_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            };
            xhr.send(data);
        }

        // Delete Notice via AJAX

        function deleteNotice(id) {
            var data = new FormData();
            data.append('action', 'delete');
            data.append('id', id); // Ensure the id is being passed correctly

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'notice_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    document.getElementById('notice-' + id).remove(); // Remove row from table
                }
            };
            xhr.send(data);
        }


    </script>


</body>

</html>