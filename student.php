<?php
include 'config.php';

// variabes init
$success_message = '';
$error_message = '';
$student_id = '';
$name = '';
$email = '';
$dept_name = '';
$password = '';
$student_type = '';
$total_credits = '';
$class_standing = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // get form data
    $action = $_POST['action'];

    if ($action == 'login') {
        // handle login attempt
        $email = $_POST["email"];
        $password = $_POST["password"];

        // check creds in account table
        $login_sql = "SELECT * FROM account WHERE email = '$email' AND password = '$password'";
        $login_result = mysqli_query($conn, $login_sql);

        if (mysqli_num_rows($login_result) > 0) {
            // get account type
            $account = mysqli_fetch_assoc($login_result);
            $account_type = $account['type'];

            // redirect based on account type
            if ($account_type == 'admin') {
                header("Location: admin.php");
                exit();
            } else if ($account_type == 'instructor') {
                header("Location: admin.php");
                exit();
            } else if ($account_type == 'student') {
                // get student ID from student table
                $student_sql = "SELECT student_id FROM student WHERE email = '$email'";
                $student_result = mysqli_query($conn, $student_sql);

                if (mysqli_num_rows($student_result) > 0) {
                    $student = mysqli_fetch_assoc($student_result);
                    $student_id = $student['student_id'];

                    // redirect to student regist. page
                    header("Location: student_register.php?student_id=" . $student_id);
                    exit();
                } else {
                    $error_message = "Student not found.";
                }
            }
        } else {
            $error_message = "Invalid email or password.";
        }
    }
    else if ($action == 'create') {
        // create new student account
        $student_id = $POST['student_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $dept_name = $_POST['dept_name'];
        $password = $_POST['password'];
        $student_type = $_POST['student_type'];

        // handle 'other' department
        if ($dept_name == 'other' && isset($_POST['other_dept']) && !empty($_POST['other_dept'])) {
            $dept_name = $_POST['other_dept'];
        }

        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check if student already exists
            $check_sql = "SELECT * FROM student WHERE student_id = '$student_id' OR email = '$email'";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Student ID or email already exists.");
            }

            // check if department exists
            $dept_sql = "SELECT * FROM department WHERE dept_name = '$dept_name'";
            $dept_result = mysqli_query($conn, $dept_sql);

            if (mysqli_num_rows($dept_result) == 0) {
                // insert department if it doesn't exist
                $insert_dept_sql = "INSERT INTO department (dept_name, location) VALUES ('$dept_name', 'TBD')";
                if (!mysqli_query($conn, $insert_dept_sql)) {
                    throw new Exception("Error creating department: " . mysqli_error($conn));
                }
            }

            // insert into account table
            $account_sql = "INSERT INTO account (email, password, type) VALUES ('$email', '$password', 'student')";
            if (!mysqli_query($conn, $account_sql)) {
                throw new Exception("Error creating account: " . mysqli_error($conn));
            }

            // insert into student table
            $student_sql = "INSERT INTO student (student_id, name, email, dept_name) VALUES ('$student_id', '$name', '$email', '$dept_name')";
            if (!mysqli_query($conn, $student_sql)) {
                throw new Exception("Error creating student record: " . mysqli_error($conn));
            }

            // insert into specific student type table
            if ($student_type == 'undergraduate') {
                $total_credits = $_POST['total_credits'] ?? 0;
                $class_standing = $_POST['class_standing'] ?? 'Freshman';

                undergrad_sql = "INSERT INTO undergraduate (student_id, total_credits, class_standing) VALUES ('$student_id', '$total_credits', '$class_standing')";
                if (!mysqli_query($conn, $undergrad_sql)) {
                    throw new Exception("Error creating undergrad record: " . mysqli_error($conn));
                }
            } else if ($student_type == 'master') {
                $total_credits = $_POST['total_credits'] ?? 0;

                $master_sql = "INSERT INTO master (student_id, total_credits) VALUES ('$student_id', '$total_credits')";
                if (!mysqli_query($conn, $master_sql)) {
                    throw new Exception("Error creating master's record: " . mysqli_error($conn));
                }
            } else if ($student_type == 'phd') {
                $phd_sql = "INSERT INTO PhD (student_id, qualifier, proposal_defence_date, dissertation_defence_date) VALUES ('$student_id', NULL, NULL, NULL)";
                if (!mysqli_query($conn, $phd_sql)) {
                    throw new Exception("Error creating PhD record: " . mysqli_error($conn));
                }
            }

            // commit transaction
            mysqli_commit($conn);
            $success_message = "Student account created successfully!";

            // clear form fields after successful submission
            $student_id = '';
            $name = '';
            $email = '';
            $dept_name = '';
            $password = '';
            $student_type = '';
            $total_credits = '';
            $class_standing = '';
        } catch (Exception $e) {
            // rollback transaction on error
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else if ($action == 'update') {
        // for existing student account
        $student_id = $_POST['student_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $dept_name = $_POST['dept_name'];
        $student_type =$_POST['student_name'];

        // handle 'other' department
        if ($dept_name == 'other' && isset($_POST['other_dept']) && !empty($_POST['other_dept'])) {
            $dept_name = $_POST['other_dept'];
        }

        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check if student exists
            $check_sql = "SELECT * FROM student WHERE student_id = '$student_id'";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) == 0) {
                throw new Exception("Student ID not found.");
            }

            // check if department exists
            $dept_sql = "SELECT * FROM department WHERE dept_name = '$dept_name'";
            $dept_result = mysqli_query($conn, $dept_sql);

            if (mysqli_num_rows($dept_result) == 0) {
                // insert department if it doesn't exist
                $insert_dept_sql = "INSERT INTO department (dept_name, location) VALUES ('$dept_name', 'TBD')";
                if (!mysqli_query($conn, $insert_dept_sql)) {
                    throw new Exception("Error creating department: " . mysqli_error($conn));
                }
            }

            // get original student email for account table
            $student_row = mysqli_fetch_assoc($check_result);
            $original_email = student_row['email'];

            // update student table
            $student_sql = "UPDATE student SET name = '$name, email = '$email', dept_name = '$dept_name' WHERE student_id = '$student_id'";
            if (!mysqli_query($conn, $student_sql)) {
                throw new Exception("Error updating student record: " . mysqli_error($conn));
            }
            
            // update account table if email changed
            if ($original_email != $email) {
                $account_sql = "UPDATE account SET email = '$email' WHERE email = '$original_email'";
                if (!mysqli_query($conn, $account_sql)) {
                    throw new Exception("Error updating account: " . mysqli_error($conn));
                }
            }

            // update student for each type
            if ($student_type == 'undergraduate') {
                $total_credits = $_POST['total_credits'] ?? 0 // ?? 0 if null set to 0
                $class_standing = $_POST['class_standing'] ?? 'Freshman';

                // check for undergrad record
                $check_undergrad_sql = "SELECT * FROM undergraduate WHERE student_id = '$student_id'";
                $check_undergrad_result = mysqli_query($conn, $check_undergrad_sql);

                if (mysqli_num_rows($check_undergrad_result) > 0) {
                    $undergrad_sql = "UPDATE undergraduate SET total_credits = '$total_credits', class_standing = '$class_standing' WHERE student_id = '$student_id'";
                } else {
                    $undergrad_sql = "INSERT INTO undergraduate (student_id, total_credits, class_standing) VALUES ('$student_id', '$total_credits', '$class_standing')";
                }

                if (!mysqli_query($conn, $undergrad_sql)) {
                    throw new Exception("Error updating undergraduate record: " . mysqli_error($conn));
                }

            } else if ($student_type == 'master') {
                // check for master record
                $check_master_sql = "SELECT * FROM master WHERE student_id = '$student_id'";
                $check_master_result = mysqli_query($conn, $check_master_sql);

                if (mysqli_num_rows($check_master_result) > 0) {
                    $master_sql = "UPDATE master SET total_credits = '$total_credits' WHERE student_id = '$student_id'";
                } else {
                    $master_sql = "INSERT INTO master (student_id, total_credits) VALUES ('$student_id', '$total_credits')";
                }

                if (!mysqli_query($conn, $master_sql)) {
                    throw new Exception("Error updating master's record: " . mysqli_error($conn));
                }
            } else if ($student_type == 'phd') {
                // check for PhD record
                $check_phd_sql = "SELECT * FROM PhD WHERE student_id = '$student_id'";
                $check_phd_result = mysqli_query($conn, $check_phd_sql);

                if (mysqli_num_rows($check_phd_result) == 0) {
                    $phd_sql = "INSERT INTO PhD (student_id, qualifier, proposal_defence_date, dissertation_defence_date) VALUES ('$student_id', NULL, NULL, NULL)";
                    if (!mysqli_query($conn, $phd_sql)) {
                        throw new Exception("Error updating PhD record: " . mysqli_error($conn));
                    }
                }
            }

            // commit transaction
            mysqli_commit($conn);
            $success_message = "Student account updated successfully!";
        } catch (Exception $e) {
            // rollback transaction on error
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}
?>