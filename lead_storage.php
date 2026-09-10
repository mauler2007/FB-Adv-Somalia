<?php

require_once __DIR__ . '/config.php';

function partial_leads_get_pdo(): ?PDO
{
    if (!extension_loaded('pdo_sqlite')) {
        error_log('PDO SQLite extension is not loaded.');
        return null;
    }

    $dataDir = dirname(DB_PATH);

    if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true)) {
        error_log('Failed to create data directory: ' . $dataDir);
        return null;
    }

    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        return null;
    }
}

function partial_leads_init_db(PDO $pdo): bool
{
    $sql = "
        CREATE TABLE IF NOT EXISTS leads (
            id               TEXT PRIMARY KEY,
            name             TEXT,
            email            TEXT,
            phone            TEXT,
            country          TEXT,
            messanger        TEXT,
            status           TEXT DEFAULT 'partial',
            tg_sent          INTEGER DEFAULT 0,
            tg_sent_at       TEXT,
            sheets_synced    INTEGER DEFAULT 0,
            sheets_synced_at TEXT,
            source           TEXT,
            locale           TEXT,
            page_url         TEXT,
            referrer         TEXT,
            utm_source       TEXT,
            utm_campaign     TEXT,
            utm_medium       TEXT,
            created_at       TEXT NOT NULL,
            updated_at       TEXT NOT NULL,
            completed_at     TEXT,
            visitor_id       TEXT
        );
    ";

    try {
        $pdo->exec($sql);
        partial_leads_ensure_visitor_id_column($pdo);
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leads_visitor_id ON leads (visitor_id)");
        return true;
    } catch (PDOException $e) {
        error_log('DB init error: ' . $e->getMessage());
        return false;
    }
}

function partial_leads_ensure_visitor_id_column(PDO $pdo): void
{
    $stmt = $pdo->query("PRAGMA table_info(leads)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'visitor_id') {
            return;
        }
    }

    $pdo->exec("ALTER TABLE leads ADD COLUMN visitor_id TEXT");
}

function partial_leads_generate_id(): string
{
    try {
        return 'lead_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    } catch (Exception $e) {
        return 'lead_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8);
    }
}

function partial_leads_is_valid_id(string $id): bool
{
    return preg_match('/^lead_\d{14}_[0-9a-f]{8}$/', $id) === 1;
}

function partial_leads_normalize_status(?string $status): string
{
    $allowed = ['partial', 'completed', 'spam'];
    $status = $status ?: 'partial';

    return in_array($status, $allowed, true) ? $status : 'partial';
}


// ========================= TELEGRAM VALIDATION START =========================
// Step 1 only:
// - validate the @username format;
// - reject only high-confidence junk / obvious placeholders;
// - do not check account existence here (that is Step 2).
//
// IMPORTANT: keep the scoring rules aligned with js/main.min.js.
// Frontend validation is UX; this backend validation is the safety layer.

function partial_leads_check_telegram_junk(string $rawValue): array
{
    $username = ltrim(trim($rawValue), '@');
    $usernameLower = strtolower($username);
    $threshold = 3;

    // Exact placeholders that we intentionally hard-block.
    $exactJunk = [
        'testtest',
        'test123',
        'test1234',
        'test12345',
        'asdf123',
        'zxcvbn123',
    ];

    if (in_array($usernameLower, $exactJunk, true)) {
        return [
            'is_junk' => true,
            'score' => $threshold,
            'matched' => ['exact_placeholder'],
        ];
    }

    $patterns = [
        // High-confidence signals: one match is enough.
        ['name' => 'keyboard_seq_en', 'regex' => '/qwerty|asdfgh|zxcvbn|wertyu|sdfghj|xcvbnm/i', 'weight' => 3],

        // Entire username is one repeated character: aaaaa, bbbbb, etc.
        ['name' => 'full_repeated_char', 'regex' => '/^(.)\1{4,}$/', 'weight' => 3],

        // Entire username is a short block repeated 3+ times: ababab, abcabcabc.
        ['name' => 'full_repeated_block', 'regex' => '/^(.{2,4})\1{2,}$/', 'weight' => 3],

        // Weak/medium signals: never reject on one signal alone.
        ['name' => 'digit_seq_asc', 'regex' => '/12345|23456|34567|45678|56789|67890/', 'weight' => 1],
        ['name' => 'digit_seq_desc', 'regex' => '/98765|87654|76543|65432|54321/', 'weight' => 1],
        ['name' => 'long_digit_run', 'regex' => '/\d{6,}/', 'weight' => 1],
        ['name' => 'consonant_run', 'regex' => '/[bcdfghjklmnpqrstvwxyz]{6,}/i', 'weight' => 1],
    ];

    $weakWords = [
        'test', 'admin', 'user', 'telegram', 'null', 'undefined',
        'anon', 'noname', 'nobody', 'temp', 'trash', 'fake',
        'sample', 'account', 'profile', 'deleted',
    ];

    foreach ($weakWords as $word) {
        $patterns[] = [
            'name' => 'filler_' . $word,
            // Filler only as a token/prefix, not an arbitrary substring.
            'regex' => '/(?:^|_)' . preg_quote($word, '/') . '(?:_|\d*$)/i',
            'weight' => 1,
        ];
    }

    $score = 0;
    $matched = [];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern['regex'], $username) === 1) {
            $score += $pattern['weight'];
            $matched[] = $pattern['name'];
        }
    }

    return [
        'is_junk' => $score >= $threshold,
        'score' => $score,
        'matched' => $matched,
    ];
}

