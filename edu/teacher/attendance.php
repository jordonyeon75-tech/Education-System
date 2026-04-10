<?php
// Include required files
require_once '../config/db_config.php';
require_once '../lib/authlib.php';

// Ensure the user is a teacher
if (!isTeacher()) {
    header("Location: ../common/login.php");
    exit();
}

// Get the logged-in teacher's ID
$teacher_id = $_SESSION['user_id'];

// Get today's date in YYYY-MM-DD format
$today = date('Y-m-d');

// Handle form submission to update attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's for updating attendance or adding a new date
    if (isset($_POST['update_attendance'])) {
        $course_id = intval($_POST['course_id']);
        $updates = $_POST['attendance'];

        foreach ($updates as $student_id => $dates) {
            foreach ($dates as $date => $status) {
                // Check if attendance record already exists
                $check_sql = "SELECT id FROM attendance WHERE course_id = ? AND stu_id = ? AND date = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("iis", $course_id, $student_id, $date);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Update existing record
                    $update_sql = "UPDATE attendance SET present = ? WHERE course_id = ? AND stu_id = ? AND date = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("siis", $status, $course_id, $student_id, $date);
                    $update_stmt->execute();
                } else {
                    // Insert new attendance record
                    $insert_sql = "INSERT INTO attendance (course_id, stu_id, date, present) VALUES (?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iiss", $course_id, $student_id, $date, $status);
                    $insert_stmt->execute();
                }
            }
        }
        echo "<p>Attendance updated successfully!</p>";
    }

    // Handle new date and attendance submission
    if (isset($_POST['new_attendance'])) {
        $course_id = intval($_POST['course_id']);
        $attendance_date = $_POST['attendance_date']; // Date picked from calendar
        $students = $_POST['students'];

        // Insert attendance for each student for the selected date
        foreach ($students as $student_id => $status) {
            $insert_sql = "INSERT INTO attendance (course_id, stu_id, date, present) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiss", $course_id, $student_id, $attendance_date, $status);
            $insert_stmt->execute();
        }
        echo "<p>Attendance for the selected date has been recorded.</p>";
    }
}

// Fetch active courses assigned to the teacher
$sql = "SELECT id, course_name FROM course WHERE teacher_id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Fetch attendance data if course is selected
$attendance_data = [];
$date_headers = [];
if (isset($_GET['course_id']) && intval($_GET['course_id']) > 0) {
    $course_id = intval($_GET['course_id']);

    // Fetch distinct dates for the selected course
    $date_sql = "SELECT DISTINCT date FROM attendance WHERE course_id = ? ORDER BY date ASC";
    $stmt = $conn->prepare($date_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $dates_result = $stmt->get_result();
    $date_headers = $dates_result->fetch_all(MYSQLI_ASSOC);

    // Fetch attendance statuses for students
    $sql = "SELECT u.id AS student_id, u.first_name, u.last_name, a.date, a.present
            FROM enrollment e
            JOIN user u ON e.stu_id = u.id
            LEFT JOIN attendance a ON e.course_id = a.course_id AND e.stu_id = a.stu_id
            WHERE e.course_id = ? AND e.status = 'approved'
            ORDER BY u.last_name, a.date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group attendance data by student
    while ($row = $result->fetch_assoc()) {
        $attendance_data[$row['student_id']]['name'] = $row['first_name'] . ' ' . $row['last_name'];
        $attendance_data[$row['student_id']]['dates'][$row['date']] = $row['present'];
    }
}
?>

<?php include '../common/admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        #attendance-table {
            display: none;
        }
    </style>
</head>

