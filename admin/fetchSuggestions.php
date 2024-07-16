<?php
// Change LIKE from "%$searchTerm%" to "$searchTerm%" in line 23 to search for records where the column starts with the searchTerm
require_once '../database/connect.php';

$database = new Database();
$db = $database->getConnection();

$field = $_POST['field'] ?? ''; // e.g. name
$searchTerm = $_POST['search'] ?? ''; // e.g. agro-mat
$count = $_POST['count'] ?? 30; // Default 30 if not specified

$columns = [$field => $field];
$column = $columns[$field] ?? '';

if (!$column) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

try {
    if (is_array($searchTerm) && count($searchTerm) > 1) {
        $placeholders = implode(',', array_fill(0, count($searchTerm), '?'));
        $query = "SELECT id, clientid, firstname, lastname, phone, email, phone_additional, name, name_custom, nip, street, house_no, flat_no, postcode, town, country, import_db FROM clients WHERE id IN ($placeholders) AND deleted IS NULL";
        $stmt = $db->prepare($query);
        $stmt->execute($searchTerm);
    } else {
        $query = "SELECT id, clientid, firstname, lastname, phone, email, phone_additional, name, name_custom, nip, street, house_no, flat_no, postcode, town, country, import_db FROM clients WHERE $column LIKE :searchTerm AND deleted IS NULL LIMIT :count";
        $stmt = $db->prepare($query);
        $stmt->execute(['searchTerm' => "%$searchTerm%", 'count' => $count]);
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add latest orderid and receiptid to each result
    foreach ($results as &$result) {
        $clientid = $result['clientid'];
    
        // Get the count of orders
        //$query = "SELECT COUNT(*) AS count FROM orders WHERE clientid = :clientid AND (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)";
        $query = "SELECT COUNT(*) AS count FROM orders WHERE clientid = :clientid AND history LIKE '%Zlecona naprawa%' AND status = 'Zakończono' AND (deleted = '' OR deleted IS NULL)"; // AND repair_accepted = 'TAK'
        $stmt = $db->prepare($query);
        $stmt->execute([':clientid' => $clientid]);
        $result_tmp = $stmt->fetch(PDO::FETCH_ASSOC);
        $client_orders = $result_tmp['count'];
    
        // Get the latest orderid
        $query = "SELECT MAX(orderid) AS last_orderid FROM orders WHERE clientid = :clientid AND (deleted = '' OR deleted IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->execute([':clientid' => $clientid]);
        $result_tmp = $stmt->fetch(PDO::FETCH_ASSOC);
        $order = $result_tmp['last_orderid'];
        if ($order !== null) {
            $exp = substr($order, -3);
            $incrementedNumber = intval($exp) + 1;
            $orderid = str_pad($incrementedNumber, 3, "0", STR_PAD_LEFT);
        } else {
            $orderid = '001';
        }
    
        // Add additional data to the result
        $result['client_orders'] = $client_orders;
        $result['orderid'] = $clientid . '/' . $orderid;
        $result['receiptid'] = $clientid . '/' . $orderid . 'A';
    }    

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error fetching suggestions: ' . $e->getMessage()]);
}
