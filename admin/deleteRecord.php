<?php
require_once '../database/connect.php';

// Check if ID is provided
if (isset($_POST['id'])) {
    $database = new Database();
    $db = $database->getConnection();

    $table = $_POST['dbTable'];
    $id = $_POST['id']; // Row id
    $type = $_POST['type'];

    if ($type == 'destroy') {
        $query = "DELETE FROM " . $table . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
    } else if ($type == 'delete') {
        $query = "UPDATE " . $table . " SET deleted = :one WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':one' => 'TAK', ':id' => $id]);
        //$stmt->execute();
        $record = $stmt->fetch();
    } else if ($type == 'archive') {
        $query = "UPDATE " . $table . " SET archived = :one, status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':one' => 'TAK', ':status' => 'Zakończono', ':id' => $id]);
        //$stmt->execute();
        $record = $stmt->fetch();
    } else if ($type == 'restore') {
        $query = "UPDATE " . $table . " SET deleted = :null WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':null' => null, ':id' => $id]);
        //$stmt->execute();
        $record = $stmt->fetch();
    } else {

        // Photo
        $exp1 = explode('/', $type[0]);
        $file = end($exp1);

        $exp2 = explode('/', $type[1]);
        $list = explode(',', $exp2[1]);
        $key = array_search($file, $list);
        if ($key !== false) {
            unset($list[$key]);
        }
        $final = implode(',', $list);

        // DB
        $query = "UPDATE " . $table . " SET attachments = :list WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':list' => $final, ':id' => $id]);
        $stmt->execute();
        $record = $stmt->fetch();

        // DIR
        if (file_exists('../' . $type[0])) {
            unlink('../' . $type[0]);
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => 'Row deleted']);
} else {
    // Setting header to JSON for AJAX response
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing ID']);
}
