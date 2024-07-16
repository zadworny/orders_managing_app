<?php
include 'Orders.php';

$orders = new Orders();
$results = $orders->fetchOrders();

header('Content-Type: application/json');
echo json_encode($results);
?>
