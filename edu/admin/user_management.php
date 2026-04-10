<?php
// Include the necessary files
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
require_once '../lib/compresslib.php';
require_once '../lib/userlib.php';

// Check if the user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Redirect to the login page or homepage if the user is not logged in or not an admin
    header('Location: ../common/login.php');
    exit();
}



// Fetch the available courses from the course table (this happens regardless of POST/GET)
$sql = "SELECT id, role_name FROM user_type";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching roles: " . $conn->error);
}

$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}


// Fetch existing users
$sql = "SELECT u.*, ut.role_name
        FROM user u
        JOIN user_type ut ON u.role_id = ut.id";
$result = $conn->query($sql);



// Handle AJAX requests (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Validate and sanitize input
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : null;
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $address = $_POST['address'];
        $phone_number = $_POST['phone_number'];
        // $status = $_POST['status'];

        // Check if role_id exists
        if ($role_id) {
            $roleCheckQuery = "SELECT * FROM user_type WHERE id = ?";
            $stmt = $conn->prepare($roleCheckQuery);
            $stmt->bind_param("i", $role_id);
            $stmt->execute();
            $roleResult = $stmt->get_result();

            if ($roleResult->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid role_id provided']);
                exit();
            }
        }

        // Action: Create
        if ($_POST['action'] == 'create') {
            // Check if the username already exists
            if (isUsernameExists($conn, $username)) {
                // Username already exists, send error message
                echo json_encode(['status' => 'error', 'message' => 'Username already taken']);
                exit(); // Exit if creation fails
            }

            // Check if the email already exists
            if (isEmailExists($conn, $email)) {
                // Email already exists, send error message
                echo json_encode(['status' => 'error', 'message' => 'Email already in use']);
                exit(); // Exit if creation fails
            }

            // Check if the password is strong enough **before hashing**
            $passwordStrength = isStrongPassword($password);
            if ($passwordStrength !== true) {
                // If the password is not strong enough, send an error
                echo json_encode(['status' => 'error', 'message' => $passwordStrength]);
                exit(); // Exit if password is not strong enough
            }

            // Hash the password after checking strength
            $password = password_hash($password, PASSWORD_DEFAULT);

            // Proceed with user creation
            $status = 'active';
            $image = handleImageUpload($_FILES['image']);

            // Prepare the SQL query to insert the notice only if image is uploaded or no image is provided
            if ($image !== null || !isset($_FILES['image'])) {
                // If image was uploaded or no image is provided, proceed with the insert
                $stmt = null;
                // Prepare the insert query
                if ($image) {
                    // If an image is uploaded, include the image field and set status as 'active'
                    $stmt = $conn->prepare("INSERT INTO user (username, password, email, role_id, first_name, last_name, address, phone_no, profile_picture, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssss", $username, $password, $email, $role_id, $first_name, $last_name, $address, $phone_number, $image, $status);
                } else {
                    // If no image is uploaded, exclude the image field and set status as 'active'
                    $stmt = $conn->prepare("INSERT INTO user (username, password, email, role_id, first_name, last_name, address, phone_no, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssss", $username, $password, $email, $role_id, $first_name, $last_name, $address, $phone_number, $status);
                }
                // Execute the query
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'User created successfully']);
                    exit(); // Exit after successful creation
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create user']);
                    exit(); // Exit if creation fails
                }
            } else {
                // If the image is invalid, do not proceed with the insert and return an error
                echo json_encode(['status' => 'error', 'message' => 'Invalid image. User not created']);
                exit(); // Exit if image is invalid
            }
        }



        // Action: Update
        if ($_POST['action'] == 'update') {
            $id = $_POST['id']; // Get the user ID from the form
            $status = $_POST['status'];

            
            $image = null; // Initialize image variable to null
          

            // Check if the image was uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Handle image upload (returns the file name or an error message)
                $image = handleImageUpload($_FILES['image']);

                // If the image upload fails, return an error message
                if ($image === null) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid image. User not updated.']);
                    exit(); // Exit if image is invalid
                }
            }

           

            // Prepare the update query
            if ($image) {
                // If image is uploaded, include it in the update query
                $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, role_id = ?, first_name = ?, last_name = ?, address = ?, phone_no = ?, status = ?, profile_picture = ? WHERE id = ?");
                $stmt->bind_param("ssssssssss", $username, $email, $role_id, $first_name, $last_name, $address, $phone_number, $status, $image, $id);
            } else {
                // If no image is uploaded, just update the text fields
                $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, role_id = ?, first_name = ?, last_name = ?, address = ?, phone_no = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssssss", $username, $email, $role_id, $first_name, $last_name, $address, $phone_number, $status, $id);
            }

            // Execute the update query
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
                exit(); // Exit after successful update
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
                exit(); // Exit if update fails
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
        <h1>User Management</h1>
        <button class="AdminFormBtn" onclick="toggleCreateForm()">
            Create Notice
        </button>
        <!-- Create User Form -->

        <div class="AdminForm" id="createFrom" style="display: none;">
            <h3>Create New User</h3>
            <form id="createForm" method="POST" action="create_user.php" enctype="multipart/form-data">
                <input type="text" id="username" name="username" placeholder="Username" required>
                <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <input type="text" id="email" name="email" placeholder="Email" required>
                <input type="text" id="address" name="address" placeholder="Address" required>
                <input type="tel" id="phone_no" name="phone_no" placeholder="Phone Number" required>

                <select id="roles" name="role_ids" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Image upload field (optional) -->
                <input type="file" id="image" accept="image/*">

                <button type="submit" class="AdminFormBtn">Create User</button>
            </form>
        </div>


        <h3>Existing Users</h3>
        <input type="text" id="searchInputUser" class="AdminSearch" placeholder="Search..." />
        <div class="AdminTablePhone">
        <table id="userTable" class="datatable table table-striped" border="1">
            <thead>
                <tr>
                <th>No</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone Number</th>
                    <th>Profile Picture</th>
                    <th>Email</th>
                    <th>Role</th>
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
                 while ($user = $result->fetch_assoc()):
                 ?>
                    <tr id="user-<?php echo $user['id']; ?>">
                    <td><?php echo $counter; ?></td>
                        <td id="username-<?php echo $user['id']; ?>"><?php echo $user['username']; ?></td>
                        <td id="name-<?php echo $user['id']; ?>"><?php echo $user['first_name'] . " " . $user['last_name']; ?></td>
                        <td id="address-<?php echo $user['id']; ?>"><?php echo $user['address']; ?></td>
                        <td id="phone_number-<?php echo $user['id']; ?>"><?php echo $user['phone_no']; ?></td>
                        <td id="user-image-<?php echo $user['id']; ?>" data-column="image">
                            <?php
                            if (!empty($user['profile_picture'])) {
                                $imagePath = '../images/' . $user['profile_picture'];
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Profile Picture" width="100" height="100">';
                            } else {
                                echo 'No image available';
                            }
                            ?>
                        </td>
                        <td id="email-<?php echo $user['id']; ?>"><?php echo $user['email']; ?></td>
                        <td id="role-<?php echo $user['id']; ?>" data-role-id="<?php echo $user['role_id']; ?>">
                            <?php echo $user['role_name']; ?>
                        </td>
                        <td id="status-<?php echo $user['id']; ?>"><?php echo $user['status']; ?></td>
                        <td>
                            <button onclick="editUser(<?php echo $user['id']; ?>); document.getElementById('editUserForm').scrollIntoView({ behavior: 'smooth' });">Edit</button>
                        </td>
                    </tr>
                    <?php
                    $counter++;
                    ?>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
        <div id="paginationUser" class="AdminTableBolowBtn"></div>
        <!-- Edit User Form -->
        <div id="editUserForm" style="display:none;" class="AdminForm">
            <h3>Edit User</h3>
            <input type="hidden" id="editUserId">
            <input type="text" id="editUsername" placeholder="Username" required readonly>
            <input type="text" id="editFirstName" placeholder="First Name" required>
            <input type="text" id="editLastName" placeholder="Last Name" required>
            <input type="text" id="editEmail" placeholder="Email" required readonly>
            <input type="text" id="editAddress" placeholder="Address" required>
            <input type="text" id="editPhoneNumber" placeholder="Phone Number" required>

            <!-- Edit Role -->
            <label for="editRoles">Select Role:</label>
            <select id="editRoles" name="role_ids" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="file" id="editProfilePicture" accept="image/*">
            <!-- Display current image (optional) -->
            <div id="currentImageContainer" style="display:none;">
                <label>Current Image:</label>
                <img id="currentImage" src="" alt="Current User Profile" width="100">
            </div>
            <label for="editStatus">Status:</label>
            <select id="editStatus" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button onclick="updateUser()" class="AdminFormBtn">Update User</button>
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

        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var username = document.getElementById('username').value;
            var first_name = document.getElementById('first_name').value;
            var last_name = document.getElementById('last_name').value;
            var address = document.getElementById('address').value;
            var phone_number = document.getElementById('phone_no').value;
            var password = document.getElementById('password').value;
            var email = document.getElementById('email').value;
            var role_id = document.getElementById('roles').value;
            var image = document.getElementById('image').files[0];

            // Client-side validation for name fields (letters and spaces only)
            var namePattern = /^[A-Za-z\s]+$/;
            if (!namePattern.test(first_name)) {
                alert("First name should only contain letters and spaces.");
                return;
            }

            if (!namePattern.test(last_name)) {
                alert("Last name should only contain letters and spaces.");
                return;
            }

            var data = new FormData();
            data.append('action', 'create');
            data.append('username', username);
            data.append('first_name', first_name);
            data.append('last_name', last_name);
            data.append('address', address);
            data.append('phone_number', phone_number);
            data.append('password', password);
            data.append('email', email);
            data.append('role_id', role_id);
            if (image) {
                data.append('image', image);
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'user_management.php', true);
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            };
            xhr.send(data);
        });

        // Edit User (Populate edit form)
        function editUser(id, imagePath) {
            // Get user data
            var username = document.getElementById('username-' + id).innerText;
            var firstName = document.getElementById('name-' + id).innerText.split(" ")[0]; // Assuming 'name' field has "First Last"
            var lastName = document.getElementById('name-' + id).innerText.split(" ")[1];
            var address = document.getElementById('address-' + id).innerText;
            var phone = document.getElementById('phone_number-' + id).innerText;
            var email = document.getElementById('email-' + id).innerText;
            var role_id = document.getElementById('role-' + id).getAttribute('data-role-id'); // 
            var status = document.getElementById('status-' + id).innerText;

            // Set form values
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editFirstName').value = firstName;
            document.getElementById('editLastName').value = lastName;
            document.getElementById('editAddress').value = address;
            document.getElementById('editPhoneNumber').value = phone;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRoles').value = role_id;
            document.getElementById('editStatus').value = status;


            // Display the current image in the form if available
            var imageCell = document.getElementById('user-image-' + id).querySelector('img');
            var currentImageContainer = document.getElementById('currentImageContainer');
            var currentImage = document.getElementById('currentImage');

            if (imageCell) {
                currentImageContainer.style.display = 'block';
                currentImage.src = imageCell.src; // Set image path
            } else {
                currentImageContainer.style.display = 'none'; // Hide image if none
            }


            // Show the form
            document.getElementById('editUserForm').style.display = 'block';
        }


        // Update User via AJAX
        function updateUser() {
            var id = document.getElementById('editUserId').value;
            var username = document.getElementById('editUsername').value;
            var firstName = document.getElementById('editFirstName').value;
            var lastName = document.getElementById('editLastName').value;
            var address = document.getElementById('editAddress').value;
            var phone = document.getElementById('editPhoneNumber').value;
            var email = document.getElementById('editEmail').value;
            var role_id = document.getElementById('editRoles').value;
            var status = document.getElementById('editStatus').value;
            var image = document.getElementById('editProfilePicture').files[0];


            var data = new FormData();
            data.append('action', 'update');
            data.append('id', id);
            data.append('username', username);
            data.append('first_name', firstName);
            data.append('last_name', lastName);
            data.append('address', address);
            data.append('phone_number', phone);
            data.append('email', email);
            data.append('role_id', role_id);
            data.append('status', status);

            // Append profile picture if selected
            if (image) {
                data.append('image', image);
            }

            // AJAX Request to update user
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'user_management.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    alert(response.message); // Show success/failure message
                    if (response.status === 'success') {
                        document.getElementById('editUserForm').style.display = 'none'; // Hide the form after update
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Failed to update user. Please try again.'); // Show failure message
                    }
                } else {
                    alert('There was an error with the request. Please try again later.');
                }
            };
            xhr.send(data); // Send the data
        }
    </script>

</body>

</html>