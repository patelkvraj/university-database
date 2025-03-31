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
$course_to_rate = null;
$new_course_to_rate = null;
$rate_for_course = null;

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

/*Handle course rating after user submission*/
if (isset($_POST['rate_submission'])) {
    // Form 1 was submitted
    $course_to_rate = $_POST['course'];
    $rate_for_course = $_POST['rating'];
    $comments = $_POST['comments'];
    $comments = mysqli_real_escape_string($conn, $comments);
    // echo "You rated " . $course_to_rate . " with score " . $rate_for_course;

    $sql = "INSERT INTO rate(student_id, course_id, rate, comments) VALUES('$student_id', '$course_to_rate', '$rate_for_course', '$comments')";
    $result = $conn->query($sql);

    if ($result) {
        echo "Rating submitted successfully.";
    } else {
        echo "An error occurred while submitting your rating. Please try again.";
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
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php
                   $sql = "SELECT c.course_id, c.course_name, t.semester, t.year, t.grade, AVG(r.rate) AS average_rate, i.instructor_name
                        FROM
                            take t
                        JOIN
                            course c ON t.course_id = c.course_id AND t.student_id = '$student_id' AND t.grade <> 'NULL'
                        LEFT JOIN
                            rate r ON t.course_id = r.course_id
                        LEFT JOIN
                            section s ON t.course_id = s.course_id AND t.semester = s.semester AND t.year = s.year
                        LEFT JOIN 
                            instructor i ON s.instructor_id = i.instructor_id
                        GROUP BY
                            c.course_id, c.course_name, t.semester, t.year, t.grade, i.instructor_id";
                   $result = mysqli_query($conn, $sql);

                   while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>$row[course_id]</td>";
                    echo "<td>$row[course_name]</td>";
                    echo "<td>$row[semester]</td>";
                    echo "<td>$row[year]</td>";
                    echo "<td>$row[instructor_name]</td>";
                    echo "<td>$row[grade]</td>";
                    // Format the rating to one decimal place and remove trailing zeros
                    $formattedRating = rtrim(sprintf("%.1f", $row['average_rate']), '0');
                    $formattedRating = rtrim($formattedRating, '.');
                    echo "<td>$formattedRating</td>";
                    
                    echo "<td><a href='display_comments.php?course_id=$row[course_id]'>View Comments</a></td>";
                    echo "</tr>";
                   } 
                ?>
            </tbody>
        </table>

        <h2>Select a New Course to Rate:</h2>
        <?php
            $sql = "SELECT t.course_id
                    FROM take t
                    WHERE t.student_id = '$student_id'
                        AND t.grade <> 'NULL'
                        AND t.course_id NOT IN (SELECT r.course_id FROM rate r WHERE r.student_id = '$student_id')";

            $result = mysqli_query($conn, $sql);
            $row = mysqli_num_rows($result);
        ?>
            <?php if ($row) { ?>
                <form method="post" action="">
                    <label for="course">Choose a Course:</label>
                    <select name="course" id="course">
                    <?php
                        $sql = "SELECT t.course_id
                                FROM take t
                                WHERE t.student_id = '$student_id'
                                    AND t.grade <> 'NULL'
                                    AND t.course_id NOT IN (SELECT r.course_id FROM rate r WHERE r.student_id = '$student_id')";

                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_num_rows($result);

                        if ($row > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $course_id = $row['course_id'];
                                echo "<option value='$course_id'>$course_id</option>";
                            }
                        }
                    ?>
                    </select>

                    <label for="rating">with a rate:</label>
                    <select name="rating" id="rating">
                        <option value="0.5">0.5</option>
                        <option value="1">1</option>
                        <option value="1.5">1.5</option>
                        <option value="2">2</option>
                        <option value="2.5">2.5</option>
                        <option value="3">3</option>
                        <option value="3.5">3.5</option>
                        <option value="4">4</option>
                        <option value="4.5">4.5</option>
                        <option value="5">5</option>
                    </select>

                    <label for="comments">Comments:</label>
                    <textarea name="comments" id="comments" rows="4" cols="50" placeholder="Enter your comments here..."></textarea>
                    
                    <input type="hidden" name="rate_submission" value="Student Name"> <input type="submit" value="Submit Rating">
                </form>
            <?php } else { ?>
                <?php echo "No new courses to rate"; ?>
            <?php } ?>
        <?php endif; ?>
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

    <!--Student have rate a course-->
    <?php if($course_rated): ?>
        <h2>Your Rating:</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Semester</th>
                    <th>Year</th>
                    <!-- <th>Instructor</th> -->
                    <th>Grade</th>
                    <th>Your Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT c.course_id, c.course_name, t.semester, t.year, t.grade, r.rate
                            FROM
                                take t
                            JOIN
                                course c ON t.course_id = c.course_id AND t.student_id = '$student_id' AND t.grade <> 'NULL'
                            JOIN
                                rate r ON t.course_id = r.course_id AND  r.student_id = '$student_id'";
                        
                    $result = mysqli_query($conn, $sql);

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>$row[course_id]</td>";
                        echo "<td>$row[course_name]</td>";
                        echo "<td>$row[semester]</td>";
                        echo "<td>$row[year]</td>";
                        // echo "<td>Johannes Weis</td>";
                        echo "<td>$row[grade]</td>";
                        echo "<td>$row[rate]</td>";
                        echo "</tr>";
                    } 
                    ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!--Student have not rate a course-->
    <?php if(!$course_rated): ?>
        <h2>Your Rating:</h2>
        <p>You haven't rated any courses yet</p>
    <?php endif; ?>
</body>
</html>