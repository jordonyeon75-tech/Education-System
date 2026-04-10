<?php
// Database configuration
define('DB_SERVER', 'localhost');   // Database host (usually localhost)
define('DB_USERNAME', 'root');      // Database username
define('DB_PASSWORD', '');          // Database password (if any)
define('DB_NAME', 'edu');           // Database name (edu database)

// Establish database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set the character set to UTF-8 for better compatibility
$conn->set_charset("utf8");

?>