function partial_leads_validate_telegram_username(?string $value, bool $required = false): array
{
    $value = trim((string) $value);

    if ($value === '') {
        return $required
            ? ['valid' => false, 'reason' => 'telegram_required']
            : ['valid' => true, 'reason' => null];
    }

    // The form value must include @.
    // Username: 5..32 chars, starts with a Latin letter, contains only
    // Latin letters/digits/underscores and does not end with underscore.
    if (preg_match('/^@[A-Za-z][A-Za-z0-9_]{3,30}[A-Za-z0-9]$/D', $value) !== 1) {
        return ['valid' => false, 'reason' => 'telegram_invalid_format'];
    }

    $junkCheck = partial_leads_check_telegram_junk($value);

    if ($junkCheck['is_junk']) {
        return [
            'valid' => false,
            'reason' => 'telegram_obvious_fake',
            'score' => $junkCheck['score'],
            'matched' => $junkCheck['matched'],
        ];
    }

    return ['valid' => true, 'reason' => null];
}

// ========================== TELEGRAM VALIDATION END ==========================

function partial_leads_find(PDO $pdo, string $leadId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $leadId]);

    $lead = $stmt->fetch();

    return $lead ?: null;
}

function partial_leads_find_latest_by_visitor_id(PDO $pdo, string $visitorId): ?array
{
    $visitorId = trim($visitorId);

    if ($visitorId === '') {
        return null;
    }

    $cutoff = date('Y-m-d H:i:s', strtotime('-' . PARTIAL_LEAD_TTL_HOURS . ' hours'));

    $stmt = $pdo->prepare("
        SELECT *
        FROM leads
        WHERE visitor_id = :visitor_id
          AND created_at >= :cutoff
        ORDER BY updated_at DESC
        LIMIT 1");
    $stmt->execute([':visitor_id' => $visitorId, ':cutoff' => $cutoff]);

    $lead = $stmt->fetch();

    return $lead ?: null;
}

function partial_leads_save(array $data): array
{
    // Storage-layer safety: even a direct POST that bypasses save_lead.php
    // cannot persist a filled invalid/fake Telegram username.
    $telegramValidation = partial_leads_validate_telegram_username($data['messanger'] ?? '', false);

    if (!$telegramValidation['valid']) {
        return [
            'success' => false,
            'reason' => $telegramValidation['reason'],
        ];
    }

    $pdo = partial_leads_get_pdo();

    if (!$pdo || !partial_leads_init_db($pdo)) {
        return ['success' => false, 'reason' => 'server_error'];
    }

    $now = date('Y-m-d H:i:s');

    $leadId = trim($data['lead_id'] ?? '');
    $visitorId = trim($data['visitor_id'] ?? '');
    $status = partial_leads_normalize_status($data['status'] ?? 'partial');

    $fields = [
        'name'         => trim($data['name'] ?? ''),
        'email'        => trim($data['email'] ?? ''),
        'phone'        => trim($data['phone'] ?? ''),
        'country'      => trim($data['country'] ?? ''),
        'messanger'    => trim($data['messanger'] ?? ''),
        'source'       => trim($data['source'] ?? ''),
        'locale'       => trim($data['locale'] ?? ''),
        'page_url'     => trim($data['page_url'] ?? ''),
        'referrer'     => trim($data['referrer'] ?? ''),
        'utm_source'   => trim($data['utm_source'] ?? ''),
        'utm_campaign' => trim($data['utm_campaign'] ?? ''),
        'utm_medium'   => trim($data['utm_medium'] ?? ''),
    ];

    try {
        $existingLead = null;

        if ($leadId && partial_leads_is_valid_id($leadId)) {
            $existingLead = partial_leads_find($pdo, $leadId);
        }

        if (!$existingLead && $visitorId) {
            $existingLead = partial_leads_find_latest_by_visitor_id($pdo, $visitorId);

            if ($existingLead) {
                $leadId = $existingLead['id'];
            }
        }

        if ($existingLead) {
            $wasCompleted = ($existingLead['status'] ?? '') === 'completed';

            if ($wasCompleted) {
                $status = 'completed';
                $completedAt = $existingLead['completed_at'];
            } elseif ($status === 'completed') {
                $completedAt = $now;
            } else {
                $completedAt = $existingLead['completed_at'] ?? null;
            }

            // Оновлюємо поле тільки якщо нове значення непорожнє, інакше залишаємо старе:
            $sql = "
                UPDATE leads
                SET
                    name      = CASE WHEN :name != '' THEN :name ELSE name END,
                    email     = CASE WHEN :email != '' THEN :email ELSE email END,
                    phone     = CASE WHEN :phone != '' THEN :phone ELSE phone END,
                    country   = CASE WHEN :country != '' THEN :country ELSE country END,
                    messanger = CASE WHEN :messanger != '' THEN :messanger ELSE messanger END,
                    source       = CASE WHEN :source != '' THEN :source ELSE source END,
                    locale       = CASE WHEN :locale != '' THEN :locale ELSE locale END,
                    page_url     = CASE WHEN :page_url != '' THEN :page_url ELSE page_url END,
                    referrer     = CASE WHEN :referrer != '' THEN :referrer ELSE referrer END,
                    utm_source   = CASE WHEN :utm_source != '' THEN :utm_source ELSE utm_source END,
                    utm_campaign = CASE WHEN :utm_campaign != '' THEN :utm_campaign ELSE utm_campaign END,
                    utm_medium   = CASE WHEN :utm_medium != '' THEN :utm_medium ELSE utm_medium END,
                    visitor_id   = CASE WHEN :visitor_id != '' THEN :visitor_id ELSE visitor_id END,
                    status       = :status,
                    updated_at   = :updated_at,
                    completed_at = :completed_at,
                    sheets_synced = 0,
                    sheets_synced_at = NULL
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'           => $leadId,
                ':name'         => $fields['name'],
                ':email'        => $fields['email'],
                ':phone'        => $fields['phone'],
                ':country'      => $fields['country'],
                ':messanger'    => $fields['messanger'],
                ':status'       => $status,
                ':source'       => $fields['source'],
                ':locale'       => $fields['locale'],
                ':page_url'     => $fields['page_url'],
                ':referrer'     => $fields['referrer'],
                ':utm_source'   => $fields['utm_source'],
                ':utm_campaign' => $fields['utm_campaign'],
                ':utm_medium'   => $fields['utm_medium'],
                ':visitor_id'   => $visitorId,
                ':updated_at'   => $now,
                ':completed_at' => $completedAt,
            ]);

            return [
                'success' => true,
                'lead_id' => $leadId,
                'status'  => $status,
            ];
        }

        if (!$leadId || !partial_leads_is_valid_id($leadId)) {
            $leadId = partial_leads_generate_id();
        }

        $completedAt = $status === 'completed' ? $now : null;

        $sql = "
            INSERT INTO leads (
                id,
                name,
                email,
                phone,
                country,
                messanger,
                status,
                tg_sent,
                tg_sent_at,
                sheets_synced,
                sheets_synced_at,
                source,
                locale,
                page_url,
                referrer,
                utm_source,
                utm_campaign,
                utm_medium,
                visitor_id,
                created_at,
                updated_at,
                completed_at
            ) VALUES (
                :id,
                :name,
                :email,
                :phone,
                :country,
                :messanger,
                :status,
                0,
                NULL,
                0,
                NULL,
                :source,
                :locale,
                :page_url,
                :referrer,
                :utm_source,
                :utm_campaign,
                :utm_medium,
                :visitor_id,
                :created_at,
                :updated_at,
                :completed_at
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'           => $leadId,
            ':name'         => $fields['name'],
            ':email'        => $fields['email'],
            ':phone'        => $fields['phone'],
            ':country'      => $fields['country'],
            ':messanger'    => $fields['messanger'],
            ':status'       => $status,
            ':source'       => $fields['source'],
            ':locale'       => $fields['locale'],
            ':page_url'     => $fields['page_url'],
            ':referrer'     => $fields['referrer'],
            ':utm_source'   => $fields['utm_source'],
            ':utm_campaign' => $fields['utm_campaign'],
            ':utm_medium'   => $fields['utm_medium'],
            ':visitor_id'   => $visitorId,
            ':created_at'   => $now,
            ':updated_at'   => $now,
            ':completed_at' => $completedAt,
        ]);

        return [
            'success' => true,
            'lead_id' => $leadId,
            'status'  => $status,
        ];
    } catch (PDOException $e) {
        error_log('DB save error: ' . $e->getMessage());

        return [
            'success' => false,
            'reason'  => 'server_error',
        ];
    }
}

function partial_leads_mark_completed(string $leadId): bool
{
    if (!partial_leads_is_valid_id($leadId)) {
        return false;
    }

    $pdo = partial_leads_get_pdo();

    if (!$pdo || !partial_leads_init_db($pdo)) {
        return false;
    }

    try {
        $existingLead = partial_leads_find($pdo, $leadId);

        if (!$existingLead) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        if (($existingLead['status'] ?? '') === 'completed') {
            return true;
        }

        $sql = "
            UPDATE leads
            SET
                status = 'completed',
                completed_at = :completed_at,
                updated_at = :updated_at,
                sheets_synced = 0,
                sheets_synced_at = NULL
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':completed_at' => $now,
            ':updated_at'   => $now,
            ':id'           => $leadId,
        ]);

        return true;
    } catch (PDOException $e) {
        error_log('DB mark completed error: ' . $e->getMessage());
        return false;
    }
}