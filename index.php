<?php
require_once 'database/connect.php';
require_once 'login/user.php';

$db = (new Database())->getConnection();
$user = new User($db);

// Function to check if the 'user_login' cookie is valid and update session
function updateSessionFromCookie($cookieName, $user) {
    if (isset($_COOKIE[$cookieName])) {
        $username = $_COOKIE[$cookieName];
        $level = $user->userExists($username);
        if ($level !== false) {
            $_SESSION['login'] = $username;
            $_SESSION['level'] = $level;
            return true;
        }
    }
    return false;
}

if (updateSessionFromCookie('user_login', $user)) {
    // Session updated from cookie, proceed with user validation
    // Holders used in admin.js and orders.js
    echo '<div id="login_holder">' . trim($_SESSION['login']) . '</div>';
    echo '<div id="level_holder">' . trim($_SESSION['level']) . '</div>';
} elseif (!isset($_SESSION['login'])) {
    header('Location: login');
    exit;
}

// TEMP
/*echo 'TEMP TEST';
echo '<br>session: ' . $_SESSION['login'];
echo '<br>session: ' . $_SESSION['level'];
echo '<br>cookie: ' . $_COOKIE['user_login'];
echo '<br>';*/

switch ($_SESSION['level']) {
    case 'admin':
        include('admin/index.php');
        break;
    case 'editor':
        include('admin/index.php');
        break;
    case 'user':
        include('orders.php');
        break;
    case 'view':
        include('orders.php');
        break;
    default:
        echo "<br><br>Nie masz dostępu do tej strony...";
        // Content for any other role or if the role is not defined
        break;
}
?>