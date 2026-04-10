<?php
// Include database configuration and authentication logic
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition Centre</title>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS (Bootstrap 4 Integration) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="../css/AdminHeader.css">
    <link rel="stylesheet" href="../css/admin_styles.css">
    <link rel = "stylesheet" href = "../css/styles.css">
    <link rel="stylesheet" href="../css/StudentHeader.css">

</head>
<body>
<!-- Top Header -->
<div class="AdminTopHeader">
    <div class="TopHeaderCon">
        <div class="AdminTopLeft">
            <div class="PhoneBurger" id="PhoneBurger">☰</div>
            <div class="Logo">
                <img src="../images/logo.png">
            </div>
        </div>

    </div>
</div>
<!--end Top Header -->
<!-- Left Sidebar -->
<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current script name
?>

<div class="AdminLeftHeader" id="AdminLeftHeader">
    <div class="LeftHeaderCon">
        <div class="conTopSidebar">
            <?php if (isLoggedIn() && isAdmin()): ?>

                <a href="../admin/user_management.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">Manage User</div>
                </a>
                <a href="../admin/notice_management.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'notice_management.php' ? 'active' : ''; ?>">Manage Notice</div>
                </a>
                <a href="../admin/classroom_management.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'classroom_management.php' ? 'active' : ''; ?>">Manage Classroom</div>
                </a>
                <a href="../admin/course_management.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'course_management.php' ? 'active' : ''; ?>">Manage Course</div>
                </a>
                <a href="../admin/enrollment_management.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'enrollment_management.php' ? 'active' : ''; ?>">Manage Enrollment</div>
                </a>

            <?php endif; ?>

            <?php if (isLoggedIn() && isTeacher()): ?>

                <a href="../common/dashboard.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</div>
                </a>
                <a href="../teacher/attendance.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">Attendance</div>
                </a>
                <a href="../teacher/course_material.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'course_material.php' ? 'active' : ''; ?>">Course Material</div>
                </a>
                <a href="../common/profile.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">My Profile</div>
                </a>
                <a href="../common/timetable.php">
                    <div class="LeftHeaderItems <?php echo $current_page == 'timetable.php' ? 'active' : ''; ?>">Timetable</div>
                </a>



            <?php endif; ?>
        </div>
        <div class="LogoutCon">
        <?php if (isLoggedIn()): ?>
            <a href="../common/logout.php">
                <div class="LeftHeaderItems btnlog <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>">Logout</div>
            </a>
        <?php else: ?>
            <a href="../common/login.php">
                <div class="LeftHeaderItems <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</div>
            </a>
        <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const phoneBurger = document.getElementById('PhoneBurger');
    const adminLeftHeader = document.getElementById('AdminLeftHeader');
    const adminContents = document.getElementById('AdminContents');

    phoneBurger.addEventListener('click', () => {
        adminLeftHeader.classList.toggle('hidden');
        adminContents.classList.toggle('full-width');

        // Toggle between ☰ and X when sidebar visibility changes
        if (adminLeftHeader.classList.contains('hidden')) {
            phoneBurger.textContent = 'X';  // Show ☰ when sidebar is hidden
        } else {
            phoneBurger.textContent = '☰';  // Show X when sidebar is visible
        }
    });

</script>
</body>
</html>
