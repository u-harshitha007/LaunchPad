<?php
/**
 * Database connection configuration
 * Update credentials to match your local MySQL setup
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'launchpad');

/**
 * Returns a shared MySQLi connection instance
 */
function getDB(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}
