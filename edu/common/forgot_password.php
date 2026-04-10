<?php
require_once '../config/db_config.php';  // For DB connection
require_once '../lib/userlib.php'; 

ini_set('display_errors', 1); // Enable error reporting
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check password strength
    $passwordStrength = isStrongPassword($new_password);
    if ($passwordStrength !== true) {
        // Return error message directly as text
        echo "Error: Password is not strong enough. " . $passwordStrength;
        exit(); // Ensure no further processing
    }

    if ($email) {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            if ($new_password && $confirm_password) {
                if ($new_password === $confirm_password) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the password in the database
                    $update_stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
                    $update_stmt->bind_param("ss", $hashed_password, $email);

                    if ($update_stmt->execute()) {
                        echo "Password updated successfully!";
                    } else {
                        echo "Error: Failed to update the password. Please try again.";
                    }

                    $update_stmt->close();
                } else {
                    echo "Error: Passwords do not match. Please try again.";
                }
            }
        } else {
            echo "Error: Email not found in the database.";
        }

        $stmt->close();
    } else {
        echo "Error: Please enter a valid email address.";
    }

    exit(); // Ensure the script ends after sending the response
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <style>
       body {
    font-family: Arial, sans-serif;
    background-color: #9182BB;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100vh;
}


        h2 {
            text-align: center;
            color: #333;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 4);
            width: 100%;
            max-width: 400px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 0px 0px;
            border: 1px solid #ccc;
            border-radius: 15px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: #6c5ce7;
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #6c5ce7;
        }
        .ForgetBactBtn{
            margin-top:20px;
        }
        .ForgetBactBtn a{
            color:#fff;
            text-decoration:none;
        }
        .ForgetBactBtn a:hover{
            text-decoration:underline;
        }

        @media (max-width: 480px) {
            form {
                padding: 15px;
                max-width: 300px;
            }

            button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<form id="password-reset-form" method="POST" action="">
<h2>Forget Password</h2>
    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br><br>

    <label for="new_password">New Password:</label><br>
    <input type="password" id="new_password" name="new_password"><br><br>

    <label for="confirm_password">Confirm Password:</label><br>
    <input type="password" id="confirm_password" name="confirm_password"><br><br>

    <button type="submit">Reset Password</button>

</form>
<div class="ForgetBactBtn">
        <a href="login.php">Back To Login</a>
</div>
<script>
    // Handle the form submission
    document.getElementById('password-reset-form').onsubmit = function(event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(this); // Gather form data

        // Send the form data using fetch
        fetch('forgot_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())  // Get response as text (HTML)
        .then(data => {
            console.log('Response data:', data);  // Log the response data for debugging
            
            // Display the response message in an alert
            alert(data);

            // Check if the message indicates success and redirect to login page
            if (data.includes("Password updated successfully")) {
                window.location.href = 'login.php'; // Redirect to login page
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);  // Log the error if fetch fails
            alert('An error occurred. Please try again.');
        });
    };
</script>

</body>
</html>
