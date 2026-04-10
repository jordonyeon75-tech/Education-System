<?php
function isStrongPassword($password) {
    // Check if the password length is at least 8 characters
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    
    // Check if the password contains at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    
    // Check if the password contains at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    
    // Check if the password contains at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    
    // Check if the password contains at least one special character
    if (!preg_match('/[\W_]/', $password)) {
        return 'Password must contain at least one special character (e.g., !, @, #, $, %, ^, &, *)';
    }
    
    // If all checks pass
    return true;
}


// Function to check if the username already exists
function isUsernameExists($conn, $username) {
    $sqlCheckUsername = "SELECT id FROM user WHERE username = ?";
    $stmtCheck = $conn->prepare($sqlCheckUsername);
    $stmtCheck->bind_param("s", $username);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    return $stmtCheck->num_rows > 0;
}

// Function to check if the email already exists
function isEmailExists($conn, $email) {
    $sqlCheckEmail = "SELECT id FROM user WHERE email = ?";
    $stmtCheckEmail = $conn->prepare($sqlCheckEmail);
    $stmtCheckEmail->bind_param("s", $email);
    $stmtCheckEmail->execute();
    $stmtCheckEmail->store_result();

    return $stmtCheckEmail->num_rows > 0;
}

?>