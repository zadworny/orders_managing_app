<?php
require_once '../database/connect.php';
require_once '../login/user.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize the Database connection
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Check if POST variables are set
$dbTable = isset($_POST['dbTable']) ? $_POST['dbTable'] : null;
$name = isset($_POST['name']) ? $_POST['name'] : null;
$value = isset($_POST['value']) ? $_POST['value'] : null;
$method = isset($_POST['method']) ? $_POST['method'] : null;

/*if ($dbTable === null || $name === null || $method === null) {
    die(json_encode(['success' => false, 'error' => 'Required parameters not set']));
}*/

$totalrows = 0;

/*$dbTable = 'others';
$name = 'complaints';
$value = '2024-06-15T03:04';
$method = 'update';*/

$success = false;
if ($method == 'select') {
    $tsql = "SELECT COUNT(*) AS totalrows FROM orders WHERE deleted = '' OR deleted IS NULL";
    $tstmt = $db->prepare($tsql);
    $tstmt->execute();
    $tresult = $tstmt->fetch();
    $totalrows = $tresult['totalrows'];
    
    $sql = "SELECT value FROM " . $dbTable . " WHERE name = :name AND value IS NOT NULL AND value != ''";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $success = $result['value'];
    } else {
        $success = '';
    }
} else if ($method == 'delete') {
    $sql = "UPDATE " . $dbTable . " SET value = :value WHERE name = :name";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => $name, ':value' => null]);
    $success = true;
} else if ($method == 'update') {
    $updateSql = "UPDATE " . $dbTable . " SET value = :value WHERE name = :name";
    $updateStmt = $db->prepare($updateSql);

    if ($updateStmt->execute([':name' => $name, ':value' => $value])) {
        if ($updateStmt->rowCount() > 0) {
            $success = true;
        } else {
            // No rows updated, check if the record exists
            $checkSql = "SELECT COUNT(*) FROM " . $dbTable . " WHERE name = :name";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([':name' => $name]);
            $recordExists = $checkStmt->fetchColumn() > 0;

            if ($recordExists) {
                // Record exists but no rows updated, so it means the value is the same
                $success = true;
            } else {
                // Insert the new record
                $insertSql = "INSERT INTO " . $dbTable . " (name, value) VALUES (:name, :value)";
                $insertStmt = $db->prepare($insertSql);
                if ($insertStmt->execute([':name' => $name, ':value' => $value])) {
                    $success = true;
                } else {
                    // Output error information
                    $errorInfo = $insertStmt->errorInfo();
                    echo "Error inserting data: " . $errorInfo[2];
                }
            }
        }
    } else {
        // Output error information
        $errorInfo = $updateStmt->errorInfo();
        echo "Error updating data: " . $errorInfo[2];
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => $success, 'totalrows' => $totalrows, 'method' => $method]);
?>
