<?php
require_once '../database/connect.php';
require_once '../functions/columns.php';

// Check if ID is provided
if (isset($_POST['id'])) {
    $database = new Database();
    $db = $database->getConnection();

    $table = $_POST['dbTable'];
    $id = $_POST['id']; // Row id

    //echo json_encode(['record' => $table]); exit(); // Test

    /*$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename ORDER BY ORDINAL_POSITION";
    $stmt = $db->prepare($query);
    $stmt->execute([':dbname' => 'jrqerflhfm_app', ':tablename' => $table]);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $columnsList = implode(', ', $columns);*/

    //$query = "SELECT " . $columnsList . " FROM " . $table . " WHERE id = :id LIMIT 1";
    if ($table == 'orders') {
        $columnsList = $columnsListSpecial;

        $query = "SELECT DISTINCT " . $columnsList . " FROM " . $table . " t1 JOIN clients t2 ON t1.clientid = t2.clientid WHERE t1.id = :id LIMIT 1";
    } else {
        $query = "SELECT * FROM " . $table . " WHERE id = :id LIMIT 1";
    }
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    /*if ($table == 'orders') {
        // Query to get the highest receiptid where orderid equals $record['orderid']
        $query = "SELECT MAX(receiptid) AS highest_receiptid FROM " . $table . " WHERE orderid = :orderid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':orderid', $record['orderid']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result && isset($result['highest_receiptid'])) {
            $record['highest_receiptid'] = $result['highest_receiptid'];
        } else {
            $record['highest_receiptid'] = null;
        }
    }*/

    // Setting header to JSON for AJAX response
    header('Content-Type: application/json');
    echo json_encode(['record' => $record]);
} else {
    echo json_encode(['error' => 'Missing ID']);
}
