<?php

header('Content-Type: application/json');

require_once __DIR__ . '/telegram_input.php';
require_once __DIR__ . '/lead_storage.php';
require_once __DIR__ . '/telegram_checker.php';

// ====== ТУТ EMAIL ======
$to = "dev1@betandyou.com";

// ====== ДАНІ ======
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = trim($_POST['country'] ?? '');

$messanger = normalizeTelegramInput($_POST['messanger'] ?? '');

if ($messanger === null) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'reason' => 'telegram_invalid_format',
    ]);
    exit;
}

// ====== ВАЛІДАЦІЯ ======
if (!$name || !$phone || !$email) {
    http_response_code(422);
    echo json_encode(["success" => false, "reason" => "required_fields"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["success" => false, "reason" => "invalid_email"]);
    exit;
}


// ========================= TELEGRAM FINAL VALIDATION START =========================
// Final submit: Telegram is required and must pass both format and junk checks.
$telegramValidation = partial_leads_validate_telegram_username($messanger, true);

if (!$telegramValidation['valid']) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "reason" => $telegramValidation['reason'],
    ]);
    exit;
}
// ========================== TELEGRAM FINAL VALIDATION END ==========================


// ========================= TELEGRAM REMOTE CHECK START =========================
if (TELEGRAM_CHECKER_ENABLED) {
    $telegramCheck = telegram_checker_check($messanger);

    if ($telegramCheck['result'] === 'not_taken') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'reason' => 'telegram_not_found',
        ]);

        exit;
    }

    if ($telegramCheck['result'] === 'unknown') {
        // Technical checker problems must not lose the lead.
        // Log for diagnostics and continue submit (fail-open).
        error_log(
            'Telegram checker unknown: ' .
            json_encode($telegramCheck, JSON_UNESCAPED_UNICODE)
        );
    }

if (
    defined('TELEGRAM_CHECKER_DEBUG_LOG') &&
    TELEGRAM_CHECKER_DEBUG_LOG
) {
    file_put_contents(
        __DIR__ . '/telegram-checker-debug.log',
        '[' . date('Y-m-d H:i:s') . '] ' .
        $messanger . ' => ' .
        json_encode($telegramCheck, JSON_UNESCAPED_UNICODE) .
        PHP_EOL,
        FILE_APPEND
    );
}
}
// ========================== TELEGRAM REMOTE CHECK END ==========================


// ====== ТЕКСТ ЛИСТА ======
$subject = "New request from Somalia Meta Advertise Landing Page  URL: https://meta-adv-so.bettest.site/";

$body = "
New contact request:

Name: $name
Email: $email
Phone: $phone
Country: $country
Messanger: $messanger
";

// ====== HEADERS ======
$headers = "From: no-reply@yourdomain.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ====== ВІДПРАВКА ======
if (mail($to, $subject, $body, $headers)) {
    $lead_id = trim($_POST['lead_id'] ?? '');
    $visitor_id = trim($_POST['visitor_id'] ?? '');

    // Ignore malformed client-provided lead_id.
    // partial_leads_save() will fall back to visitor_id or create a new lead.
    if ($lead_id !== '' && !partial_leads_is_valid_id($lead_id)) {
        $lead_id = '';
    }

    try {
        // Final submit is the authoritative final snapshot.
        // It must persist all final contact values even if partial save
        // did not run yet or was still waiting for debounce.
        $saveResult = partial_leads_save([
            'lead_id' => $lead_id,
            'visitor_id' => $visitor_id,

            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'country' => $country,
            'messanger' => $messanger,

            'status' => 'completed',
        ]);

        if (!$saveResult['success']) {
            error_log(
                'SQLite error saving final lead snapshot: ' .
                ($saveResult['reason'] ?? 'unknown')
            );

            // Preserve previous fallback for an already existing lead.
            if ($lead_id) {
                partial_leads_mark_completed($lead_id);
            }
        }
    } catch (Throwable $e) {
        // Email has already been sent successfully.
        // Storage problems must not turn successful mail delivery
        // into a failed form submit.
        error_log(
            'SQLite error saving final lead snapshot: ' .
            $e->getMessage()
        );
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

