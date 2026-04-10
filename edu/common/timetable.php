<?php
// Include necessary files
require_once '../config/db_config.php';
require_once '../lib/authlib.php';  // Ensure this is included once at the top


// Check if the user is logged in 
if (!isTeacher() && !isStudent()) {
    header("Location: ../common/login.php");
    exit();
}

// Include sidebar based on user role
if (isLoggedIn()) {
    // Check the role and include the appropriate sidebar
    include isTeacher() ? '../common/admin_sidebar.php' : '../common/sidebar.php';
} else {
    // Default to student sidebar if not logged in
    include '../common/sidebar.php';
}


// Get today's date in YYYY-MM-DD format
$today_date = date('Y-m-d');

// Modify SQL query to fetch records where class_date is today's date
$query = "SELECT c.id, c.course_id, c.venue, c.start_time, c.end_time, c.class_date, co.course_name 
          FROM classroom c 
          JOIN course co ON c.course_id = co.id 
          WHERE c.class_date = ?"; // Use prepared statement for better security

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today_date); // Bind the parameter for today's date
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Store the results in an associative array
    $classrooms = [];
    while ($row = $result->fetch_assoc()) {
        $classrooms[] = $row;
    }
} else {
    $classrooms = []; // No records found
}
?>



<body>
    <!-- Main Content -->


    <?php
    if (isTeacher()) {
        echo '<div class="AdminContents" id="AdminContents">';
        echo '<div class="ContentsCon dashboard-container">';
    } elseif (isStudent()) {
        echo '<div class="StudentContents" id="StudentContents">';
        echo '<div class="ContentsCon">';
    }
    ?>



    <!-- Display Today's Date -->
    <h3>Classes for Today: <br>
        <span class="date-text">(<?php echo date('F j, Y'); ?>)</span>
    </h3>


    <!-- Classroom Table -->
    <table class="classroom-table">
        <thead>
            <tr>
                <th>Class Time</th>
                <th>Venue</th>
                <th>Subject</th>
            </tr>
        </thead>
        <tbody class="classroom-body">
            <?php
            // Check if there are classroom records for today
            if (count($classrooms) > 0) {
                foreach ($classrooms as $classroom) {
                    $start_time = date('h:i A', strtotime($classroom['start_time'])); // Format time to h:i A (e.g. 08:30 AM)
                    $end_time = date('h:i A', strtotime($classroom['end_time']));     // Format time to h:i A (e.g. 09:30 AM)
                    $class_time = $start_time . ' - ' . $end_time; // Concatenate start time and end time
                    $venue = htmlspecialchars($classroom['venue']); // Sanitize venue to prevent XSS
                    $subject = htmlspecialchars($classroom['course_name']); // Sanitize course name to prevent XSS
            
                    echo "<tr>";
                    echo "<td>{$class_time}</td>";
                    echo "<td>{$venue}</td>";
                    echo "<td>{$subject}</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No classroom records for today.</td></tr>";
            }
            ?>
        </tbody>


        </div>
        </div>
        <!-- End Main Content -->

        <script>
            const phoneBurger = document.getElementById('PhoneBurger');
            const StudentLeftHeader = document.getElementById('StudentLeftHeader');
            const StudentContents = document.getElementById('StudentContents');
            const StudentTopHeader = document.getElementById('StudentTopHeader');

            phoneBurger.addEventListener('click', () => {
                StudentLeftHeader.classList.toggle('hidden');
                StudentContents.classList.toggle('full-width');

                // Toggle between ☰ and X when sidebar visibility changes
                if (StudentLeftHeader.classList.contains('hidden')) {
                    phoneBurger.textContent = 'X'; // Show ☰ when sidebar is hidden
                } else {
                    phoneBurger.textContent = '☰'; // Show X when sidebar is visible
                }
            });

            let lastScrollTop = 0;
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

                if (currentScroll > lastScrollTop) {
                    // Scrolling down
                    StudentTopHeader.classList.remove('show');
                    StudentTopHeader.classList.add('hide');
                } else {
                    // Scrolling up
                    StudentTopHeader.classList.remove('hide');
                    StudentTopHeader.classList.add('show');
                }

                lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // For Mobile or negative scrolling
            });
        </script>
</body>