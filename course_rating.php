<?php
include 'config.php';

// init variables
$success_message = '';
$error_message = '';
$student_id = '';
$course_info = null;

include 'config.php';

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $_SERVER["REQUEST_METHOD"] = "POST";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : $student_id;

    if (!empty($student_id)) {
        $sql = "SELECT course_id, semester, grade FROM take WHERE student_id = '$student_id'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $course_info = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Student not found.";
        }
    } else {
        $error_message = "Please enter a student ID.";
    }
}
// include header
include 'header.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Student Course History</h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required>
        </div>
        <button type="submit">Search</button>
    </form>

    <?php if ($course_info): ?>
        <h1>Select a Course to Rate:</h1>

        <form method="post" action="course_rating.php">

            <label for="course">Choose a Course:</label>
            <select name="course" id="course">
                <option value="web_dev">Computing I</option>
                <option value="data_sci">Computing II</option>
                <option value="python_intro">Computing III</option>
                <option value="java_adv">Computing IV</option>
            </select>

            <label for="rating">Your Rating:</label>
            <select name="rating" id="rating">
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">3 Stars</option>
                <option value="4">4 Stars</option>
                <option value="5">5 Stars</option>
            </select>

            <input type="hidden" name="student_name" value="Student Name"> <input type="submit" value="Submit Rating">
        </form>
    <?php endif; ?>
</body>
</html>