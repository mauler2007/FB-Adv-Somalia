<?php

header('Content-Type: application/json');

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
    echo json_encode(["success" => false]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false]);
    exit;
}

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
    require_once 'lead_storage.php';
    $lead_id = $_POST['lead_id'] ?? null;
    if ($lead_id) {
        try {
            partial_leads_mark_completed($lead_id);
        } catch (Exception $e) {
            // Log the error but do not break the existing successful form submit
            error_log("SQLite error marking lead as completed: " . $e->getMessage());
        }
    }
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}