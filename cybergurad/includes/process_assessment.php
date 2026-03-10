<?php
require 'init.php';
require 'db_connect.php';

// Load config
$cfg = [];
if (file_exists(__DIR__ . '/config.php')) {
    $cfg = include __DIR__ . '/config.php';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF check
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        header('Location: assessment.php?error=csrf');
        exit();
    }

    // Sanitize inputs
    $deviceName = htmlspecialchars(trim($_POST['device_name'] ?? ''));
    $manufacturer = htmlspecialchars(trim($_POST['manufacturer'] ?? ''));
    $model = htmlspecialchars(trim($_POST['model'] ?? ''));
    $firmware = htmlspecialchars(trim($_POST['firmware_version'] ?? ''));

    // Check empty fields
    if (empty($deviceName) || empty($manufacturer)) {
        header("Location: assessment.php?error=empty");
        exit();
    }

    // Insert device
    $stmt = $conn->prepare("INSERT INTO devices (device_name, manufacturer, model, firmware_version) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $deviceName, $manufacturer, $model, $firmware);

    if ($stmt->execute()) {

        $deviceId = $conn->insert_id;
        $stmt->close();

        // Load API key
        $apiKey = $cfg['google_api_key'] ?? getenv('CYBERGUARD_GOOGLE_API_KEY') ?: '';
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        $prompt = "Act as a Senior IoT Security Expert. Provide a DEEP and DETAILED security analysis for this device:
        Device: $deviceName, Manufacturer: $manufacturer, Model: $model, Firmware: $firmware.

        Your response MUST be a valid JSON object with these EXACT keys:
        1. 'status': Choose ONLY ('Safe', 'Medium', 'High Risk').
        2. 'summary': Provide a LONG technical overview.
        3. 'recommendations': Provide at least 5 HTML <li> items.
        4. 'proper_usage': Provide at least 4 HTML <li> items.

        IMPORTANT: Return ONLY raw JSON.";

        $data = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]]
        ];

        // Default fallback
        $status = 'Medium';
        $summary = 'Detailed analysis pending.';
        $recommendations = '<li>Isolate device on separate network.</li>';
        $proper_usage = '<li>Change default credentials.</li>';

        if (!empty($apiKey)) {

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                $summary = "API request failed: " . $curlErr;
            } else {

                $result = json_decode($response, true);

                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {

                    $cleanJson = trim($result['candidates'][0]['content']['parts'][0]['text']);
                    $cleanJson = str_replace(['```json', '```'], '', $cleanJson);

                    $aiResponse = json_decode($cleanJson, true);

                    if (json_last_error() === JSON_ERROR_NONE && $aiResponse) {
                        $status = $aiResponse['status'] ?? 'Medium';
                        $summary = $aiResponse['summary'] ?? '';
                        $recommendations = $aiResponse['recommendations'] ?? '';
                        $proper_usage = $aiResponse['proper_usage'] ?? '';
                    }
                }
            }
        }

        // Insert assessment
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        if ($userId) {
            $stmt = $conn->prepare(
                'INSERT INTO assessments (device_id, status, summary, recommendations, proper_usage, user_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('issssi', $deviceId, $status, $summary, $recommendations, $proper_usage, $userId);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO assessments (device_id, status, summary, recommendations, proper_usage)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('issss', $deviceId, $status, $summary, $recommendations, $proper_usage);
        }

        if ($stmt->execute()) {
            $insertedId = $stmt->insert_id;
            $stmt->close();

            header("Location: result.php?id=" . $insertedId);
            session_write_close();
            if (ob_get_level()) ob_end_flush();
            exit();
        }
    }

    $conn->close();
    session_write_close();
    if (ob_get_level()) ob_end_flush();
    exit();

} else {
    header("Location: index.php");
    session_write_close();
    if (ob_get_level()) ob_end_flush();
    exit();
}
?>