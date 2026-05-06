<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'ADMIN_KEY'])->notEmpty();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

return true;
