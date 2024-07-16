<?php
include 'Orders.php';

$id = $_POST['id'];
$method = $_POST['method'];
$type = $_POST['type'];

$orders = new Orders();
$success = $orders->updateCollect($id, $method, $type);

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>
