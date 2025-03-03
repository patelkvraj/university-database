<?php
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$student_id = $_POST["student_id"];
$section_id = $_POST["section_id"];
// Check if section has space
$check = "SELECT COUNT(*) as count FROM take WHERE section_id = '$section_id'";
$result = mysqli_query($conn, $check);
$row = mysqli_fetch_assoc($result);
if ($row["count"] >= 15) {
echo "Error: Section is full.";
} else {
$sql = "INSERT INTO take (student_id, course_id, section_id, semester, year)
VALUES ('$student_id', (SELECT course_id FROM section WHERE
section_id='$section_id'),
'$section_id', 'Spring', 2025)";
if (mysqli_query($conn, $sql)) {
echo "Successfully enrolled!";
} else {
echo "Error: " . mysqli_error($conn);
}
}
}
?>