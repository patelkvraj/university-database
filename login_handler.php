<?php

// start session
session_start();

include 'config.php';

// init variables
$error_message = '';

// handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // check creds in account table
    $login_sql = "SELECT * FROM account WHERE email = '$email' AND password = '$password'";
    $login_result = mysqli_query($conn, $login_sql);

    if (mysqli_num_rows($login_result) > 0) {
        // get account type
        $account = mysqli_fetch_assoc($login_result);
        $account_type = $account['type'];

        // store login info in session
        $_SESSION['logged_in'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['account_type'] = $account_type;

        // redirect based on account type
        if ($account_type == 'admin') {
            $_SESSION['admin'] = true;
            header("Location: admin.php");
            exit();
        } else if ($account_type == 'instructor') {
            // get instructor ID
            $instructor_sql = "SELECT instructor_id FROM instructor WHERE email = '$email'";
            $instructor_result = mysqli_query($conn, $instructor_sql);

            if (mysqli_num_rows($instructor_result) > 0) {
                $instructor = mysqli_fetch_assoc($instructor_result);
                $instructor_id = $instructor['instructor_id'];
                $_SESSION['instructor_id'] = $instructor_id;
                header("Location: instructor.php?instructor_id=" . $instructor_id);
                exit();
            } else {
                $error_message = "Instructor record not found.";
            }
        } else if ($account_type == 'student') {
            // get student ID
            $student_sql = "SELECT student_id FROM student WHERE email = '$email'";
            $student_result = mysqli_query($conn, $student_sql);

            if (mysqli_num_rows($student_result) > 0) {
                $student = mysqli_fetch_assoc($student_result);
                $student_id = $student['student_id'];
                $_SESSION['student_id'] = $student_id;
                header("Location: student_dashboard.php?student_id=" . $student_id);
                exit();
            } else {
                $error_message = "Student record not found.";
            }
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Error</title>
</head>
<body>
    <h1>Login Error</h1>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <p><a href="index.html">Return to login page</a></p>
</body>
</html>