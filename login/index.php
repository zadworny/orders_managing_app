<?php
require_once '../database/connect.php';
require_once 'user.php';

$db = (new Database())->getConnection();
$user = new User($db);

// Show password reset form
$hide = $css = $error = '';
$show = 'style="display:none"';
$token = $_GET['token'] ?? '';
$passc = $_GET['pass'] ?? '';
if ($token) {
    $hide = 'style="display:none"';
    $show = 'style="display:block"';
    $auth = $user->getUserByToken($token);
    if (!$auth) {
        $css = 'style="background-color: #f8d7da; border: 1px solid #f5c6cb; display: block"';
        $error = 'Resetowanie hasła wygasło';
    }
} elseif ($passc == 'changed') {
    $css = 'style="background-color: #d7f8da; border: 1px solid #c6f5cb; display: block"';
    $error = 'Zaloguj się nowym hasłem';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../style/logins.css?v=1.0.0">
</head>
<body>
    <div class="login-container">
        <img src="../images/Hydro-Dyna.png" alt="Logo">
        <div id="notification" <?php echo $css; ?>><?php echo $error; ?></div>
        <form id="loginForm" action="auth.php" method="post" <?php echo $hide; ?>>
            <input type="text" name="username" placeholder="Login" required>
            <input type="password" name="password" placeholder="Hasło" required>

            <!-- CAPTCHA -->
            <input id="captchaInput" type="hidden" name="captcha" placeholder="Prepisz kod z obrazka" value="notactivated" required>
            <div id="captchaSection">
                <img src="captcha.php" alt="CAPTCHA" id="captchaImage">
                <button id="captchaRefresh" type="button" onclick="refreshCaptcha()">Odśwież obrazek</button>
            </div>

            <button type="submit">Zaloguj</button>
            <label class="checkbox-inline">
                <input type="checkbox" name="remember" checked> Zaloguj na 30 dni
            </label><br>
            <a id="forgotPasswordLink">Zapomniałem hasła</a>
        </form>
        <form id="resetForm" action="email.php" method="post" <?php echo $hide; ?>>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Wyślij</button>
            <label class="checkbox-inline">
                Otrzymasz link do resetowania hasła
            </label><br>
            <a class="backtoLoginLink">Wróć do logowania</a>
        </form>
        <form id="newpassForm" action="reset.php" method="post" <?php echo $show; ?>>
            <label class="checkbox-inline">
                Hasło musi mieć małą i wielką literę, cyfrę i znak specjalny. Wskaźnik musi być zielony
            </label>
            <div id="password-strength-indicator"></div>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="newPassword" id="newPassword" placeholder="Wpisz nowe hasło" required>
            <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Powtórz nowe hasło" required disabled>
            <button id="submit-btn" type="submit">Zatwierdź</button>
            <br>
            <a class="backtoLoginLink">Wróć do logowania</a>
        </form>
    </div>
    <script src="../scripts/jquery.min.js?v=1.0.0"></script>
    <script src="../scripts/logins.js?v=1.0.1"></script>
</body>
</html>
