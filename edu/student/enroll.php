<?php
require_once '../config/db_config.php'; // DB connection
include '../lib/authlib.php'; // Include authlib

// Redirect if user is not logged in
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../common/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch available courses
$courses = [];
$course_stmt = $conn->prepare("SELECT id, course_name, course_fee FROM course");
$course_stmt->execute();
$course_result = $course_stmt->get_result();
while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row;
}
$course_stmt->close();

// Fetch enrolled courses to disable selection
$enrolled_course_ids = [];
$enrollment_stmt = $conn->prepare("SELECT course_id FROM enrollment WHERE stu_id = ?");
$enrollment_stmt->bind_param("i", $student_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();
while ($row = $enrollment_result->fetch_assoc()) {
    $enrolled_course_ids[] = $row['course_id'];
}
$enrollment_stmt->close();

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $selected_courses = $_POST['courses'] ?? [];
    $new_courses = array_diff($selected_courses, $enrolled_course_ids);

    if (!empty($new_courses)) {
        foreach ($new_courses as $course_id) {
            $stmt = $conn->prepare("INSERT INTO enrollment (stu_id, course_id, status) VALUES (?, ?, 'Pending')");
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = "Please select at least one new course.";
    }
}

// Fetch newly enrolled courses for payment (exclude those already in payment table)
$new_enrolled_courses = [];
$total_payment = 0;
$new_enrollment_stmt = $conn->prepare("
    SELECT c.course_name, c.course_fee, e.status, e.course_id 
    FROM enrollment e
    JOIN course c ON e.course_id = c.id
    WHERE e.stu_id = ? 
    AND e.status = 'Pending'
    AND NOT EXISTS (
        SELECT 1 
        FROM payment p 
        WHERE p.course_id = e.course_id 
        AND p.stu_id = e.stu_id
        AND p.status IN ('Pending', 'Approved')
    )
");
$new_enrollment_stmt->bind_param("i", $student_id);
$new_enrollment_stmt->execute();
$new_enrollment_result = $new_enrollment_stmt->get_result();
while ($row = $new_enrollment_result->fetch_assoc()) {
    $new_enrolled_courses[] = $row;
    $total_payment += $row['course_fee'];
}
$new_enrollment_stmt->close();


// Handle receipt upload and payment
$success_message = "";
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['receipt']['name']);
        $upload_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_file)) {
            // Insert payment record into the payment table as "Pending"
            $payment_stmt = $conn->prepare("INSERT INTO payment (stu_id, course_id, fee_amount, receipt, status, updated_date) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $updated_date = date('Y-m-d H:i:s');
            foreach ($new_enrolled_courses as $course) {
                // Remove the course from enrollment and set status as 'Pending Payment'
                $update_enrollment_stmt = $conn->prepare("UPDATE enrollment SET status = 'Pending' WHERE stu_id = ? AND course_id = ?");
                $update_enrollment_stmt->bind_param("ii", $student_id, $course['course_id']);
                $update_enrollment_stmt->execute();
                $update_enrollment_stmt->close();

                // Insert into the payment table
                $payment_stmt->bind_param("iiiss", $student_id, $course['course_id'], $course['course_fee'], $file_name, $updated_date);
                $payment_stmt->execute();
            }
            $payment_stmt->close();

            $success_message = "Payment uploaded successfully! Please wait for admin verification.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Failed to upload receipt.";
        }
    } else {
        $error_message = "Please upload a receipt.";
    }
}

