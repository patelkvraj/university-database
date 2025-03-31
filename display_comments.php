<?php
// start session
session_start();

include 'config.php';

$course_id = $_GET['course_id'];

$sql = "SELECT *
        FROM rate r
            JOIN student s ON r.student_id = s.student_id
        WHERE r.comments <> 'NULL' AND r.comments != '' AND r.course_id = '$course_id'";

$result = mysqli_query($conn, $sql);
$comments = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
}
include 'header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course Comments</title>
</head>
<body>
    <div>
        <h2>Course Comments</h2>
        <?php if ($comments): ?>
            <?php foreach ($comments as $comment): ?>
                <div>
                    <strong>Student:</strong> <?php echo htmlspecialchars($comment['name']); ?><br>
                    <strong>Rating:</strong> <?php echo htmlspecialchars($comment['rate']); ?><br>
                    <strong>Comment:</strong> <?php echo nl2br(htmlspecialchars($comment['comments'])); ?>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No comments available.</p>
        <?php endif; ?>
    </div>
</body>
</html>