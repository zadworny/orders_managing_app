<?php
require_once '../database/connect.php';

$database = new Database();
$db = $database->getConnection();

// Determine if this is an update or insert
$id = $_POST['id'];
$isUpdate = isset($id) && !empty($id);
$table = 'events';

$columns = ['date_from', 'date_to', 'event', 'note', 'user'];
foreach ($columns as $c) {
    if ($c !== 'user') {
        $params[':' . $c] = $_POST[$c];
    } else {
        $params[':' . $c] = $_SESSION['login'];
    }
    $arr[$c] = $c . ' = :' . $c;
}

if ($isUpdate) {
    $set = implode(', ', $arr);
    $params[':id'] = $id;
    $query = "UPDATE " . $table . " SET " . $set . " WHERE id = :id";
} else {
    $keys = implode(', ', $columns);
    $vals = ':' . implode(', :', $columns);
    $query = "INSERT INTO " . $table . " (" . $keys . ") VALUES (" . $vals . ")";
}

foreach ($params as $key => $value) {
    if ($value === '' || $value === null) {
        $params_tmp[$key] = null; // Convert empty strings to null
    } else {
        $params_tmp[$key] = (string) $value; // Ensure all values are strings
    }
}
$params = $params_tmp;

$stmt = $db->prepare($query);
$success = $stmt->execute($params);

// Setting header to JSON for AJAX response
header('Content-Type: application/json');
echo json_encode(['success' => $success, 'user' => $_SESSION['login']]);