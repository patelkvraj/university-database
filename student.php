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

// check if student_id passed in URL
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // automatically search for student data
    $search_sql = "SELECT s.*,
                    u.total_credits as undergrad_credits, u.class_standing,
                    m.total_credits as master_credits,
                    CASE
                        WHEN u.student_id IS NOT NULL THEN 'undergraduate'
                        WHEN m.student_id iS NOT NULL THEN 'master'
                        WHEN p.student_id IS NOT NULL THEN 'phd'
                        ELSE NULL
                    END as student_type
                FROM student s
                LEFT JOIN undergraduate u ON s.student_id = u.student_id
                LEFT JOIN master m ON s.student_id = m.student_id
                LEFT JOIN PhD p ON s.student_id = p.student_id
                WHERE s.student_id = '$student_id'";

    $result = mysqli_query($conn, $search_sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'];
        $email = $row['email'];
        $dept_name = $row['dept_name'];
        $student_type = $row['student_type'];

        if ($student_type == 'undergraduate') {
            $total_credits = $row['undergrad_credits'];
            $class_standing = $row['class_standing'];
        } else if ($student_type == 'master') {
            $total_credits = $row['master_credits'];
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // get form data
    $action = $_POST['action'];

    if ($action == 'create') {
        // create new student account
        $student_id = $_POST['student_id'];
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

                $undergrad_sql = "INSERT INTO undergraduate (student_id, total_credits, class_standing) VALUES ('$student_id', '$total_credits', '$class_standing')";
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
        $student_type = $_POST['student_type'];

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
            $original_email = $student_row['email'];

            // update student table
            $student_sql = "UPDATE student SET name = '$name', email = '$email', dept_name = '$dept_name' WHERE student_id = '$student_id'";
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
                $total_credits = $_POST['total_credits'] ?? 0; // ?? 0 if null set to 0
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
    } else if ($action == 'search') {
        // search for existing student to edit
        $student_id = $_POST['student_id'];

        $search_sql = "SELECT s.*,
                        u.total_credits as undergrad_credits, u.class_standing,
                        m.total_credits as master_credits,
                        CASE
                            WHEN u.student_id IS NOT NULL THEN 'undergraduate'
                            WHEN m.student_id IS NOT NULL THEN 'master'
                            WHEN p.student_id IS NOT NULL THEN 'phd'
                            ELSE NULL
                        END as student_type
                    FROM student s
                    LEFT JOIN undergraduate u ON s.student_id = u.student_id
                    LEFT JOIN master m ON s.student_id = m.student_id
                    LEFT JOIN PhD p ON s.student_id = p.student_id
                    WHERE s.student_id = '$student_id'";

        $result = mysqli_query($conn, $search_sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $name = $row['name'];
            $email = $row['email'];
            $dept_name = $row['dept_name'];
            $student_type = $row['student_type'];

            if ($student_type == 'undergraduate') {
                $total_credits = $row['undergrad_credits'];
                $class_standing = $row['class_standing'];
            } else if ($student_type == 'master') {
                $total_credits = $row['master_credits'];
            }
        } else {
            $error_message = "Student not found.";
        }
    }
}

// get list of departments for dropdown
$dept_query = "SELECT dept_name FROM department";
$dept_result = mysqli_query($conn, $dept_query);

// include header
include 'header.php';

// *********************
// END OF PHP LOGIC (mostly)
// *********************
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $student_id ? 'Update Student Account' : 'Create Student Account'; ?></title>
</head>
<body>
    <h1><?php echo $student_id ? 'Update Student Account' : 'Create Student Account'; ?></h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($student_id): ?>
        <!-- If editing existing account, no need to show search form -->
    <?php else: ?>
        <h2>Search for Existing Student</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="search">
            <div>
                <label for="student_id_search">Student ID:</label>
                <input type="text" id="student_id_search" name="student_id" required>
            </div>
            <button type="submit">Search</button>
        </form>

        <hr>
    <?php endif; ?>

    <h2><?php echo $student_id ? 'Update Student Account' : 'Create New Student Account'; ?></h2>
    <form method="post" action="">
        <input type="hidden" name="action" value="<?php echo $student_id ? 'update' : 'create'; ?>">

        <div>
            <label for="student_id_form">Student ID:</label>
            <input type="text" id="student_id_form" name="student_id" value="<?php echo $student_id; ?>" <?php echo $student_id ? 'readonly' : ''; ?> required>
        </div>

        <div>
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
        </div>

        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
        </div>

        <div>
            <label for="dept_name">Department:</label>
            <select id="dept_name" name="dept_name" required>
                <option value="">Select Department</option>
                <?php
                $departments = array();
                if (mysqli_num_rows($dept_result) > 0) {
                    mysqli_data_seek($dept_result, 0); // reset result pointer
                    while($dept_row = mysqli_fetch_assoc($dept_result)) {
                        $departments[] = $dept_row['dept_name'];
                        $selected = ($dept_row['dept_name'] == $dept_name) ? 'selected' : '';
                        echo "<option value='" . $dept_row['dept_name'] . "' $selected>" . $dept_row['dept_name'] . "</option>";
                    }
                }
                ?>
                <option value="other" <?php echo (!empty($dept_name) && !in_array($dept_name, $departments)) ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div>
            <label for="other_dept">If Other, Enter Department Name:</label>
            <input type="text" id="other_dept" name="other_dept" value="<?php echo (!empty($dept_name) && !in_array($dept_name, $departments)) ? $dept_name : ''; ?>">
        </div>

        <?php if (!$student_id): ?>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <?php endif; ?>

        <div>
            <label for="student_type"> Student Type:</label>
            <select id="student_type" name="student_type" required>
                <option value="">Select Type</option>
                <option value="undergraduate" <?php echo ($student_type == 'undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                <option value="master" <?php echo ($student_type == 'master') ? 'selected' : ''; ?>>Master</option>
                <option value="phd" <?php echo ($student_type == 'phd') ? 'selected' : ''; ?>>PhD</option>
            </select>
        </div>

        <!-- fields for undergraduate -->
        <?php if ($student_type == 'undergraduate'): ?>
        <div id="undergraduate_fields">
            <div>
                <label for="total_credits_ug">Total Credits:</label>
                <input type="number" id="total_credits_ug" name="total_credits" value="<?php echo $total_credits; ?>" min="0">
            </div>
            <div>
                <label for="class_standing">Class Standing:</label>
                <select id="class_standing" name="class_standing">
                    <option value="Freshman" <?php echo ($class_standing == 'Freshman') ? 'selected' : ''; ?>>Freshman</option>
                    <option value="Sophomore" <?php echo ($class_standing == 'Sophomore') ? 'selected' : ''; ?>>Sophomore</option>
                    <option value="Junior" <?php echo ($class_standing == 'Junior') ? 'selected' : ''; ?>>Junior</option>
                    <option value="Senior" <?php echo ($class_standing == 'Senior') ? 'selected' : ''; ?>>Senior</option>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <!-- fields for masters -->
        <?php if ($student_type == 'master'): ?>
        <div id="master_fields">
            <div>
                <label for="total_credits_ms">Total Credits:</label>
                <input type="number" id="total_credits_ms" name="total_credits" value="<?php echo $total_credits; ?>" min="0">
            </div>
        </div>
        <?php endif; ?>

        <button type="submit"><?php echo $student_id ? 'Update Account' : 'Create Account'; ?></button>
        <button type="reset">Reset Form</button>
        <?php if ($student_id): ?>
            <a href="student.php"><button type="button">New Student</button></a>
        <?php endif; ?>
    </form>
</body>
</html>
