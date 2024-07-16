<?php
include 'Orders.php';

$id = $_POST['id'];
$status = $_POST['status'];
$user = $_SESSION['login']; // better solution - previously passed in JS function
$date_quote = $_POST['date_quote'];
$date_start = $_POST['date_start'];
$date_count = $_POST['date_count'];
$date_end = $_POST['date_end'];
$urgent = $_POST['urgent'];
$mark = $_POST['mark'];

$orders = new Orders();
$success = $orders->updateOrder($id, $status, $user, $date_quote, $date_start, $date_count, $date_end, $urgent, $mark);

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>
