<?php

header('Content-Type: application/json');
require_once __DIR__ . '/lead_storage.php';

// ====== ТУТ EMAIL ======
$to = "dev1@betandyou.com";

// ====== ДАНІ ======
$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$country   = trim($_POST['country'] ?? '');
$messanger = trim($_POST['messanger'] ?? '');

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

    // Ignore malformed client-provided lead_id and safely fall back to visitor_id lookup.
    if ($lead_id !== '' && !partial_leads_is_valid_id($lead_id)) {
        $lead_id = '';
    }

    if (!$lead_id && $visitor_id) {
        try {
            $pdo = partial_leads_get_pdo();

            if ($pdo && partial_leads_init_db($pdo)) {
                $latestLead = partial_leads_find_latest_by_visitor_id($pdo, $visitor_id);

                if ($latestLead) {
                    $lead_id = $latestLead['id'];
                }
            }
        } catch (Exception $e) {
            // Log the error but do not break the existing successful form submit
            error_log("SQLite error finding lead by visitor_id: " . $e->getMessage());
        }
    }

    if ($lead_id) {
        try {
            // ========================= TELEGRAM SUBMIT MERGE START =========================
            // Save the final Telegram value together with completion.
            // This closes the race:
            // user types Telegram -> immediately clicks Submit -> 1500ms partial debounce
            // has not fired yet. Without this merge Sheets could keep an older/empty value.
            $saveResult = partial_leads_save([
                'lead_id' => $lead_id,
                'visitor_id' => $visitor_id,
                'messanger' => $messanger,
                'status' => 'completed',
            ]);

            if (!$saveResult['success']) {
                error_log(
                    'SQLite error saving final Telegram value: ' .
                    ($saveResult['reason'] ?? 'unknown')
                );

                // Preserve the existing business rule: successful email submit must not
                // be broken by partial-lead storage problems.
                partial_leads_mark_completed($lead_id);
            }
            // ========================== TELEGRAM SUBMIT MERGE END ==========================
        } catch (Exception $e) {
            // Keep successful email delivery independent from partial-lead storage.
            error_log("SQLite error completing lead: " . $e->getMessage());
        }
    }
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}