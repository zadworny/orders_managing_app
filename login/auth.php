<?php
require_once '../database/connect.php';
require_once 'user.php';
// echo password_hash('infoserwis', PASSWORD_DEFAULT); // Password test

$db = (new Database())->getConnection();
$user = new User($db);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['captcha'])) {

    if ((!$_POST['captcha'] || $_POST['captcha'] == 'notactivated') || 
        ($_POST['captcha'] != 'notactivated' && $_POST['captcha'] == $_SESSION['captcha'])) {

        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
    
        // Attempt to authenticate the user
        $auth = $user->authenticate($username, $password);
    
        if ($auth) {

            // User authentication succeeded
            $_SESSION['login'] = $auth['login'];
            $_SESSION['level'] = $auth['level'];
    
            if (!empty($_POST['remember'])) {
                setcookie("user_login", $username, time() + (86400 * 30), "/");
            }
            echo json_encode(['success' => true, 'message' => 'Zalogowano...']);
            
        } else {
            // User authentication failed
            $_SESSION['login_error'] = 'Invalid username or password.';
            echo json_encode(['success' => false, 'message' => 'Błędny login lub hasło']);
        }

    } else {
        $_SESSION['login_error'] = 'Invalid captcha.';
        echo json_encode(['success' => false, 'message' => 'Błędny kod captcha']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Ups, błąd połączenia']);
}
?>
