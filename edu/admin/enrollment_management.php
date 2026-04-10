<?php
// Include necessary files
require_once '../config/db_config.php';
require_once '../lib/authlib.php';
require_once '../lib/compresslib.php';
require_once '../vendor/autoload.php';  // PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start the session to use CSRF token and error messages
session_start();

// Check if the user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../common/login.php');
    exit();
}

// Validate CSRF token if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    die("CSRF token mismatch.");
}

// Generate CSRF token for the form
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '../config/smtp_config.php';

// Check if the form is submitted for status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_id'], $_POST['status'])) {
    // Trim and sanitize the status to remove extra spaces and avoid invisible characters
    $payment_id = $_POST['payment_id'];
    $status = trim($_POST['status']);  // Trim spaces from status

    // Start a transaction to ensure both tables are updated successfully
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $conn->begin_transaction();

    try {
        // Update the status in the payment table
        $stmt1 = $conn->prepare("UPDATE payment SET status = ? WHERE id = ?");
        $stmt1->bind_param("si", $status, $payment_id);

        if (!$stmt1->execute()) {
            throw new Exception("Error updating payment status: " . $stmt1->error);
        }

        // Update the status in the enrollment table based on both stu_id and course_id
        $stmt2 = $conn->prepare("UPDATE enrollment 
                                 SET status = ? 
                                 WHERE stu_id = (SELECT stu_id FROM payment WHERE id = ?) 
                                   AND course_id = (SELECT course_id FROM payment WHERE id = ?)");
        $stmt2->bind_param("sii", $status, $payment_id, $payment_id);

        if (!$stmt2->execute()) {
            throw new Exception("Error updating enrollment status: " . $stmt2->error);
        }

        // If the status is approved, send an email to the student
        if ($status === 'approved') {
            // Get the email address, course name, and course fee from the user and course tables
            $stmt3 = $conn->prepare("SELECT u.email, c.course_name, c.course_fee 
                             FROM user u 
                             JOIN enrollment e ON u.id = e.stu_id 
                             JOIN course c ON e.course_id = c.id 
                             WHERE e.stu_id = (SELECT stu_id FROM payment WHERE id = ?) 
                               AND e.course_id = (SELECT course_id FROM payment WHERE id = ?)");
            $stmt3->bind_param("ii", $payment_id, $payment_id);  // Bind both payment ID to both placeholders
            $stmt3->execute();
            $stmt3->bind_result($email, $course_name, $course_fee);

            // Fetch the result
            if ($stmt3->fetch()) {
                // Send an email using PHPMailer
                if ($email) {
                    $mail = new PHPMailer();
                    $mail->isSMTP();
                    $mail->Host = $smtpHost;  // Set the SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUsername;  // SMTP username
                    $mail->Password = $smtpPassword;  // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $smtpPort;

                    // Recipients
                    $mail->setFrom($smtpUsername, 'Admin');
                    $mail->addAddress($email);  // Student's email

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Course Enrollment Status Approved';
                    $mail->Body = "
            Dear Student,<br><br>

            We are pleased to inform you that your enrollment for the course <b>" . htmlspecialchars($course_name) . "</b> has been approved.<br><br>

            Below are the details of your enrollment:<br>
            <ul>
                <li><b>Course Name:</b> " . htmlspecialchars($course_name) . "</li>
                <li><b>Course Fee:</b> RM " . htmlspecialchars($course_fee) . "</li>
                <li><b>Status:</b> Approved</li>
            </ul><br>

            Please ensure that you complete any further required steps for your enrollment.<br><br>

            Should you have any questions or require further assistance, feel free to contact us.<br><br>

            Best regards,<br>
            Admin<br>
            ";

                    // Send the email
                    if (!$mail->send()) {
                        error_log("Mailer Error: " . $mail->ErrorInfo);  // Log to server logs
                        $_SESSION['error'] = "There was a problem sending the email. Please try again later.";
                    }
                }
            } else {
                throw new Exception("Error: No email found for student.");
            }

            // Free the result set to prevent commands from being out of sync
            $stmt3->free_result();
            $stmt3->close();
        }


        // Commit the transaction
        $conn->commit();

        // Redirect to the same page to show the updated data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        // If an error occurs, rollback the transaction
        $conn->rollback();
        die("Error updating records: " . $e->getMessage());
    }
}

$sql = "SELECT CONCAT(u.first_name, ' ', u.last_name) AS name, 
               c.course_name, 
               c.course_code, 
               p.fee_amount, 
               p.receipt, 
               p.status, 
               p.id AS payment_id
        FROM enrollment e
        JOIN user u ON e.stu_id = u.id
        JOIN course c ON e.course_id = c.id
        JOIN payment p ON e.course_id = p.course_id AND e.stu_id = p.stu_id";

// Execute the query
$result = $conn->query($sql);

// Check if the query was successful
if ($result === false) {
    die("Error executing query: " . $conn->error);  // Handle error if the query fails
}
?>

<?php
include '../common/admin_sidebar.php';
?>

<div class="AdminContents" id="AdminContents">
    <div class="ContentsCon">

        <h3>Enrollment and Payment Information</h3>
        <input type="text" id="searchInputEnrollment" class="AdminSearch" placeholder="Search..." />

        <?php
        // Check if any results were returned
        if ($result->num_rows > 0) {
            ?>
            <div class="AdminTablePhone">
                <table id="enrollmentTable" class="datatable table table-striped" border="1">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Course Name</th>
                            <th>Course Code</th>
                            <th>Fee Amount</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <thead>
                        <tr style="background-color: #f1f1f1 !important; border-bottom-color:#f1f1f1 !important;">
                            <td colspan="100"></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['fee_amount']); ?></td>
                                <td>
                                    <?php if (!empty($row['receipt'])): ?>
                                        <a href="../images/<?php echo htmlspecialchars($row['receipt']); ?>" target="_blank">View
                                            Receipt</a>
                                    <?php else: ?>
                                        No receipt
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <!-- Edit Form for Status -->
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <select name="status">
                                            <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo ($row['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm AdminFormBtn"
                                            style="font-size: 12px!important;padding: 10px 0!important;">Update Status</button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                            $counter++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div id="paginationEnrollment" class="AdminTableBolowBtn"></div>
            <?php
        }
        ?>
    </div>
</div>

<?php
include '../common/footer.php';
?>
</div>
</div>
<!--end  Main Content -->
</body>

</html>