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

    <!-- Custom CSS for Top Header and Sidebar -->
    <link rel="stylesheet" href="../css/StudentHeader.css">
    <link rel="stylesheet" href="../css/styles.css" />
</head>
<body>

<!-- Top Header -->
<div class="StudentTopHeader" id="StudentTopHeader">
    <div class="TopHeaderCon">
        <div class="PhoneBurger" id="PhoneBurger">☰</div>
        <div class="Logo">
            <!-- <img src="../images/logo.png" alt="Logo"> -->
        </div>
        <div class="StudentTopRight">
            <!-- Optionally add elements like profile or notifications here -->
        </div>
    </div>
</div>
<!-- End Top Header -->

<!-- Left Sidebar -->
<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current script name
?>

<div class="StudentLeftHeader" id="StudentLeftHeader">
    <div class="LeftHeaderCon">
        <div class="conTopSidebar">
            <div class="Logo">
                <img src="../images/logo.png" alt="Logo">
            </div>

            <?php if (isLoggedIn() && isStudent()): ?>
                <a href="../common/dashboard.php" class="Studentbtnmain"> 
                    <div class="LeftHeaderItems <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <div class="HeaderIcon"></div>
                        <div class="HeaderText">Dashboard</div>
                    </div>
                </a>

                <a href="../common/timetable.php" class="Studentbtnmain"> 
                    <div class="LeftHeaderItems <?php echo $current_page == 'timetable.php' ? 'active' : ''; ?>">
                        <div class="HeaderIcon"></div>
                        <div class="HeaderText">Timetable</div>
                    </div>
                </a>

                <a href="../student/course.php" class="Studentbtnmain">
                    <div class="LeftHeaderItems <?php echo $current_page == 'course.php' ? 'active' : ''; ?>">
                        <div class="HeaderIcon"></div>
                        <div class="HeaderText">Courses</div>
                    </div>
                </a>

                <a href="../student/enroll.php" class="Studentbtnmain">
                    <div class="LeftHeaderItems <?php echo $current_page == 'enroll.php' ? 'active' : ''; ?>">
                        <div class="HeaderIcon"></div>
                        <div class="HeaderText">Enrollment</div>
                    </div>
                </a>

                <!-- Profile Link -->
                <a href="../common/profile.php" class="Studentbtnmain"> 
                    <div class="LeftHeaderItems <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <div class="HeaderIcon"></div>
                        <div class="HeaderText">Profile</div>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <div class="LogoutCon">
            <?php if (isLoggedIn()): ?>
                <a href="../common/logout.php">
                    <div class="LeftHeaderItems btn-log">Logout</div>
                </a>
            <?php else: ?>
                <a href="../common/login.php">
                    <div class="LeftHeaderItems btn-log">Login</div>
                </a>
            <?php endif; ?>
        </div>
    </div> <!-- Closing LeftHeaderCon -->
</div> <!-- Closing StudentLeftHeader -->
<!-- End Left Sidebar -->

<script>
    // Select DOM elements for ease of use
    const phoneBurger = document.getElementById('PhoneBurger');
    const studentLeftHeader = document.getElementById('StudentLeftHeader');
    const studentContents = document.getElementById('StudentContents');
    const studentTopHeader = document.getElementById('StudentTopHeader');

    // Toggle sidebar visibility
    phoneBurger.addEventListener('click', () => {
        studentLeftHeader.classList.toggle('hidden');
        studentContents.classList.toggle('full-width');
        
        // Change icon text between ☰ and X
        phoneBurger.textContent = studentLeftHeader.classList.contains('hidden') ? '☰' : 'X';
    });

    // Scroll logic for hiding the top header when scrolling down
    let lastScrollTop = 0;
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        if (currentScroll > lastScrollTop) {
            // Scroll down: hide the top header
            studentTopHeader.classList.replace('show', 'hide');
        } else {
            // Scroll up: show the top header
            studentTopHeader.classList.replace('hide', 'show');
        }

        // Update last scroll position for next scroll event
        lastScrollTop = Math.max(currentScroll, 0); // Prevent negative values
    });
</script>
</body>
</html>
