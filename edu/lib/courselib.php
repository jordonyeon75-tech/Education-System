<?php
// Function to generate a unique course code
function generateCourseCode($conn, $course_name) {
    // Extract the first 3 letters from the course name (you can modify this as per your logic)
    $course_prefix = strtoupper(substr($course_name, 0, 3)); // First 3 characters

    // Fetch the highest course number with the same prefix
    $query = "SELECT MAX(CAST(SUBSTRING(course_code, 4) AS UNSIGNED)) AS max_code FROM course WHERE course_code LIKE '$course_prefix%'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    
    // Determine the next course number
    $next_code_number = isset($row['max_code']) ? $row['max_code'] + 1 : 1;
    
    // Format the course number to always have 2 digits
    $next_code_number = str_pad($next_code_number, 2, "0", STR_PAD_LEFT);
    
    // Combine the prefix and the next number to form the course code
    $course_code = $course_prefix . $next_code_number;

    return $course_code;
}
?>