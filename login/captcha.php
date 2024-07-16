<?php
session_start();
error_reporting(0);

$captcha_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
$_SESSION["captcha"] = $captcha_code;

$image = imagecreatetruecolor(120, 30);
if (!$image) {
    die('Error: Failed to create a true color image.');
}

// Allocate colors with error checking
$bg_color = @imagecolorallocate($image, 255, 255, 255);
$text_color = @imagecolorallocate($image, 0, 0, 0);
if ($bg_color === false || $text_color === false) {
    imagedestroy($image);
    die('Error: Failed to allocate a color.');
}

// Proceed with filling the background and adding text
imagefilledrectangle($image, 0, 0, 120, 30, $bg_color);

$font_path = './captcha.ttf'; // Ensure this path is correct
if (!file_exists($font_path)) {
    imagedestroy($image);
    die('Error: Font file not found.');
}

$font_size = 20;
$x = 10;
$y = 25;
$angle = 0;
imagettftext($image, $font_size, $angle, $x, $y, $text_color, $font_path, $captcha_code) or die('Error: imagettftext function failed.');

header('Content-type: image/jpeg');
imagejpeg($image);
imagedestroy($image);
?>
