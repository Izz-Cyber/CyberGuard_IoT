<?php
// Database credentials: prefer explicit config.php, then environment, then sensible defaults
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = include __DIR__ . '/config.php';
}

$dbHost = getenv('CYBERGURAD_DB_HOST') ?: ($config['db_host'] ?? 'localhost');
$dbUser = getenv('CYBERGURAD_DB_USER') ?: ($config['db_user'] ?? 'root');
$dbPass = getenv('CYBERGURAD_DB_PASS') ?: ($config['db_pass'] ?? '');
$requestedName = getenv('CYBERGURAD_DB') ?: ($config['db_name'] ?? 'cybergurad');

// Try to connect to the requested database
$conn = @new mysqli($dbHost, $dbUser, $dbPass, $requestedName);

if ($conn->connect_errno) {
    // 1049 = Unknown database
    if ($conn->connect_errno === 1049) {
        // Try connecting without a database to list available DBs (if credentials allow)
        $tmp = @new mysqli($dbHost, $dbUser, $dbPass);
        if (!$tmp->connect_errno) {
            $dbs = [];
            $res = $tmp->query('SHOW DATABASES');
            if ($res) {
                while ($row = $res->fetch_row()) { $dbs[] = $row[0]; }
                $res->free();
            }

            // Find closest match to requestedName
            $best = null; $bestScore = PHP_INT_MAX;
            foreach ($dbs as $d) {
                $score = levenshtein($requestedName, $d);
                if ($score < $bestScore) { $bestScore = $score; $best = $d; }
            }

            $suggestion = '';
            if ($best !== null && $bestScore <= 5 && $best !== $requestedName) {
                $suggestion = " Did you mean '{$best}'?";
            }

            // Attempt to create the database automatically if privileges allow
            $created = false;
            if ($tmp->query("CREATE DATABASE `" . $tmp->real_escape_string($requestedName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $created = true;
            }

            $tmp->close();

            if ($created) {
                // try reconnecting to the newly created DB
                $conn = @new mysqli($dbHost, $dbUser, $dbPass, $requestedName);
                if ($conn->connect_errno) {
                    die("Database '{$requestedName}' was created but connection failed: " . $conn->connect_error);
                }
            } else {
                die("Database '{$requestedName}' not found." . $suggestion . "\nCould not create database automatically. Please create it manually or set the CYBERGURAD_DB environment variable to the correct name.");
            }
        } else {
            // Can't connect to server at all
            die('Could not connect to MySQL server. Please verify host/credentials.');
        }
    } else {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// Set the character set to utf8mb4 for full Unicode support
$conn->set_charset('utf8mb4');

// Create tables if they don't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        avatar_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        role VARCHAR(32) NOT NULL DEFAULT 'user'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(255) NOT NULL,
        manufacturer VARCHAR(255) NOT NULL,
        model VARCHAR(255) NOT NULL,
        firmware_version VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        status VARCHAR(255) NOT NULL,
        summary TEXT,
        recommendations TEXT,
        proper_usage TEXT,
        user_id INT,
        assessment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Ensure legacy deployments without an assessment_date column keep working.
// If the `assessments` table exists but the `assessment_date` column is missing,
// add it with a safe default of the current timestamp so selects relying on it won't fail.
try {
    $tbl = $conn->query("SHOW TABLES LIKE 'assessments'");
    if ($tbl && $tbl->num_rows > 0) {
        $col = $conn->query("SHOW COLUMNS FROM assessments LIKE 'assessment_date'");
        if ($col && $col->num_rows === 0) {
            // best-effort alter; if it fails due to privileges, we silently continue
            @$conn->query("ALTER TABLE assessments ADD COLUMN assessment_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
    }
} catch (Exception $e) {
    // ignore — we don't want DB column differences to block the app startup
}
?>