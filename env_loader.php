<?php
/**
 * Environment Loader
 * Loads environment variables from .env file using vlucas/phpdotenv
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Optional: Validate required environment variables
$dotenv->required([
    'DB_HOST',
    'DB_NAME', 
    'DB_USER',
    'APP_URL',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME'
])->notEmpty();

// Return success
return true;
?>
