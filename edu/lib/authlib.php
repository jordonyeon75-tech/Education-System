<?php
// Start session at the beginning of the file
session_start();

function isLoggedIn()
{
    // Check if the user ID is set in the session
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    // Check if the user role_id is 1 (assuming 1 is for admin)
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function isTeacher()
{
    // Check if the user role is 'teacher'
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function isStudent()
{
    // Check if the user role is 'student'
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;
}
?>
