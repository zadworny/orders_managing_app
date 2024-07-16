<?php
require_once '../database/connect.php';
require_once '../login/user.php';

// Initialize the Database connection
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// This is a very basic example. Remember to sanitize and validate all inputs in a real application.
if (isset($_POST['checkboxState'])) {
    $checkboxState = $_POST['checkboxState'];
    $dbColumn = 'checkbox_' . $_POST['dbTable'];

    $id = $user->userId($_SESSION['login']); // individual checkboxes set for each user
    
    $sql = "UPDATE users SET " . $dbColumn . " = :checkboxes WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':checkboxes' => $checkboxState, ':id' => $id]);
    if ($stmt->rowCount() == 0) {
        echo "No records were updated.";
    } else {
        echo "Success";
    }
} else {
    // Handle error or redirect
    echo "Invalid request";
}
?>