<?php
// Database connection details
$host = 'localhost';
$dbname = 'hydro_dyna';
$username = 'root';
$password = 'root'; // Assuming the password is 'root', adjust if different

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if `id` is provided
    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        // Prepare the DELETE statement
        $sql = "DELETE FROM clients WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        // Bind the `id` parameter
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Execute the statement
        if($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete the record']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID not provided']);
    }
} catch(PDOException $e) {
    // Handle connection error
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
}

?>
