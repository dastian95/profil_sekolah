<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-type: image/png');

// Generate random code
// Remove ambiguous characters (g, 9, q, 0, o, 1, l, i) to avoid confusion
$code = substr(str_shuffle("2345678abcdefhjkmnprstuvwxyz"), 0, 5);
$_SESSION['captcha'] = $code;

// Cek ketersediaan GD Library secara langsung
if (!function_exists('imagecreatetruecolor')) {
    die("Error: GD Library tidak terdeteksi aktif. Mohon Stop & Start Apache di Laragon.");
}

// Create image
$im = imagecreatetruecolor(150, 45);
$bg = imagecolorallocate($im, 240, 240, 240); // Light gray background
$fg = imagecolorallocate($im, 33, 37, 41);    // Dark text color
$line_color = imagecolorallocate($im, 200, 200, 200);

imagefill($im, 0, 0, $bg);

// Add some random lines for noise
for ($i = 0; $i < 5; $i++) {
    imageline($im, rand(0, 150), rand(0, 45), rand(0, 150), rand(0, 45), $line_color);
}

// Add the code to the image
imagestring($im, 5, 50, 14, $code, $fg);

imagepng($im);
imagedestroy($im);
?>