<?php
// Database connection, authentication, etc.
include '../config/db_config.php'; 
include '../lib/authlib.php'; 

if (isLoggedIn()) {
    if (isTeacher()) {
        include '../common/admin_sidebar.php';  
    } else 
        include '../common/sidebar.php';  
} else {
    header('Location: ../common/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

function getUserProfile($user_id, $conn) {
    $sql = "SELECT 
                u.id, u.username, u.email, u.first_name, u.last_name, 
                u.address, u.phone_no, u.profile_picture, u.status, 
                u.last_login, u.created_at, ut.role_name
            FROM 
                user AS u
            JOIN 
                user_type AS ut ON u.role_id = ut.id
            WHERE 
                u.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        error_log("User profile not found for ID: $user_id");  // Log the error
        return null;
    }
}

function handleProfilePictureUpload($file) {
    $upload_dir = '../images/'; // Directory to store uploaded images
    $max_size = 5 * 1024 * 1024; // Maximum allowed file size (5 MB)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // Allowed image types

    // Check if the file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Error: ' . $file['error'];
    }

    // Check if the uploaded file is an image
    if (!in_array($file['type'], $allowed_types)) {
        return 'Error: Invalid file type. Only JPG, PNG, and GIF are allowed.';
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return 'Error: File is too large. Maximum file size is 5 MB.';
    }

    // Generate a unique file name based on the original file name
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('profile_', true) . '.' . $ext;

    // Move the uploaded file to the server directory
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
        return $file_name; // Return just the file name, so you can save it in the database
    } else {
        return 'Error: Failed to upload the image.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone_no = $_POST['phone_no'];

    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profile_picture = handleProfilePictureUpload($_FILES['profile_picture']);
        if (strpos($profile_picture, 'Error:') === 0) {
            $error_message = $profile_picture;
            echo $error_message;
            exit();
        }
    }

    // Update profile in the database
    $sql = "UPDATE user SET 
                first_name = ?, 
                last_name = ?, 
                email = ?, 
                address = ?, 
                phone_no = ?";

    if ($profile_picture) {
        $sql .= ", profile_picture = ?";
    }

    $sql .= " WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if ($profile_picture) {
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $address, $phone_no, $profile_picture, $user_id);
    } else {
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $address, $phone_no, $user_id);
    }

    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        // Fetch the updated profile data immediately after update
        $user_profile = getUserProfile($user_id, $conn);
    } else {
        $error_message = "Error updating profile: " . $stmt->error;
    }
} else {
    // Fetch the user profile if no form submission
    $user_profile = getUserProfile($user_id, $conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../css/admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Profile Style */
        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .profile-pic-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .profile-pic-container img {
            border-radius: 50%;
            border: 1px solid;
            width: 200px;
            height: 200px;
            object-fit: cover;
        }

        .ProfileEditBtn i {
            padding: 10px;
            background-color: #333;
            width: 100px;
            text-align: center;
            color: #fff;
            border-radius: 15px;
        }
    </style>
</head>

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

<h2>User Profile</h2>

<!-- Display success or error messages -->
<?php if ($success_message): ?>
    <p class="success"><?= htmlspecialchars($success_message); ?></p>
<?php elseif ($error_message): ?>
    <p class="error"><?= htmlspecialchars($error_message); ?></p>
<?php endif; ?>

<?php if ($user_profile): ?>
    <!-- Profile Picture -->
    <div class="profile-pic-container">
        <?php if (!empty($user_profile['profile_picture'])): ?>
            <img src="../images/<?= htmlspecialchars($user_profile['profile_picture']); ?>" alt="Profile Picture">
        <?php else: ?>
            <p>No profile picture uploaded.</p>
        <?php endif; ?>
    </div>

    <!-- Edit Icon -->
    <div class="ProfileEditBtn">
        <i class="fa fa-edit edit-icon" onclick="toggleEdit()" title="Edit Profile"></i>
    </div>

    <!-- Profile Form -->
    <div class="AdminForm">
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateFileType()">
            <p><strong>Username:</strong> <?= htmlspecialchars($user_profile['username']); ?></p>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user_profile['email']); ?>" disabled required><br>

            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($user_profile['first_name']); ?>" disabled required><br>

            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($user_profile['last_name']); ?>" disabled required><br>

            <label for="address">Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($user_profile['address']); ?>" disabled required><br>

            <label for="phone_no">Phone:</label>
            <input type="text" name="phone_no" value="<?= htmlspecialchars($user_profile['phone_no']); ?>" disabled required><br>

            <!-- Profile Picture Upload -->
            <label for="profile_picture">Profile Picture:</label>
            <input type="file" name="profile_picture" <?= $user_profile['profile_picture'] ? '' : 'required'; ?> style="width: 50px;" disabled>

            <!-- Submit Button -->
            <button type="submit" id="update-btn" disabled class="AdminFormBtn">Update Profile</button>
        </form>
    </div>
<?php else: ?>
    <p>User profile not found.</p>
<?php endif; ?>

<script>
    function toggleEdit() {
        const inputs = document.querySelectorAll('form input');
        const submitButton = document.getElementById('update-btn');
        const profilePictureInput = document.querySelector('input[name="profile_picture"]');

        // Toggle the 'disabled' attribute on all inputs and the submit button
        inputs.forEach(input => input.disabled = !input.disabled);
        submitButton.disabled = !submitButton.disabled;

        // Specifically enable the file input for profile picture
        profilePictureInput.disabled = false;
    }

    // File type validation before submitting the form
    function validateFileType() {
        const fileInput = document.querySelector('input[name="profile_picture"]');
        const file = fileInput.files[0];
        
        // Allowed image types
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Check if file is selected and is of a valid type
        if (file && !allowedTypes.includes(file.type)) {
            alert("Error: Invalid file type. Only JPG, PNG, and GIF are allowed.");
            return false; // Prevent form submission
        }

        return true; // Allow form submission
    }
</script>

</body>
</html>
