<?php
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$email = $_POST["email"];
$password = $_POST["password"];
$type = "student";
$sql = "INSERT INTO account (email, password, type) VALUES ('$email', '$password',
'$type')";
if (mysqli_query($conn, $sql)) {
echo "Account created successfully!";
} else {
echo "Error: " . mysqli_error($conn);
}
}
?>