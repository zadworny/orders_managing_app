<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require_once '../database/connect.php';
require_once 'user.php';

$db = (new Database())->getConnection();
$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    if (!empty($email)) {

        $email = $_POST['email'];
        $foundUser = $user->findUserByEmail($email);

        if ($foundUser) {
            $token = bin2hex(random_bytes(50));
            if ($user->updateUserToken($email, $token)) {
                if ($user->sendPasswordResetLink($email, $token)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Błąd aplikacji: ' . $mail->ErrorInfo]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Ups, błąd zapisu w bazie danych']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Błędny email']);
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Wpisz swój email']);
    }
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'error' => 'Błąd wysyłania']);
}
?>
