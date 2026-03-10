<?php
$config = include __DIR__ . '/config.php';

$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
if ($mysqli->connect_errno) {
    http_response_code(500);
    die('DB_SERVER_CONNECT_ERROR: ' . $mysqli->connect_error);
}

// Ensure database exists (attempt to create if permitted)
$dbName = $config['db_name'];
try {
    if (!$mysqli->select_db($dbName)) {
        $create_sql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($dbName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if (!$mysqli->query($create_sql)) {
            http_response_code(500);
            die('DB_SELECT_ERROR: ' . $mysqli->error);
        }
        if (!$mysqli->select_db($dbName)) {
            http_response_code(500);
            die('DB_SELECT_ERROR: ' . $mysqli->error);
        }
    }
} catch (mysqli_sql_exception $e) {
    // Some environments enable exceptions for mysqli; try a fallback without exceptions
    mysqli_report(MYSQLI_REPORT_OFF);
    $tmp = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
    if ($tmp->connect_errno) {
        http_response_code(500);
        die('DB_SERVER_CONNECT_ERROR: ' . $tmp->connect_error);
    }
    $create_sql = "CREATE DATABASE IF NOT EXISTS `" . $tmp->real_escape_string($dbName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if (!$tmp->query($create_sql)) {
        http_response_code(500);
        die('DB_SELECT_ERROR: ' . $tmp->error);
    }
    $tmp->close();
    // restore exception reporting and attempt to select the database again
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if (!$mysqli->select_db($dbName)) {
        http_response_code(500);
        die('DB_SELECT_ERROR: ' . $mysqli->error);
    }
}

$mysqli->set_charset('utf8mb4');

// Helper: get mysqli instance
function db_connect()
{
    global $mysqli;
    return $mysqli;
}
