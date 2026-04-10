<?php
// Start session
session_start();

// Destroy all session data
session_unset();  // Remove all session variables
session_destroy();  // Destroy the session

// Redirect the user to the login page (or home page)
header('Location: ../common/login.php');  // Change 'login.php' to your actual login page
exit();
?>
