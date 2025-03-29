<?php
// start session
session_start();

include 'config.php';

// init variables
$success_message = '';
$error_message = '';
$student_id = '';
$course_info = null;
$course_rated = null;

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $_SERVER["REQUEST_METHOD"] = "POST";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($student_id)) {
        $sql = "SELECT course_id, student_id FROM take WHERE student_id = '$student_id' AND grade <> 'NULL'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $course_info = mysqli_fetch_assoc($result);
        } else {
            $error_message = "*No courses to rate.";
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
    <h1>Course Rating</h1>
    <form method="post" action="">
        <div>
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required>
        </div>
        <button type="submit">Search</button>
    </form>

    <?php if ($course_info): ?>
        <h2>Courses you have completed:</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Semester</th>
                    <th>Year</th>
                    <th>Instructor</th>
                    <th>Grade</th>
                    <th>Overall Rating</th>
                </tr>
                <tr>
                    <td>COMP2010</td>
                    <td>Computing 3</td>
                    <td>Fall</td>
                    <td>2023</td>
                    <td>Johannes Weis</td>
                    <td>Grade</td>
                    <td>4.5/5.0</td>
                </tr>
                <tr>
                    <td>COMP2040</td>
                    <td>Computing 4</td>
                    <td>Spring</td>
                    <td>2022</td>
                    <td>Yelena Rykalova</td>
                    <td>Grade</td>
                    <td>4.3/5.0</td>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <h2>Select a Course to Rate:</h2>

        <form method="post" action="">

            <label for="course">Choose a Course:</label>
            <select name="course" id="course">
            <?php
                $sql = "SELECT course_id, student_id
                        FROM take
                        WHERE student_id = '$student_id'";

                $result = mysqli_query($conn, $sql);
                $num = mysqli_num_rows($result);

                if ($num > 1) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $course_id = $row['course_id'];
                        echo "<option value='$course_id'>$course_id</option>";
                    }
                }
            ?>
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

            <?php
                // check whether the student have rate a class
                $sql = "SELECT *
                        FROM rate
                        WHERE rate.student_id = '$student_id'";
                $result = $conn->query($sql);
                $row = mysqli_fetch_assoc($result);

                // if rate a class, update the $course_rated
                if ($row) {
                    $course_rated = $row["course_id"];
                }
            ?>

        </form>
    <?php endif; ?>

    <!--Student have rate a course-->
    <?php if($course_rated): ?>
        <h2>Your Rating:</h2>
        <p>You have rated at least one class</p>
    <?php endif; ?>

    <!--Student have not rate a course-->
    <?php if(!$course_rated): ?>
        <h2>Your Rating:</h2>
        <p>You haven't rated any courses yet</p>
    <?php endif; ?>
</body>
</html>