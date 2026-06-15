<?php

define('DB_PATH', __DIR__ . '/data/leads.db');
define('PARTIAL_LEAD_TTL_HOURS', 24);
define('PARTIAL_LEAD_DEBOUNCE_MS', 1500);

// Google Sheets — наступний етап
define('GOOGLE_SHEETS_ENABLED', true);
define('GOOGLE_SHEETS_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbyJ1hCIRrS-8RJdeeieHcgAJjn2Ujsh0TxIJj4Lg6IlbhAjm3IAyJXNlJjfaYU3N_Bp/exec');

// Telegram — після Google Sheets
define('TG_ENABLED', false);
define('TG_BOT_TOKEN', '');
define('TG_CHAT_ID', '');
