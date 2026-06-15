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
            completed_at     TEXT
        );
    ";

    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log('DB init error: ' . $e->getMessage());
        return false;
    }
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

function partial_leads_find(PDO $pdo, string $leadId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $leadId]);

    $lead = $stmt->fetch();

    return $lead ?: null;
}

function partial_leads_save(array $data): array
{
    $pdo = partial_leads_get_pdo();

    if (!$pdo || !partial_leads_init_db($pdo)) {
        return ['success' => false, 'reason' => 'server_error'];
    }

    $now = date('Y-m-d H:i:s');

    $leadId = trim($data['lead_id'] ?? '');
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

            $sql = "
                UPDATE leads
                SET
                    name = :name,
                    email = :email,
                    phone = :phone,
                    country = :country,
                    messanger = :messanger,
                    status = :status,
                    source = :source,
                    locale = :locale,
                    page_url = :page_url,
                    referrer = :referrer,
                    utm_source = :utm_source,
                    utm_campaign = :utm_campaign,
                    utm_medium = :utm_medium,
                    updated_at = :updated_at,
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
            $sql = "
                UPDATE leads
                SET
                    status = 'completed',
                    updated_at = :updated_at,
                    sheets_synced = 0,
                    sheets_synced_at = NULL
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':updated_at' => $now,
                ':id'         => $leadId,
            ]);

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