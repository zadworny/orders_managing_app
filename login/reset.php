<?php
require_once '../database/connect.php';
require_once 'user.php';

$db = (new Database())->getConnection();

$user = new User($db);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token']) && isset($_POST['newPassword']) && isset($_POST['confirmPassword'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Hasła nie są takie same']);
    } elseif ($user->resetPassword($token, $newPassword)) {
        echo json_encode(['success' => true, 'message' => 'Hasło zmienione, zaloguj się...']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ups, błąd zapisu w bazie danych']);
    }
}
?>
