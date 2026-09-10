<?php

header('Content-Type: application/json');
require_once __DIR__ . '/lead_storage.php';

// 1.Приймаємо тільки POST — решта методів повертає 405
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'reason' => 'invalid_method']);
    exit;
}

try {
    $data = $_POST;
    $data['visitor_id'] = trim($_POST['visitor_id'] ?? '');

    // Збагачуємо дані серверними значеннями якщо фронт їх не передав.
    // Це дозволяє відстежувати звідки прийшов лід навіть без JS-трекінгу.
    $data['source'] = $data['source'] ?? 'landing';
    $data['locale'] = $data['locale'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en');
    // Fix page_url construction to use normal ://
    $data['page_url'] = $data['page_url'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''));
    $data['referrer'] = $data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');

    // UTM-мітки читаємо з GET-параметрів як fallback.
    // Фронт може передати їх явно якщо зчитав з URL заздалегідь.
    $data['utm_source'] = $data['utm_source'] ?? ($_GET['utm_source'] ?? '');
    $data['utm_campaign'] = $data['utm_campaign'] ?? ($_GET['utm_campaign'] ?? '');
    $data['utm_medium'] = $data['utm_medium'] ?? ($_GET['utm_medium'] ?? '');

    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $status = $data['status'] ?? 'partial';

    // ========================= TELEGRAM PARTIAL VALIDATION START =========================
    // Partial lead: Telegram may be empty.
    // If the user has already typed it, reject invalid format / obvious junk.
    $telegramValidation = partial_leads_validate_telegram_username($data['messanger'] ?? '', false);

    if (!$telegramValidation['valid']) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'reason' => $telegramValidation['reason'],
        ]);
        exit;
    }
    // ========================== TELEGRAM PARTIAL VALIDATION END ==========================

    // 5. Головна умова MVP: не зберігаємо якщо немає жодного контакту.
    // Виключення: status=completed (фінальний submit через send.php).
    $leadId = trim($data['lead_id'] ?? '');
    if (!$leadId && !$phone && !$email && $status !== 'completed') {
        echo json_encode(['success' => false, 'reason' => 'no_contact']);
        exit;
    }

    // Use partial_leads_save to handle create/update logic
    $result = partial_leads_save($data);

    if ($result['success']) {
        echo json_encode(['success' => true, 'lead_id' => $result['lead_id'], 'status' => $result['status']]);
    } else {
        $reason = $result['reason'] ?? 'server_error';
        $validationReasons = [
            'telegram_required',
            'telegram_invalid_format',
            'telegram_obvious_fake',
        ];

        http_response_code(in_array($reason, $validationReasons, true) ? 422 : 500);
        echo json_encode(['success' => false, 'reason' => $reason]);
    }
} catch (Throwable $e) {
    // 10. Ловимо будь-яку помилку щоб фронт завжди отримав валідний JSON
    // а не HTML-сторінку з помилкою PHP.
    error_log('save_lead.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'reason' => 'server_error']);
}