<body>
<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">
        <!-- edit everything from here -->
    <h1>Attendance Records</h1>
    <div class="AdminForm">    
    <!-- Course Selection -->
    <form method="GET" action="attendance.php">
        <label for="course">Select a Course:</label>
        <select id="course" name="course_id" required onchange="this.form.submit()">
            <option value="">-- Select Course --</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= htmlspecialchars($course['id']) ?>" <?= isset($_GET['course_id']) && $_GET['course_id'] == $course['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($course['course_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
            </div>
    <!-- "All Attendance" Button (Shows the Update Attendance Table) -->
    <?php if (isset($_GET['course_id']) && !empty($attendance_data)): ?>
        <button id="all-attendance-btn" onclick="toggleAttendanceTable()" class="AdminFormBtn">All Attendance</button>
    <?php endif; ?>

    <!-- Record New Attendance Form (Always visible) -->
    <h2>Record New Attendance</h2>
    <div class="AdminForm">
    <form method="POST" action="attendance.php">
        <?php if (!empty($_GET['course_id'])): ?>
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($_GET['course_id']) ?>">
        <?php endif; ?>
        <label for="attendance_date">Select Date for New Attendance:</label>
        <input type="date" name="attendance_date" id="attendance_date" required max="<?= $today ?>">

        <h3>Student Attendance</h3>
        <table class="datatable">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <thead>
            <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                <td colspan="100"></td>

            </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_data as $student_id => $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($data['name']) ?></td>
                        <td>
                            <select name="students[<?= $student_id ?>]" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" name="new_attendance" class="AdminFormBtn">Submit Attendance</button>
    </form>
    </div>               

    <!-- Attendance Update Table (Hidden by default) -->
    <div id="attendance-table">
        <h2>Update Attendance</h2>
        <form method="POST" action="attendance.php">
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($_GET['course_id']) ?>">

            <table class="datatable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <?php foreach ($date_headers as $date): ?>
                            <?php if (!empty($date['date'])): // Skip empty dates 
                            ?>
                                <th><?= htmlspecialchars($date['date']) ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <thead>
            <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                <td colspan="100"></td>

            </tr>
            </thead>
                <tbody>
                    <?php foreach ($attendance_data as $student_id => $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($data['name']) ?></td>
                            <?php foreach ($date_headers as $date): ?>
                                <?php if (!empty($date['date'])): ?>
                                    <td>
                                        <select name="attendance[<?= $student_id ?>][<?= $date['date'] ?>]">
                                            <?php
                                            // Get the current status for the specific student and date, default to 'Not Marked' if not found
                                            $current_status = strtolower(trim($data['dates'][$date['date']] ?? 'Not Marked'));  // Convert to lowercase for case-insensitive comparison

                                            // Define the available options with proper casing
                                            $options = ['Present', 'Absent', 'Late', 'Not Marked'];

                                            // Iterate over the options and render the dropdown
                                            foreach ($options as $option):
                                                // Convert each option to lowercase for case-insensitive comparison
                                                $option_lower = strtolower($option);
                                            ?>
                                                <option value="<?= htmlspecialchars($option) ?>" <?= $current_status == $option_lower ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($option) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" name="update_attendance" style="margin-top: 10px;">Update Attendance</button>
        </form>
    </div>
    </div>
</div>
<!--end  Main Content -->
    <script>
        function toggleAttendanceTable() {
            const attendanceTable = document.getElementById('attendance-table');
            if (attendanceTable.style.display === 'none') {
                attendanceTable.style.display = 'block';
            } else {
                attendanceTable.style.display = 'none';
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
            const courseSelect = document.getElementById("course");
            const attendanceDateInput = document.getElementById("attendance-date");
            const studentAttendanceSection = document.getElementById("student-attendance-section");
            const studentList = document.getElementById("student-list");
            const selectedCourseId = document.getElementById("selected-course-id");
            const selectedAttendanceDate = document.getElementById("selected-attendance-date");

            function fetchStudents(courseId, date) {
                fetch(`attendance.php?action=fetch_students&course_id=${courseId}&date=${date}`)
                    .then(response => response.json())
                    .then(students => {
                        let studentRows = '';
                        students.forEach(student => {
                            const status = student.status || "";
                            const remark = student.remark || "";

                            studentRows += `
                        <tr>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.email}</td>
                            <td>
                                <select name="attendance[${student.id}]" required>
                                    <option value="present" ${status === 'present' ? 'selected' : ''}>Present</option>
                                    <option value="absent" ${status === 'absent' ? 'selected' : ''}>Absent</option>
                                    <option value="late" ${status === 'late' ? 'selected' : ''}>Late</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="remark[${student.id}]" value="${remark}" placeholder="Optional Remark">
                            </td>
                        </tr>
                    `;
                        });

                        studentList.innerHTML = studentRows;
                        studentAttendanceSection.style.display = "block";
                    })
                    .catch(error => {
                        console.error("Error fetching students:", error);
                        alert("Failed to fetch student list. Please try again.");
                    });
            }

            courseSelect.addEventListener("change", function() {
                const courseId = courseSelect.value;
                const date = attendanceDateInput.value;

                if (courseId && date) {
                    selectedCourseId.value = courseId;
                    selectedAttendanceDate.value = date;
                    fetchStudents(courseId, date);
                } else {
                    studentAttendanceSection.style.display = "none";
                    studentList.innerHTML = "";
                }
            });

            attendanceDateInput.addEventListener("change", function() {
                const courseId = courseSelect.value;
                const date = attendanceDateInput.value;

                if (courseId && date) {
                    selectedCourseId.value = courseId;
                    selectedAttendanceDate.value = date;
                    fetchStudents(courseId, date);
                } else {
                    studentAttendanceSection.style.display = "none";
                    studentList.innerHTML = "";
                }
            });
        });

        function fetchStudents(courseId, date) {
            document.getElementById('loading').style.display = 'block'; // Show loading
            fetch(`attendance.php?action=fetch_students&course_id=${courseId}&date=${date}`)
                .then(response => response.json())
                .then(students => {
                    let studentRows = '';
                    students.forEach(student => {
                        const status = student.status || "";
                        const remark = student.remark || "";

                        studentRows += `
                    <tr>
                        <td>${student.first_name} ${student.last_name}</td>
                        <td>${student.email}</td>
                        <td>
                            <select name="attendance[${student.id}]" required>
                                <option value="present" ${status === 'present' ? 'selected' : ''}>Present</option>
                                <option value="absent" ${status === 'absent' ? 'selected' : ''}>Absent</option>
                                <option value="late" ${status === 'late' ? 'selected' : ''}>Late</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="remark[${student.id}]" value="${remark}" placeholder="Optional Remark">
                        </td>
                    </tr>
                `;
                    });

                    studentList.innerHTML = studentRows;
                    studentAttendanceSection.style.display = "block";
                    document.getElementById('loading').style.display = 'none'; // Hide loading
                })
                .catch(error => {
                    console.error("Error fetching students:", error);
                    alert("Failed to fetch student list. Please try again.");
                    document.getElementById('loading').style.display = 'none'; // Hide loading
                });
        }
    </script>

</body>

</html>