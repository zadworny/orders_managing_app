<?php
session_start();
error_reporting(0);

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Check if the 'user_login' cookie exists and delete it
/*if (isset($_COOKIE['user_login'])) {
    unset($_COOKIE['user_login']);
    setcookie('user_login', '', time() - 3600, '/');
}*/

// UNSET ALL COOKIES
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
        setcookie($name, '', time() - 3600, '/', '.' . $_SERVER['HTTP_HOST']);
    
        if ($_SERVER['HTTP_HOST'] == 'localhost' || filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_IP)) {
            setcookie($name, '', time() - 3600, '/', 'localhost');
        }
    }
}

// Redirect to the login page
header("Location: ../login/index.php");
exit;
?>
