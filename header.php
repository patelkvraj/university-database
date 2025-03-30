<?php
// start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// function to determine if user is logged in
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true;
}

// function to redirect to appropriate dashboard based on account type
function home_redirect() {
    if (!is_logged_in()) {
        return "index.html";
    }

    switch ($_SESSION['account_type']) {
        case 'admin':
            return "admin.php";
        case 'instructor':
            return "instructor.php?instructor_id=" . $_SESSION['instructor_id'];
        case 'student':
            return "student_dashboard.php?student_id=" . $_SESSION['student_id'];
        default:
            return "index.html";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'University Course Management'; ?></title>
</head>
<body>
    <div class="navigation">
        <a href="<?php echo home_redirect(); ?>">Home</a>

        <?php if (is_logged_in()): ?>
            <?php if ($_SESSION['account_type'] == 'student'): ?>
                | <a href="student.php?student_id=<?php echo $_SESSION['student_id']; ?>">Account Settings</a>
                | <a href="student_register.php?student_id=<?php echo $_SESSION['student_id']; ?>">Course Registration</a>
                | <a href="student_history.php?student_id=<?php echo $_SESSION['student_id']; ?>">Course History</a>
                | <a href="student_todo.php?student_id=<?php echo $_SESSION['student_id']; ?>">To-Do List</a>
                | <a href="course_rating.php?student_id=<?php echo $_SESSION['student_id']; ?>">Course Rating</a>
            <?php endif; ?>

            <?php if ($_SESSION['account_type'] == 'admin'): ?>
                | <a href="admin.php">Admin Dashboard</a>
                | <a href="grader_assignment.php">Grader Assignment</a>
                | <a href="phd_advisors.php">PhD Advisors</a>
            <?php endif; ?>

            <?php if ($_SESSION['account_type'] == 'instructor'): ?>
                | <a href="instructor.php?instructor_id=<?php echo $_SESSION['instructor_id']; ?>">Instructor Dashboard</a>
                | <a href="grader_assignment.php">Grader Assignment</a>
                | <a href="phd_advisors.php">Manage PhD Advisees</a>
            <?php endif; ?>

            | <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>

    <?php if (isset($success_message) && $success_message): ?>
        <div class="success-message"><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if (isset($error_message) && $error_message): ?>
        <div class="error-message"><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>
</body>
</html>