// Fetch pending payments
$pending_payments = [];
$pending_payment_stmt = $conn->prepare("SELECT p.receipt, p.status, p.updated_date, c.course_name 
                                        FROM payment p 
                                        JOIN course c ON p.course_id = c.id 
                                        WHERE p.stu_id = ? AND p.status = 'Pending'");
$pending_payment_stmt->bind_param("i", $student_id);
$pending_payment_stmt->execute();
$pending_payment_result = $pending_payment_stmt->get_result();
while ($row = $pending_payment_result->fetch_assoc()) {
    $pending_payments[] = $row;
}
$pending_payment_stmt->close();

// Fetch past payments
$past_payments = [];
$past_payment_stmt = $conn->prepare("SELECT p.receipt, p.status, p.updated_date, c.course_name 
                                     FROM payment p 
                                     JOIN course c ON p.course_id = c.id 
                                     WHERE p.stu_id = ? AND p.status != 'Pending'");
$past_payment_stmt->bind_param("i", $student_id);
$past_payment_stmt->execute();
$past_payment_result = $past_payment_stmt->get_result();
while ($row = $past_payment_result->fetch_assoc()) {
    $past_payments[] = $row;
}
$past_payment_stmt->close();
?>

<?php
include '../common/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll and Pay</title>
    <style>
        /* Forms */
        form {
            margin-bottom: 30px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
            color: #555;
        }

        input[type="date"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* Buttons */
        button, input[type="submit"] {
            background-color: #FAAC14;
            color: #fff;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-top: 10px;
        }

        button:hover, input[type="submit"]:hover {
            background-color: #FAAC14;
        }
    </style>
</head>
<body>
<div class="StudentContents" id="StudentContents">
    <div class="ContentsCon">

<h2>Enrollment</h2>

<h3>Courses</h3>
<form method="POST">
    <?php foreach ($courses as $course): ?>
        <div>
            <input type="checkbox" name="courses[]" value="<?php echo $course['id']; ?>"
                <?php echo in_array($course['id'], $enrolled_course_ids) ? 'disabled' : ''; ?> />
            <?php echo $course['course_name']; ?> (Fee: RM<?php echo number_format($course['course_fee'], 2); ?>)
        </div>
    <?php endforeach; ?>
    <br>
    <button type="submit" name="enroll">Enroll</button>
</form>

<h3>Courses Enrolled</h3>

<?php if (!empty($new_enrolled_courses)): ?>
<div class="AdminTablePhone">
    <table id="userTable" class="datatable table table-striped" border="1">
        <thead>
        <tr class="theadstick">
            <th>Course Name</th>
            <th>Course Fee</th>
        </tr>
        </thead>
        <thead>
        <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
            <td colspan="100"></td>

        </tr>
        </thead>
        <?php foreach ($new_enrolled_courses as $course): ?>
            <tr>
                <td><?php echo $course['course_name']; ?></td>
                <td><?php echo number_format($course['course_fee'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td><strong>Total Payment</strong></td>
            <td><strong><?php echo number_format($total_payment, 2); ?></strong></td>
        </tr>
    </table>
    <?php else: ?>
        <p>No pending enrolments found.</p>
    <?php endif; ?>

<h3>Submit Payment</h3>
<form method="POST" enctype="multipart/form-data">
    <label for="receipt">Upload Receipt:</label><br>
    <input type="file" name="receipt" id="receipt" accept="image/*,application/pdf" required><br><br>
    <button type="submit" name="submit_payment">Submit Payment</button>
</form>

<?php
if (!empty($error_message)) {
    echo "<p style='color: red;'>$error_message</p>";
}
if (!empty($success_message)) {
    echo "<p style='color: green;'>$success_message</p>";
}
?>

<h3>Pending Payments Approval</h3>

    <?php if (!empty($pending_payments)): ?>
    <div class="AdminTablePhone">
        <table id="userTable" class="datatable table table-striped" border="1">
            <thead>
            <tr class="theadstick">
                <th>Course Name</th>
                <th>Receipt</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>View Receipt</th>
            </tr>
            </thead>
            <thead>
            <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                <td colspan="100"></td>

            </tr>
            </thead>

            <?php foreach ($pending_payments as $payment): ?>
                <tr>
                    <td><?php echo $payment['course_name']; ?></td>
                    <td><?php echo $payment['receipt']; ?></td>
                    <td><?php echo $payment['status']; ?></td>
                    <td><?php echo $payment['updated_date']; ?></td>
                    <td><a href="receipts/<?php echo $payment['receipt']; ?>" target="_blank">View</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <p>No pending payments found.</p>
        <?php endif; ?>

<h3>Past Payments</h3>

        <?php if (!empty($past_payments)): ?>
        <div class="AdminTablePhone">
            <table id="userTable" class="datatable table table-striped" border="1">
                <thead>
                <tr class="theadstick">
                    <th>Course Name</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>View Receipt</th>
                </tr>
                </thead>
                <thead>
                <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                    <td colspan="100"></td>

                </tr>
                </thead>
                <?php foreach ($past_payments as $payment): ?>
                    <tr>
                        <td><?php echo $payment['course_name']; ?></td>
                        <td><?php echo $payment['receipt']; ?></td>
                        <td><?php echo $payment['status']; ?></td>
                        <td><?php echo $payment['updated_date']; ?></td>
                        <td><a href="receipts/<?php echo $payment['receipt']; ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
                <p>No past payments found.</p>
            <?php endif; ?>


</body>
</html>
