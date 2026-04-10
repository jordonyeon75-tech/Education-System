<?php
// Include necessary files
require_once '../config/db_config.php';  // For DB connection
require_once '../lib/authlib.php';       // For authentication functions

// Check if the user is already logged in
if (isLoggedIn()) {
    // Redirect to the appropriate dashboard based on user role
    if (isAdmin()) {
        header('Location: ../admin/user_management.php');
    } elseif (isTeacher()) {
        header('Location: dashboard.php');
    } elseif (isStudent()) {
        header('Location: dashboard.php');
    }
    exit();
}

// Handle form submission for login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Prepare SQL query to fetch user data
        $sql = "SELECT id, username, password, role_id FROM user WHERE username = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Check if the account is locked
            $attempt_sql = "SELECT failed_attempts, lock_time FROM login_attempts WHERE user_id = ?";
            $attempt_stmt = $conn->prepare($attempt_sql);
            $attempt_stmt->bind_param("i", $user_id);
            $attempt_stmt->execute();
            $attempt_result = $attempt_stmt->get_result();

            if ($attempt_result->num_rows > 0) {
                // Fetch the login attempt data
                $login_attempt = $attempt_result->fetch_assoc();

                // If the account is locked and the lock time is less than 3 seconds ago
                if ($login_attempt['failed_attempts'] >= 3 && (strtotime($login_attempt['lock_time']) > strtotime("-10 minutes"))) {
                    $error_message = "Your account is locked. Please try again after 10 minutes.";
                } else {
                    // If 3 seconds have passed since the last lock time, reset failed attempts to 0
                    if (strtotime($login_attempt['lock_time']) < strtotime("-10 minutes")) {
                        // Reset failed attempts and allow 3 new tries
                        $reset_attempt_sql = "UPDATE login_attempts SET failed_attempts = 0, lock_time = NULL WHERE user_id = ?";
                        $reset_stmt = $conn->prepare($reset_attempt_sql);
                        $reset_stmt->bind_param("i", $user_id);
                        $reset_stmt->execute();
                    }

                    // Check if the provided password matches the hashed password in the database
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role_id'] = $user['role_id'];

                        // Redirect to the appropriate dashboard based on role
                        if (isAdmin()) {
                            header('Location: ../admin/user_management.php');
                        } elseif (isTeacher()) {
                            header('Location: ../common/dashboard.php');
                        } elseif (isStudent()) {
                            header('Location: ../common/dashboard.php');
                        }
                        exit();
                    } else {
                        // Increment failed attempts and lock the account if needed
                        $new_failed_attempts = $login_attempt['failed_attempts'] + 1;

                        if ($new_failed_attempts >= 3) {
                            $lock_time = date("Y-m-d H:i:s");
                            $update_attempt_sql = "UPDATE login_attempts SET failed_attempts = ?, lock_time = ? WHERE user_id = ?";
                            $update_stmt = $conn->prepare($update_attempt_sql);
                            $update_stmt->bind_param("isi", $new_failed_attempts, $lock_time, $user_id);
                            $update_stmt->execute();
                            $error_message = "Your account is locked due to too many failed attempts.";
                        } else {
                            $update_attempt_sql = "UPDATE login_attempts SET failed_attempts = ? WHERE user_id = ?";
                            $update_stmt = $conn->prepare($update_attempt_sql);
                            $update_stmt->bind_param("ii", $new_failed_attempts, $user_id);
                            $update_stmt->execute();

                            // Calculate remaining attempts but ensure it doesn't show negative numbers
                            $remaining_attempts = max(0, 3 - $new_failed_attempts); // Ensure attempts don't go negative
                            $error_message = "Invalid username or password. You have $remaining_attempts attempt(s) left.";
                        }
                    }
                }
            } else {
                // If no login attempts record exists, create one
                $create_attempt_sql = "INSERT INTO login_attempts (user_id, failed_attempts, lock_time) VALUES (?, 0, NULL)";
                $create_stmt = $conn->prepare($create_attempt_sql);
                $create_stmt->bind_param("i", $user_id);
                $create_stmt->execute();

                // Proceed with password verification as usual
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role_id'] = $user['role_id'];

                    // Redirect to the appropriate dashboard based on role
                    if (isAdmin()) {
                        header('Location: ../admin/user_management.php');
                    } elseif (isTeacher()) {
                        header('Location: ../teacher/attendance.php');
                    } elseif (isStudent()) {
                        header('Location: ../student/enroll.php');
                    }
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            }
        } else {
            $error_message = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/LoginStyle.css">
    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

</head>
<body>


<?php
// Display error message if there is one
if (isset($error_message)) {
    echo "<p style='color:red;'>$error_message</p>";
}
?>
<div class="background">
    <div class="loginimg">
        <div class="loginimg-content">
            <p>Welcome back to your learning journey</p>
            <p>Log in to access your courses and resources</p>
        </div>
    </div>
    <div class="login-container">
        <h2>Login</h2>
        <form method="POST" action="login.php">
            <div class="input-group">
                <i class="fa fa-user"></i>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username">
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password">
                <i class="fa fa-eye show-password" id="togglePassword" onclick="togglePassword()"></i>
            </div>
            <div class="remember">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="register-link">
        </div>
    </div>
</div>


</body>
</html>

<!-- JavaScript Section -->
<script>
    // Optional: Add JavaScript to handle additional client-side validation
    document.querySelector('form').addEventListener('submit', function(event) {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        // Check if both fields are filled out
        if (!username || !password) {
            alert('Please enter both username and password.');
            event.preventDefault(); // Prevent form submission if validation fails
        }
    });
</script>
