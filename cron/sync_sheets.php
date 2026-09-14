<?php

// Run only from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lead_storage.php';

if (!GOOGLE_SHEETS_ENABLED || empty(GOOGLE_SHEETS_WEBHOOK_URL)) {
    exit('Google Sheets sync disabled');
}

if (!function_exists('curl_init')) {
    exit("cURL extension is not available\n");
}

$pdo = partial_leads_get_pdo();

if (!$pdo) {
    exit('Failed to connect to database');
}

if (!partial_leads_init_db($pdo)) {
    exit("Failed to initialize database\n");
}

$stmt = $pdo->prepare('SELECT * FROM leads WHERE sheets_synced = 0 ORDER BY updated_at ASC LIMIT 50');
$stmt->execute();
$leads = $stmt->fetchAll();

$processed = count($leads);
$success = 0;
$failed = 0;

foreach ($leads as $lead) {
    $payload = json_encode([
        'lead_id' => $lead['id'],
        'status' => $lead['status'],
        'name' => $lead['name'],
        'phone' => $lead['phone'],
        'email' => $lead['email'],
        'country' => $lead['country'],
        'messanger' => $lead['messanger'],
        'created_at' => $lead['created_at'],
        'updated_at' => $lead['updated_at'],
        'completed_at' => $lead['completed_at'],
    ]);

    $ch = curl_init(GOOGLE_SHEETS_WEBHOOK_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        error_log("sync_sheets: curl error lead {$lead['id']}: $curlError");
        $failed++;
        continue;
    }

    $decoded = json_decode($response, true);

    if ($httpCode === 200 && isset($decoded['success']) && $decoded['success'] === true) {
        $updateStmt = $pdo->prepare(
            'UPDATE leads
     SET sheets_synced = 1,
         sheets_synced_at = CURRENT_TIMESTAMP
     WHERE id = ?
       AND updated_at = ?
       AND status = ?'
        );

        $updateStmt->execute([
            $lead['id'],
            $lead['updated_at'],
            $lead['status'],
        ]);

        if ($updateStmt->rowCount() === 1) {
            $success++;
        } else {
            // Lead змінився, поки ми синхронізували стару версію.
            // Не позначаємо нову версію як synced.
            // Наступний cron відправить її повторно.
            error_log(
                "sync_sheets: lead {$lead['id']} changed during sync, retry required"
            );
        }
    } else {
        error_log("sync_sheets: failed lead {$lead['id']}: HTTP $httpCode body: $response");
        $failed++;
    }

}

echo "Processed: $processed\n";
echo "Success: $success\n";
echo "Failed: $failed\n";
