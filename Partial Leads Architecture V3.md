# Partial Leads + SQLite + Google Sheets Architecture — V3

**Updated:** 2026-09-14

**Scope of this version:** partial leads, reusable frontend lead-form module, Telegram normalization/validation, remote Telegram existence check, SQLite → Google Sheets synchronization, tested fail-open behavior, direct-final persistence, LP integration contract, and current refactor/hardening backlog.

## What changed in V3

Compared with V2, this version documents:

```text
Telegram normalization + local format/junk validation
server-side Fragment/VPS checker
TAKEN / AVAILABLE / unknown mapping
fail-open behavior and checker toggle
localized frontend telegram_not_found UX
full final snapshot merge into completed lead
Google Sheets stale-snapshot race fix
direct-final-before-debounce persistence fix + regression test
GSAP/form dependency fix + regression test
frontend business logic extracted to reusable js/lead-form.js
LP integration contract + integration checklist
checker/debug test scenarios
current small-refactor / hardening backlog
production HTTPS / secret / debug-log hardening
```

---

## Purpose

Система зберігає незавершені заявки користувачів до натискання `Submit`, дозволяє накопичувати дані після reload сторінки та синхронізує актуальний стан лідів у Google Sheets для менеджерів.

Основний принцип:

```text
SQLite = Source of Truth
Google Sheets = View for Managers
```

Усі дані спочатку потрапляють у SQLite.

Google Sheets використовується лише як менеджерське представлення даних і не є основним сховищем.

---

# High Level Architecture

Система має два окремі frontend/backend flow: **partial save** і **final submit**.

```text
                         ┌──────────────────────────────┐
                         │          User Form           │
                         └──────────────┬───────────────┘
                                        │
                         normalize + local validation
                                        │
                    ┌───────────────────┴───────────────────┐
                    │                                       │
              PARTIAL SAVE                           FINAL SUBMIT
                    │                                       │
            debounce 1500 ms                          send.php
                    │                                       │
             save_lead.php                 local final validation
                    │                                       │
                    │                         Telegram remote checker
                    │                         (server-side only)
                    │                              │
                    │                 ┌────────────┴────────────┐
                    │                 │                         │
                    │             not_taken                  taken
                    │                 │                         │
                    │        422 telegram_not_found             │
                    │                                           │
                    │                                    send email
                    │                                           │
                    └──────────────────┬────────────────────────┘
                                       │
                                 SQLite leads.db
                                       │
                              cron/sync_sheets.php
                                       │
                              Google Apps Script
                                       │
                                  Google Sheets
```

Окремо існує технічний сценарій:

```text
Telegram checker timeout / 4xx / 5xx / invalid response
    → result = unknown
    → fail-open
    → final submit продовжується
```

Форма не пише напряму в Google Sheets.

Це важливо, бо Google Sheets / Apps Script або Telegram checker можуть бути тимчасово недоступні, але технічна проблема зовнішнього сервісу не повинна втрачати валідну заявку.

---

# Components

## Frontend

Frontend тепер розділений на **LP-specific presentation code** і **reusable lead business logic**.

Основні файли:

```text
js/main.min.js   → LP-specific animation / presentation / language switch
js/lead-form.js  → reusable lead capture / validation / partial save / final submit
```

`lead-form.js` відповідає за:

* debounce partial save;
* frontend-валідацію телефону та email;
* Telegram input normalization;
* локальну Telegram format/junk validation;
* localized UX для backend-помилки `telegram_not_found`;
* створення `visitor_id`;
* збереження `lead_id` у `localStorage`;
* передачу `lead_id` та `visitor_id` у backend;
* фінальний submit.

`main.min.js` більше не містить partial-lead / submit business logic. Це важливо для повторного використання системи на інших LP і для того, щоб форма не залежала від GSAP або іншого presentation JS.

Remote Telegram check **не виконується з браузера**. Токен checker-а ніколи не передається у frontend.

На початку `lead-form.js` використовується невеликий integration config:

```js
const LEAD_FORM_CONFIG = {
    formSelector: '#formHomeReg',
    partialUrl: 'save_lead.php',
    submitUrl: 'send.php',
    debounceMs: 1500,
};
```

Важливий regression rule:

```text
GSAP unavailable / .top__inner missing
→ animation layer can be skipped
→ lead-form.js still initializes
→ intl-tel-input / validation / partial save / final submit still work
```

Назва `main.min.js` історична. Файл може бути не мінімізований.

---

## Backend

Основні файли:

```text
save_lead.php
send.php
lead_storage.php
telegram_input.php
telegram_checker.php
cron/sync_sheets.php
config.php
```

Призначення:

```text
save_lead.php         → partial save
send.php              → final submit
lead_storage.php      → SQLite storage / merge / lead lifecycle
telegram_input.php    → Telegram normalization
telegram_checker.php  → server-side remote Telegram existence check
cron/sync_sheets.php  → SQLite → Google Sheets sync
config.php            → конфігурація
```

Історичне ім'я поля:

```text
messanger
```

залишається без перейменування, щоб не ламати існуючі integration/storage contracts.

---

## SQLite

SQLite використовується як основна база даних.

Причини вибору:

* простий деплой;
* не потрібен MySQL;
* достатньо для LP / lead capture;
* база зберігається одним файлом;
* легко робити backup.

База:

```text
data/leads.db
```

---

## Google Sheets

Google Sheets використовується як таблиця для менеджерів.

Google Sheets не є джерелом правди.

Якщо дані в SQLite та Google Sheets відрізняються, правильними вважаються дані SQLite.

---

# Browser Storage

У браузері використовуються два незалежні ключі:

```text
registration_draft
visitor_id
```

---

## registration_draft

`registration_draft` — короткоживуча технічна чернетка поточного ліда.

Приклад:

```json
{
  "lead_id": "lead_20260618115123_ca57afec",
  "created_at": 1781783483000
}
```

Зберігається:

```text
24 години
```

Після закінчення TTL draft видаляється при наступному відкритті сторінки.

Важливо:

```text
registration_draft не містить персональні дані форми.
```

У localStorage не зберігаються:

```text
name
phone
email
country
messanger
```

Причини:

* не зберігати PII в браузері;
* уникнути витоку персональних даних;
* не автозаповнювати форму несподівано для користувача;
* після reload форма візуально залишається чистою.

---

## visitor_id

`visitor_id` — довгоживучий ID конкретного браузера користувача.

Приклад:

```text
fab80815-056a-44f8-9a66-c40af9e7579e
```

Він зберігається окремо:

```text
localStorage.visitor_id
```

Живе доти, доки користувач не очистить localStorage або не відкриє сайт в іншому браузері / інкогніто.

Важливо:

```text
visitor_id — це не device fingerprint.
```

Система не визначає фізичний пристрій. Вона генерує UUID і зберігає його у localStorage конкретного браузера.

Тому:

```text
Chrome на ноутбуці  → visitor_id A
Firefox на ноутбуці → visitor_id B
Chrome на телефоні  → visitor_id C
```

---

# lead_id vs visitor_id

## lead_id

`lead_id` — ID конкретної заявки в SQLite.

Відповідає на питання:

```text
Який саме lead оновлюємо?
```

Приклад:

```text
lead_20260618115123_ca57afec
```

---

## visitor_id

`visitor_id` — ID браузера / відвідувача.

Відповідає на питання:

```text
З якого браузера прийшла дія?
```

---

## Чому потрібні обидва

Раніше був тільки `lead_id`.

Якщо `lead_id` втрачався після reload або очищення `registration_draft`, система не могла зрозуміти, що користувач уже мав partial lead, і створювала новий запис.

Було:

```text
1. User вводить name + phone
2. Створюється lead_1
3. Reload
4. registration_draft втрачено
5. User вводить email
6. Створюється lead_2
```

Після додавання `visitor_id`:

```text
1. User вводить name + phone
2. Створюється lead_1 + visitor_id A
3. Reload
4. registration_draft втрачено
5. visitor_id A залишився
6. User вводить email
7. Backend знаходить останній lead по visitor_id A
8. Оновлюється lead_1
```

---

# Frontend Logic

## visitor_id generation

У frontend створюється persistent `visitor_id`.

Спрощений приклад:

```js
let visitorId = null;

const getVisitorId = () => {
    const VISITOR_ID_KEY = 'visitor_id';
    let id = localStorage.getItem(VISITOR_ID_KEY);

    if (!id && window.crypto && typeof window.crypto.randomUUID === 'function') {
        try {
            id = window.crypto.randomUUID();
        } catch (e) {
            id = '';
        }
    }

    if (!id) {
        id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            var v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    localStorage.setItem(VISITOR_ID_KEY, id);

    return id;
};

visitorId = getVisitorId();
```

---

## Sending visitor_id to backend

`visitor_id` передається і в partial save, і в final submit.

Partial save:

```js
formData.set('visitor_id', visitorId);
```

Final submit:

```js
formData.set('visitor_id', visitorId);
```

---

# Lead Lifecycle

## New Lead

Користувач починає заповнювати форму.

Після зміни полів запускається debounce:

```text
1500ms
```

Якщо у формі є хоча б один контакт:

```text
phone
або
email
```

створюється partial lead.

Telegram сам по собі **не створює новий partial lead**:

```text
no current lead_id
phone empty
email empty
messanger filled
    → do not create lead
```

Але якщо lead уже існує, Telegram може оновити цей existing lead.

У SQLite створюється запис:

```text
status = partial
```

та генерується:

```text
lead_id
```

---

## Partial Update

Після створення lead користувач може дозаповнювати будь-які поля.

Навіть якщо після reload поля форми порожні, система може продовжити оновлення того самого lead через:

```text
lead_id
або
visitor_id
```

---

## Reload Behavior

Після reload форма візуально очищена:

```text
name      → empty
phone     → empty
email     → empty
country   → empty
messanger → empty
```

Але технічно у браузері можуть залишатися:

```text
registration_draft.lead_id
visitor_id
```

Якщо `registration_draft` ще живий, frontend відновлює `lead_id`.

Якщо `registration_draft` втрачений, але `visitor_id` залишився, backend може знайти останній lead цього visitor.

---

# Deduplication Strategy

Дедуплікація потрібна, щоб один користувач випадково не створював кілька лідів після reload або втрати `lead_id`.

Поточний порядок пошуку:

```text
1. lead_id
2. visitor_id
3. create new lead
```

---

## Step 1 — lead_id

Якщо у запиті є валідний `lead_id`, backend оновлює саме цей lead.

```text
lead_id exists
    → update this lead
```

Це найточніший сценарій.

---

## Step 2 — visitor_id

Якщо `lead_id` відсутній, але є `visitor_id`, backend шукає останній lead цього visitor у межах TTL.

```text
lead_id missing
visitor_id exists
    → find latest lead by visitor_id within TTL
```

Якщо lead знайдено — він оновлюється.

Якщо не знайдено — створюється новий.

---

## Step 3 — create new lead

Якщо не знайдено ні `lead_id`, ні актуального lead по `visitor_id`, створюється новий lead.

```text
no lead_id
no matching visitor_id lead
    → insert new lead
```

---

## TTL for visitor_id dedup

Для пошуку по `visitor_id` використовується TTL-вікно:

```text
PARTIAL_LEAD_TTL_HOURS = 24
```

Тобто один і той самий браузер не створює новий lead протягом 24 годин, якщо вже є актуальний lead цього visitor.

Через 24 години нова заявка з того самого браузера може створити новий lead.

---

## Important Limitation

Поточний dedup працює на рівні браузера.

Він не об'єднує заявки між:

```text
різними браузерами
різними пристроями
інкогніто-режимом
очищеним localStorage
```

Це зроблено свідомо.

Для MVP не використовується device fingerprinting, бо він складніший, менш прозорий і може мати privacy-ризики.

---

# Why Not Device Fingerprint

Можна було б прив'язуватися до пристрою через fingerprint, але для MVP обрано простіший варіант.

Fingerprint зазвичай використовує:

```text
User-Agent
screen resolution
timezone
Canvas
WebGL
fonts
language
platform
```

Проблеми fingerprinting:

* складніша реалізація;
* не 100% стабільний;
* може змінитися після оновлення браузера;
* може давати false positives;
* privacy-ризики;
* може потребувати додаткового опису у Privacy Policy.

Поточне рішення:

```text
visitor_id in localStorage
```

просте, прозоре і закриває основний бізнес-кейс:

```text
втрата lead_id після reload не створює дубльований lead
```

---

# Phone / Email Deduplication

Поточна реалізація не виконує dedup по телефону або email.

Тобто порядок зараз:

```text
lead_id → visitor_id → create
```

А не:

```text
lead_id → visitor_id → phone/email → create
```

## Чому phone/email dedup не зроблено в MVP

Phone/email dedup може випадково склеїти різних людей.

Приклади ризиків:

```text
декілька людей використовують один робочий email
родичі/партнери використовують один телефон
тестувальники вводять однакові test@test.com
користувач створює другу реальну заявку через деякий час
```

False merge гірший за дубль, бо окрема заявка може бути втрачена.

Тому phone/email dedup винесено у можливу наступну ітерацію.

## Як можна доробити пізніше

Можливий майбутній порядок:

```text
1. lead_id
2. visitor_id
3. exact phone match within 24h
4. exact email match within 24h
5. create new lead
```

Найбезпечніше починати з:

```text
exact phone match within 24h
```

Email dedup варто додавати обережніше.

---

# Backend Storage Logic

Основна логіка зберігання знаходиться у:

```text
lead_storage.php
```

Спрощений алгоритм `partial_leads_save()`:

```text
1. Normalize input
2. Validate minimal data
3. Try to find existing lead by lead_id
4. If not found, try visitor_id
5. If found, update existing lead
6. If not found, insert new lead
7. Reset sheets_synced = 0 after update
```

---

## visitor_id column

У SQLite таблицю додано поле:

```sql
visitor_id TEXT
```

Також додано неунікальний індекс:

```sql
CREATE INDEX IF NOT EXISTS idx_leads_visitor_id ON leads(visitor_id);
```

Індекс не унікальний, бо один browser visitor теоретично може створити кілька різних лідів у різні часові періоди.

---

## Safe DB Migration

Колонка `visitor_id` додається безпечно.

Перед `ALTER TABLE` перевіряється структура таблиці:

```php
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
```

Це важливо, бо SQLite кидає помилку, якщо спробувати додати колонку, яка вже існує.

---

## Visitor Lookup Helper

Пошук lead по `visitor_id` виконується helper-функцією в `lead_storage.php`.

Спрощений приклад:

```php
function partial_leads_find_latest_by_visitor_id(PDO $pdo, string $visitorId): ?array
{
    $ttlHours = defined('PARTIAL_LEAD_TTL_HOURS') ? PARTIAL_LEAD_TTL_HOURS : 24;
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$ttlHours} hours"));

    $stmt = $pdo->prepare(
        'SELECT *
         FROM leads
         WHERE visitor_id = :visitor_id
           AND created_at >= :cutoff
         ORDER BY updated_at DESC
         LIMIT 1'
    );

    $stmt->execute([
        ':visitor_id' => $visitorId,
        ':cutoff' => $cutoff,
    ]);

    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    return $lead ?: null;
}
```

Важливо:

```text
SQLite datetime('now') не використовується.
cutoff рахується у PHP.
```

---

# Merge Strategy

При оновленні lead порожні значення не перезаписують існуючі дані.

Було:

```text
name = John
phone = +380501234567
email =
```

Новий partial update:

```text
name =
phone =
email = john@test.com
```

Результат:

```text
name = John
phone = +380501234567
email = john@test.com
```

Це захищає дані від втрати після reload.

---

## Empty Values

Порожні поля не стирають існуючі значення.

Це стосується:

```text
name
email
phone
country
messanger
source
locale
page_url
referrer
utm_source
utm_campaign
utm_medium
```

---

# Validation Strategy

## Frontend Validation

Frontend-валідація потрібна для UX та щоб не слати очевидне сміття у partial save.

Важливо:

```text
Frontend validation = UX filter
Backend validation = required safety layer
```

Frontend не є повноцінним захистом, бо будь-хто може відправити POST напряму.

---

## Phone Validation

Телефон валідовується через:

```text
intl-tel-input
```

Використовується:

```js
phoneInputInstance.isValidNumber()
```

Невалідні приклади:

```text
+333
+100500
4444444
```

Поводження:

```text
invalid phone
    → add .form__input--invalid
    → block partial save
    → block final submit
```

---

## Full International Phone Format

Через `separateDialCode: true` користувач бачить код країни окремо від локальної частини номера.

Щоб у SQLite, email і Google Sheets не потрапляв тільки локальний номер, перед відправкою `FormData` телефон замінюється на повний міжнародний формат.

```js
const applyFullPhoneToFormData = (formData) => {
    if (!phoneInputInstance || !phoneInputInstance.isValidNumber()) return;

    formData.set('phone', phoneInputInstance.getNumber());
};
```

Було:

```text
964444444
```

Стало:

```text
+964444444
```

Формат:

```text
E.164
```

---

## Email Validation

Email перевіряється на frontend перед partial save та submit.

Спрощена логіка:

```js
const simpleEmailPattern = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,10}$/;
const valid = emailInput.checkValidity() && simpleEmailPattern.test(emailValue);
```

Невалідні приклади:

```text
@
test@
enot42@
```

Поведінка:

```text
invalid email
    → add .form__input--invalid
    → block partial save
    → block final submit
```

---

## Telegram Input Normalization

Telegram input нормалізується до canonical handle:

```text
@username
```

Підтримуються, зокрема:

```text
enot42
@enot42
t.me/enot42
http://t.me/enot42
https://t.me/enot42
https://www.t.me/enot42/
```

Допустимий URL може містити supported query, наприклад `?direct`.

Не приймаються:

```text
invite links
message links
reserved Telegram routes
foreign URLs
arbitrary nested paths
```

Normalization реалізована окремо у:

```text
telegram_input.php
```

Frontend має defensive UX normalization з тією самою бізнес-поведінкою. Це навмисне дублювання між browser UX layer і authoritative backend layer.

---

## Telegram Format Validation

Canonical format:

```regex
^@[A-Za-z][A-Za-z0-9_]{3,30}[A-Za-z0-9]$
```

Тобто username:

```text
5–32 chars
starts with Latin letter
contains letters / digits / underscore
does not end with underscore
```

Backend validation залишається authoritative.

---

## Telegram Junk Validation

Format validation не гарантує, що username не є очевидним сміттям.

Тому є окремий junk scoring:

```text
strong pattern = weight 3
weak pattern   = weight 1
reject threshold = 3
```

Приклади strong signals:

```text
keyboard sequences
entire username = repeated char
entire username = repeated short block
```

Weak signals включають:

```text
test/admin/user/fake/etc.
long digit sequences
long consonant runs
ascending/descending digit sequences
```

Один слабкий сигнал сам по собі не повинен відхиляти lead.

Frontend і backend мають однакову логіку за змістом, але залишаються двома незалежними шарами:

```text
frontend = UX filter
backend = authoritative validation
```

---

## Telegram Partial Validation Rule

Для partial save:

```text
empty Telegram allowed
filled Telegram must pass normalization + format + junk validation
```

Remote Telegram existence check для partial save **не виконується**.

Причини:

```text
не hammer-ити remote service на debounce
не робити partial save залежним від зовнішнього сервісу
не передавати checker token у frontend
```

---

## Telegram Final Validation Rule

Для final submit Telegram є required.

Порядок:

```text
1. normalize
2. local/backend format validation
3. junk validation
4. remote existence check
5. email send
6. completed transition
```

Backend reasons для local Telegram validation:

```text
telegram_required
telegram_invalid_format
telegram_obvious_fake
```

Remote `not_taken` повертається frontend як:

```text
telegram_not_found
```

---

# Telegram Remote Checker

Remote checker використовується **тільки у final submit**.

Файл:

```text
telegram_checker.php
```

Конфігурація:

```php
TELEGRAM_CHECKER_ENABLED
TELEGRAM_CHECKER_URL
TELEGRAM_CHECKER_TOKEN
TELEGRAM_CHECKER_CONNECT_TIMEOUT
TELEGRAM_CHECKER_TIMEOUT
TELEGRAM_CHECKER_DEBUG_LOG
```

Секретне значення `TELEGRAM_CHECKER_TOKEN` не повинно зберігатися у frontend або потрапляти в client-side requests.

---

## Remote Service Contract

Backend виконує server-to-server request:

```http
POST /check
Content-Type: application/json
X-API-TOKEN: <secret>
```

Body:

```json
{
  "username": "example_username"
}
```

Username передається без `@`.

Remote service використовує Fragment marketplace status як heuristic для існування Telegram username.

---

## Status Mapping

Поточне mapping:

```text
TAKEN
    → result = taken
    → final submit continues

AVAILABLE
AUCTION
SOLD
    → result = not_taken
    → send.php returns 422 telegram_not_found

UNKNOWN
ERROR
network error
timeout
403
404
5xx
invalid JSON / technical response
    → result = unknown
    → fail-open
```

Важливий нюанс:

```text
HTTP 400 from checker can be a normal AVAILABLE result.
```

Тому `telegram_checker.php` не повинен використовувати `CURLOPT_FAILONERROR = true`, і body потрібно парсити навіть для HTTP 400.

---

## Why Fragment Is Used as a Heuristic

Порівняння Fragment з Telegram/Madeline reference показало:

```text
private users → TAKEN
bots          → TAKEN
fake/free     → AVAILABLE
```

Відомий limitation:

```text
деякі channels можуть мати marketplace mapping, яке не відповідає contact-form use case
```

Для поточної форми це прийнятно, бо поле призначене для персонального Telegram contact. Bots вважаються достатньо реальним Telegram account для проходження existence check.

---

## Fail-open

Remote checker — зовнішня залежність.

Технічна помилка checker-а не повинна блокувати lead:

```text
result = unknown
    → diagnostic log
    → continue final submit
```

Це окреме бізнес-рішення.

Перевірений сценарій:

```text
broken checker URL
→ HTTP 404 / technical response
→ result = unknown
→ email sent
→ lead completed
→ Sheets completed
```

---

## Checker Debug Logging

Для початкової діагностики може бути увімкнений окремий debug log:

```php
define('TELEGRAM_CHECKER_DEBUG_LOG', true);
```

Приклад логічного запису:

```text
@username => {
  "result": "taken|not_taken|unknown",
  "fragment_status": "...",
  "http_code": 200,
  "error": null
}
```

Для `unknown` також використовується server-side `error_log()`.

Debug log — тимчасовий operational інструмент. У production його бажано вимкнути або мінімізувати, оскільки він може містити Telegram username.

---

## Frontend UX for telegram_not_found

Якщо `send.php` повертає:

```json
{
  "success": false,
  "reason": "telegram_not_found"
}
```

frontend:

```text
adds .form__input--invalid to Telegram field
keeps one existing Telegram validation block
switches its text from local-format message to remote-check message
keeps generic "Failed to send application."
```

Для Telegram не створюється другий dynamic error element. Використовується один блок, наприклад:

```php
<div
    class="validate-block"
    id="telegramValidationMessage"
    data-invalid="<?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?>"
    data-not-found="<?= htmlspecialchars($local['telegram_not_found'], ENT_QUOTES, 'UTF-8'); ?>"
>
    <span><?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>
```

Переклади залишаються в існуючому PHP `$local`, а JS лише читає готові localized strings через `dataset`.

Рекомендовані semantic keys:

```text
telegram_invalid   → local format / junk problem
telegram_not_found → valid-looking username, but remote checker says not_taken
```

Короткий EN варіант:

```text
telegram_invalid   = *Enter a valid Telegram username.
telegram_not_found = *Please check your Telegram username.
```

Remote-specific state очищується **тільки при наступному input у Telegram field**. Після цього локальний `validateTelegram()` повторно вирішує, чи поле має залишатися invalid через format/junk validation.

Зміна name / email / phone / country не очищує Telegram remote error.

---

## Partial Validation Rule

Для partial save:

```text
empty phone/email allowed
filled phone/email must be valid
```

Приклади:

```text
phone empty + email valid       → allowed
phone valid + email empty       → allowed
phone empty + email empty       → no new lead
phone invalid + email valid     → blocked
phone valid + email invalid     → blocked
```

---

## Final Submit Validation Rule

Для final submit:

```text
name required
phone required and valid
email required and valid
Telegram required and locally valid
```

Якщо phone / email / Telegram local validation не проходить:

```text
send.php request is not sent
```

Якщо local validation пройшла, `send.php` додатково виконує server-side remote Telegram existence check.

---

# Completed Lead

Після натискання `Submit`:

```text
send.php
```

виконує:

```text
required/email validation
Telegram normalization + local validation
Telegram remote check
email send
final data merge into SQLite
completed transition
```

Після успішної відправки email:

```text
status = completed
completed_at = timestamp
```

Після successful email у `partial_leads_save()` передається повний final snapshot разом із `status = completed`:

```text
name
email
phone
country
messanger
lead_id
visitor_id
status = completed
```

Це закриває не тільки Telegram race, а й загальний direct-final case, коли partial debounce ще не встиг зберегти останні значення:

```text
user changes Telegram
→ immediately clicks Submit
→ 1500 ms partial debounce has not fired yet
```

Якщо final snapshot save у storage падає і є `lead_id`, існує fallback через `partial_leads_mark_completed()`.

Важливе бізнес-правило:

```text
successful email submit must not be broken by partial-lead storage problems
```

---

## Submit With lead_id

Типовий сценарій:

```text
User
 ↓
Partial Lead Created
 ↓
lead_id saved in registration_draft
 ↓
User clicks Submit
 ↓
send.php
 ↓
Telegram remote check
 ↓
Email sent
 ↓
partial_leads_save(... status = completed ...)
 ↓
fallback partial_leads_mark_completed() only if needed
```

---

## Submit Without lead_id But With visitor_id

Після додавання `visitor_id` submit може знайти існуючий lead навіть без `lead_id`.

Сценарій:

```text
lead_id missing
visitor_id exists
 ↓
send.php
 ↓
partial_leads_find_latest_by_visitor_id()
 ↓
lead found
 ↓
full final snapshot merge + status = completed
```

Це закриває кейс втрати `registration_draft`.

---

## Submit Without lead_id And Without Matching visitor_id

Цей audit gap **виправлений і regression-tested**.

Final submit більше не покладається на те, що 1500 ms partial debounce уже встиг створити lead.

Після успішного `mail()` `send.php` викликає `partial_leads_save()` з **повним final snapshot**:

```text
lead_id
visitor_id
name
email
phone
country
messanger
status = completed
```

Resolution залишається таким:

```text
existing lead_id when available
→ fallback by visitor_id
→ otherwise insert new completed lead
```

Тому сценарій:

```text
no lead_id
no matching visitor_id lead
→ email sent
→ completed SQLite row is created
→ next Sheets sync receives completed lead
```

Regression test був виконаний із заблокованим `save_lead.php`, порожнім `registration_draft` і blank `lead_id` у final payload. Результат: final submit повернув success, email був відправлений, а повний completed snapshot з'явився в SQLite/Sheets.

Водночас зберігається правило:

```text
storage failure after successful email must not turn final submit into failure
```

Fallback `partial_leads_mark_completed()` використовується тільки якщо final `partial_leads_save()` не зміг зберегти snapshot і є `lead_id`.

---

# Lead Statuses

## partial

Користувач залишив контактні дані, але ще не завершив заявку.

Ознаки:

```text
status = partial
completed_at = NULL
```

Такий lead може оновлюватися багато разів.

---

## completed

Користувач успішно відправив фінальну форму.

Ознаки:

```text
status = completed
completed_at != NULL
```

Після переходу у `completed` lead не може повернутись у `partial`.

---

## spam

Зарезервований статус для майбутнього використання.

Поточна реалізація автоматично не виставляє:

```text
status = spam
```

Можливе майбутнє використання:

```text
антиспам-фільтрування
ручна модерація
автоматичне маркування підозрілих заявок
```

На поточну роботу системи `spam` не впливає.

---

# Protection Against Downgrade

Після переходу у:

```text
completed
```

заявка не може повернутися у:

```text
partial
```

Навіть якщо пізніше приходить відкладений debounce-запит.

Це захищає систему від race condition:

```text
partial save pending
submit completed
old partial request returns later
```

---

# completed_at Behavior

`completed_at` встановлюється тільки під час першого переходу у `completed`.

Повторний submit не повинен змінювати `completed_at`.

Якщо lead вже completed, система не повинна без потреби оновлювати:

```text
updated_at
sheets_synced
sheets_synced_at
```

Це зменшує зайвий шум у Google Sheets sync.

---

# Google Sheets Synchronization

Google Sheets не отримує дані напряму з форми.

Синхронізація виконується через cron.

```text
Form
 ↓
SQLite
 ↓
cron/sync_sheets.php
 ↓
Google Apps Script
 ↓
Google Sheets
```

---

## Why Not Sync Directly

Форма не залежить від Google.

Якщо:

```text
Google недоступний
Apps Script повернув помилку
закінчились квоти
мережеві проблеми
```

користувач не втрачає заявку.

Дані вже збережені в SQLite.

---

## Sync Queue

Для кожного lead зберігаються:

```text
sheets_synced
sheets_synced_at
```

---

## New Lead

Після створення:

```text
sheets_synced = 0
```

Це означає:

```text
потрібно синхронізувати
```

---

## Updated Lead

Після зміни:

```text
sheets_synced = 0
sheets_synced_at = NULL
```

Запис повторно ставиться у чергу синхронізації.

---

## Successful Sync

Після успішної синхронізації:

```text
sheets_synced = 1
sheets_synced_at = timestamp
```

---

## Sync Race Protection

Cron синхронізує snapshot lead-а. Поки HTTP request до Google Apps Script виконується, lead може змінитися в SQLite.

Небезпечний race:

```text
cron reads partial snapshot
→ user submits final form
→ SQLite becomes completed
→ old partial snapshot reaches Sheets
→ old cron blindly marks current DB row sheets_synced = 1
```

Наслідок без захисту:

```text
SQLite = completed + sheets_synced = 1
Sheets = stale partial
```

Тому після успішного webhook response cron ставить `sheets_synced = 1` тільки якщо snapshot все ще актуальний:

```sql
UPDATE leads
SET sheets_synced = 1,
    sheets_synced_at = CURRENT_TIMESTAMP
WHERE id = ?
  AND updated_at = ?
  AND status = ?
```

Перевіряються і `updated_at`, і `status`.

`status` потрібен додатково, тому що timestamp має секундну точність і partial → completed теоретично може відбутися в ту саму секунду.

Якщо:

```text
rowCount() = 0
```

це означає, що lead змінився під час sync.

Тоді:

```text
new version remains sheets_synced = 0
→ next cron retries current state
```

Цей optimistic concurrency guard не слід прибирати або спрощувати.

---

# Google Sheets Behavior

Apps Script працює як UPSERT.

Логіка:

```text
lead_id exists in sheet
    → update row

lead_id missing in sheet
    → append row
```

Тому один `lead_id` відповідає одному рядку у Google Sheets.

---

## Reserved Columns

Стовпці `A-J` зарезервовані інтеграцією.

Їх не можна:

```text
переставляти
видаляти
перейменовувати
```

Їх можна:

```text
приховувати
змінювати ширину
форматувати
```

Менеджерські колонки можна додавати починаючи з:

```text
K
```

---

## visitor_id in Google Sheets

`visitor_id` не синхронізується в Google Sheets.

Причина:

```text
visitor_id — технічне поле
менеджерам воно не потрібне
може засмітити таблицю
може бути неправильно інтерпретоване
```

Для технічного аналізу `visitor_id` доступний у SQLite.

Якщо у майбутньому потрібен аналіз по `visitor_id`, краще зробити окремий технічний export або окрему вкладку, а не додавати його в основну менеджерську таблицю.

---

# Cron Job

Синхронізація запускається cron-задачею.

Поточна реалізація:

```cron
*/10 * * * *
```

Тобто кожні 10 хвилин.

Для тестування допускається запуск щохвилини.

Команда:

```bash
/usr/bin/php /absolute/path/to/cron/sync_sheets.php
```

---

# Google Apps Script

Apps Script приймає JSON payload від `cron/sync_sheets.php`.

Очікувані поля:

```text
lead_id
status
name
phone
email
country
messanger
created_at
updated_at
completed_at
```

`visitor_id` не відправляється.

Telegram знаходиться у колонці:

```text
G = messanger
```

Якщо значення відповідає canonical Telegram regex, Apps Script створює clickable rich-text link:

```text
@username → https://t.me/username
```

---


# Failure Handling

## save_lead.php

Якщо partial save падає, frontend отримує:

```json
{
  "success": false,
  "reason": "server_error"
}
```

Форма не повинна ламати UX, але lead може не зберегтися.

---

## Telegram Checker Failure

Business response mapping:

```text
taken      → continue
not_taken  → HTTP 422 + telegram_not_found
unknown    → fail-open
```

`unknown` може виникнути через:

```text
timeout
connection error
4xx/5xx technical response
invalid JSON
unrecognized response
```

Для `unknown` backend пише diagnostic log і продовжує final submit.

Frontend не бачить `unknown` як окремий response reason. Якщо fail-open успішно дійшов до email, frontend отримує звичайний:

```json
{
  "success": true
}
```

---

## Google Sheets Sync Failure

Якщо Google Sheets sync падає:

```text
sheets_synced залишається 0
```

Наступний cron-запуск спробує синхронізувати lead знову.

---

# Manual Debug Commands

## PHP syntax check

```bash
php -l lead_storage.php
php -l save_lead.php
php -l send.php
php -l cron/sync_sheets.php
```

---

## Check SQLite schema

```bash
php -r '
require_once "lead_storage.php";
$pdo = partial_leads_get_pdo();
partial_leads_init_db($pdo);
print_r($pdo->query("PRAGMA table_info(leads)")->fetchAll(PDO::FETCH_ASSOC));
'
```

---

## Test partial_leads_save from CLI

```bash
php -r '
require_once "lead_storage.php";
$r = partial_leads_save([
  "name" => "checkVisitor",
  "email" => "visitor@test.dev",
  "phone" => "",
  "visitor_id" => "test-visitor-id",
  "status" => "partial"
]);
print_r($r);
'
```

Expected:

```text
[success] => 1
[lead_id] => lead_...
[status] => partial
```

---

## Test Telegram checker from CLI

Real / occupied username:

```powershell
php -r "require 'telegram_checker.php'; var_export(telegram_checker_check('@infinity_khm'));"
```

Expected shape:

```text
result = taken
fragment_status = TAKEN
http_code = 200
error = NULL
```

Locally valid but unavailable username:

```powershell
php -r "require 'telegram_checker.php'; var_export(telegram_checker_check('@zzqvprobe_20260911_a1'));"
```

Expected shape:

```text
result = not_taken
fragment_status = AVAILABLE
http_code = 400
error = NULL
```

Technical failure / broken endpoint should produce:

```text
result = unknown
```

and final submit must continue because checker is fail-open.

---

## Run Google Sheets sync manually

```bash
php cron/sync_sheets.php
```

Expected:

```text
Processed: N
Success: N
Failed: 0
```

---

# Testing Scenarios

## Scenario 1 — Fresh partial lead

Steps:

```text
1. localStorage.clear()
2. reload page
3. enter valid phone or email
4. wait debounce
5. check save_lead.php payload
6. check SQLite
7. check Google Sheets after sync
```

Expected:

```text
visitor_id generated
lead_id generated
status = partial
sheets_synced eventually = 1
```

---

## Scenario 2 — Reload with registration_draft

Steps:

```text
1. create partial lead
2. reload page
3. registration_draft still exists
4. enter another field
```

Expected:

```text
same lead_id is updated
no duplicate lead created
```

---

## Scenario 3 — Lost registration_draft, visitor_id remains

Steps:

```text
1. create partial lead
2. remove only registration_draft
3. keep visitor_id
4. reload page
5. enter new field
```

Expected:

```text
backend finds latest lead by visitor_id
same lead is updated
no duplicate lead created
```

---

## Scenario 4 — Full submit

Steps:

```text
1. fill required fields
2. submit
```

Expected:

```text
email sent
status = completed
completed_at set
Google Sheets row updated
```

---

## Scenario 5 — Submit without lead_id but with visitor_id

Steps:

```text
1. create partial lead
2. remove registration_draft
3. keep visitor_id
4. submit final form
```

Expected:

```text
send.php finds lead by visitor_id
existing lead is marked completed
no duplicate lead created
```

---

## Scenario 6 — Invalid phone

Test values:

```text
+333
+100500
4444444
```

Expected:

```text
.form__input--invalid added
save_lead.php not called
send.php not called
```

---

## Scenario 7 — Invalid email

Test values:

```text
@
test@
enot42@
```

Expected:

```text
.form__input--invalid added
save_lead.php not called
send.php not called
```

---

## Scenario 8 — Telegram normalization

Test values:

```text
enot42
@enot42
t.me/enot42
https://www.t.me/enot42/
```

Expected:

```text
canonical value = @enot42
```

---

## Scenario 9 — Telegram locally invalid / junk

Expected:

```text
frontend blocks request
or backend returns Telegram validation reason
remote checker is not required
```

---

## Scenario 10 — Telegram exists

Steps:

```text
1. TELEGRAM_CHECKER_ENABLED = true
2. submit with known TAKEN username
```

Expected:

```text
remote result = taken
email sent
SQLite status = completed
Sheets status = completed
```

---

## Scenario 11 — Telegram not found

Steps:

```text
1. TELEGRAM_CHECKER_ENABLED = true
2. use locally valid AVAILABLE username
3. submit
```

Expected:

```text
send.php HTTP 422
reason = telegram_not_found
email not sent
lead remains partial
Sheets may contain the already-synced partial lead
Telegram field becomes invalid
localized telegram_not_found message displayed
generic "Failed to send application." remains
```

---

## Scenario 12 — Telegram checker disabled

Config:

```php
TELEGRAM_CHECKER_ENABLED = false
```

Submit a locally valid but unavailable username.

Expected:

```text
remote checker is not called
local format/junk validation passes
email sent
lead completed
Sheets completed
```

After the test:

```php
TELEGRAM_CHECKER_ENABLED = true
```

---

## Scenario 13 — Telegram checker technical failure

Break checker URL or otherwise force technical response.

Expected:

```text
telegram_checker_check() → result = unknown
diagnostic log created
send.php does not block
email sent
lead completed
Sheets completed
```

Verified example:

```text
http_code = 404
error = unrecognized_or_technical_response
```

---

## Scenario 14 — Sheets sync race

Goal: prove stale cron snapshot cannot mark a newer DB state as synced.

Expected:

```text
cron snapshot changes during request
→ optimistic UPDATE affects 0 rows
→ current lead remains sheets_synced = 0
→ next cron retries
→ Sheets eventually receives newest state
```

---

## Scenario 15 — Direct final submit before partial debounce

Status:

```text
fixed and regression-tested
```

Strong regression variant:

```text
1. open Incognito / clear localStorage
2. block */save_lead.php in DevTools
3. verify registration_draft is absent
4. fill all required fields
5. submit final form
6. verify final request has blank/no lead_id
7. verify email / SQLite / Sheets
```

Expected and verified:

```text
send.php returns success
email sent
completed SQLite lead exists with full final snapshot
Sheets receives completed lead
```

---

# Tested During Development

Verified scenarios:

```text
partial lead creation
lead_id restore after reload
merge update after reload
optional-only update
email-only update
country/messanger-only update after lead exists
partial → completed transition
protection against downgrade
completed_at preserved
SQLite → Google Sheets sync
cron synchronization
Google Apps Script upsert
visitor_id generation
visitor_id sent to save_lead.php
visitor_id sent to send.php
visitor_id stored in SQLite
dedup by visitor_id after registration_draft loss
full phone number stored with country code
frontend phone validation
frontend email validation
Telegram normalization
Telegram format/junk validation
Telegram TAKEN → final success
Telegram AVAILABLE → 422 telegram_not_found
Telegram checker disabled → final success
Telegram checker technical failure → unknown → fail-open
frontend Telegram remote error UX
Telegram error clears only on Telegram input
full final snapshot merge on completion
direct final submit without prior partial → completed snapshot persisted
GSAP unavailable → form validation / partial / submit still work
lead business logic extracted from main.min.js into lead-form.js
single localized Telegram validation block behavior
SQLite → Sheets stale snapshot race protection
```

---

# Design Decisions

## SQLite is source of truth

Google Sheets is only a view.

---

## Form submit must work without partial lead

Partial leads are an enhancement.

Final submit is an independent business process.

---

## visitor_id is internal

`visitor_id` helps dedup technical cases but is not shown to managers.

---

## No device fingerprinting

Fingerprinting is intentionally not used in MVP due to complexity and privacy risks.

---

## Telegram remote check only on final submit

Partial save never calls the remote checker.

Це захищає remote service від зайвих запитів і не робить partial capture залежним від зовнішньої availability.

---

## Telegram checker is fail-open

Technical checker failure is not evidence that Telegram account does not exist.

Therefore:

```text
unknown != not_taken
```

Only a recognized `not_taken` result blocks final submit.

---

## Frontend does not depend on Fragment vocabulary

Frontend works with internal backend reason:

```text
telegram_not_found
```

It does not compare `TAKEN`, `AVAILABLE`, `AUCTION`, `SOLD`, etc.

Так backend може змінити remote provider без зміни frontend contract.

---

## No phone/email dedup in MVP

Phone/email dedup can create false merges.

It may be added later as a separate, controlled iteration.

---

# Current Refactor / Hardening Backlog

Code audit confirmed that a large rewrite is not required. Two production-impacting issues found by the audit are already closed.

## Completed Audit Fixes

### 1. Direct final persistence — fixed and tested

```text
final submit before partial debounce
→ successful email
→ full completed snapshot persisted in SQLite
→ Sheets can receive completed lead
```

### 2. Form logic dependency on GSAP — fixed and tested

Animation code is guarded separately, while lead business logic no longer depends on GSAP availability.

Verified:

```text
GSAP blocked
→ page remains usable
→ Telegram validation works
→ intl-tel-input / lead logic initialize
→ partial/final flow remains functional
```

### 3. Frontend lead logic extraction — completed and tested

Reusable business logic moved to:

```text
js/lead-form.js
```

LP-specific animation/presentation stays in:

```text
js/main.min.js
```

This separation is the baseline for integrating the same service into additional LPs.

---

## Optional / Deferred Hardening

### Centralize lead resolution

`send.php` and storage layer still contain overlapping rules around:

```text
lead_id
visitor_id
TTL fallback
```

Possible helper:

```text
partial_leads_resolve_existing_lead(...)
```

This is a maintainability improvement, not required for the current tested flow.

### Check lead_id / visitor_id consistency

Both values are client-provided correlation identifiers. A mismatch hardening check can be added later, but it is not authentication and is intentionally deferred for the current LP rollout.

---

## Worth Refactoring

### Frontend FormData helper

Partial and final flows repeat payload preparation.

Candidate:

```text
buildLeadFormData(...)
```

It can centralize:

```text
new FormData(form)
full international phone
canonical Telegram
visitor_id
optional lead_id
```

Validation remains separate because partial and final rules differ.

---

### JSON response helper

`send.php` and `save_lead.php` repeat:

```text
http_response_code
json_encode
exit
```

A small HTTP helper is reasonable if repetition keeps growing.

Do not put HTTP response responsibilities into `lead_storage.php`.

---

### Split partial_leads_save() only where useful

`partial_leads_save()` currently combines validation, resolution, merge state and SQL.

Potential narrow helpers:

```text
prepare fields
resolve existing lead
update existing
insert new
resolve completion state
```

Do not introduce classes / repository framework / DTO layer only for style.

---

### Sheets cron error handling

Possible small hardening:

```text
check json_encode result
check curl_init result
add connect timeout
limit/sanitize logged response body
```

Do not remove the optimistic concurrency guard.

---

## Production Security Hardening

Current checker integration should be hardened before/for production operation:

```text
use HTTPS for checker endpoint
move token to environment/server config when practical
disable or minimize debug logging
restrict access to diagnostic logs
avoid unnecessary full Telegram username logging
```

The checker token must never be exposed to frontend.

---

## Leave As Is

Do not refactor these just because they look duplicated:

```text
frontend UX Telegram validation vs backend authoritative validation
fail-open behavior for remote checker
no remote check for partial leads
partial rule: new lead requires phone or email
1500 ms debounce
CASE WHEN non-empty merge behavior
completed cannot downgrade to partial
sheets_synced reset after lead change
optimistic concurrency in sync_sheets.php
messanger field name
separate partial and final validation flows
```

---

## Lower-priority / Future Ideas

```text
backend phone validation for partial save
backend email validation for partial save
exact phone dedup within 24h
email dedup with caution
spam_score
ip_hash / user_agent_hash
manual spam status
technical export with visitor_id
admin panel for SQLite leads
Google Sheets webhook secret
```

---

# Integration Checklist — Add Partial Leads to Another LP

Цей checklist є мінімальним contract для перенесення поточного сервісу на новий landing page без зміни бізнес-логіки.

## 1. Copy reusable frontend module

```text
js/lead-form.js
```

Не копіювати стару lead-логіку назад у `main.min.js`.

На новому LP `main.js` / `main.min.js` може бути повністю іншим. Він не повинен містити duplicate listeners для partial save або final submit.

## 2. Keep / adapt LEAD_FORM_CONFIG

```js
const LEAD_FORM_CONFIG = {
    formSelector: '#formHomeReg',
    partialUrl: 'save_lead.php',
    submitUrl: 'send.php',
    debounceMs: 1500,
};
```

Якщо структура URL або form id на новому LP інша — змінюється config, а не core flow.

Critical check:

```text
partial flow → LEAD_FORM_CONFIG.partialUrl
final submit → LEAD_FORM_CONFIG.submitUrl
```

Не допускати ситуації, коли обидва flow випадково використовують `partialUrl`.

## 3. Required form contract

Форма повинна мати або бути адаптована до таких field names:

```text
name
email
phone
country
messanger
```

Історичне поле `messanger` **не перейменовувати** без окремої міграції.

Hidden technical fields:

```html
<input type="hidden" id="registrationLeadId" name="lead_id" value="">
<input type="hidden" id="currentCountry" name="currentCountry" value="...">
```

`lead-form.js` також очікує:

```text
.form__status
button[type="submit"]
#phone
#email
[name="messanger"]
```

Якщо markup нового LP відрізняється — або зберегти ці selectors, або винести їх у config до інтеграції.

## 4. Telegram localized validation block

Додати один Telegram validation block, а не два independent errors:

```php
<div
    class="validate-block"
    id="telegramValidationMessage"
    data-invalid="<?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?>"
    data-not-found="<?= htmlspecialchars($local['telegram_not_found'], ENT_QUOTES, 'UTF-8'); ?>"
>
    <span><?= htmlspecialchars($local['telegram_invalid'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>
```

У кожній locale додати:

```php
'telegram_invalid'   => '...',
'telegram_not_found' => '...',
```

Translation source залишається PHP `$local`; окремий JS i18n layer не потрібен.

## 5. Script dependencies and loading

Потрібні:

```text
intl-tel-input JS
intl-tel-input CSS/assets
js/utils.js
js/lead-form.js
```

Recommended order:

```html
<script src="js/intlTelInput.js" defer></script>
<script src="js/main.min.js<?= $updateFile ?>" defer></script>
<script src="js/lead-form.js<?= $updateFile ?>" defer></script>
```

`lead-form.js` не повинен залежати від GSAP. GSAP можна підключати окремо для LP presentation.

## 6. Copy backend service files

```text
save_lead.php
send.php
lead_storage.php
telegram_input.php
telegram_checker.php
config.php
cron/sync_sheets.php
```

Також потрібен writable storage path для:

```text
data/leads.db
```

Перевірити filesystem permissions для PHP process.

## 7. Configuration

Перевірити на новому environment:

```text
DB_PATH
PARTIAL_LEAD_TTL_HOURS
GOOGLE_SHEETS_ENABLED
GOOGLE_SHEETS_WEBHOOK_URL
TELEGRAM_CHECKER_ENABLED
TELEGRAM_CHECKER_URL
TELEGRAM_CHECKER_TOKEN
TELEGRAM_CHECKER_CONNECT_TIMEOUT
TELEGRAM_CHECKER_TIMEOUT
TELEGRAM_CHECKER_DEBUG_LOG
```

Secret checker token не повинен потрапляти у frontend, repository snippets або public logs.

## 8. Email final-submit contract

`send.php` повинен:

```text
validate final data
→ remote Telegram check
→ send email
→ persist full final snapshot with status=completed
```

Не повертати систему до старого варіанта, де після successful email записувався лише Telegram/status.

## 9. Google Sheets integration

Перевірити:

```text
cron points to correct project path
Apps Script webhook is correct
A–J columns remain integration-owned
G remains messanger
manager custom columns start from K
optimistic concurrency UPDATE remains unchanged
```

Cron production cadence currently documented as:

```cron
*/10 * * * *
```

## 10. Clean-browser smoke test

Обов'язковий мінімум після інтеграції:

```text
1. Incognito / empty localStorage
2. name only → no new partial lead
3. add valid email or phone → one save_lead.php request after debounce
4. registration_draft contains only lead_id + created_at
5. visitor_id exists separately
6. invalid phone/email blocks partial/final request
7. @abc → local Telegram invalid
8. valid-looking unavailable Telegram → send.php 422 telegram_not_found
9. known real Telegram → final success
10. final success → completed row in SQLite and Sheets
11. reload/lost registration_draft + same visitor_id → same lead updated
12. block GSAP → form still works
13. verify console shows only lead-form.js lead handlers, not duplicate main.js handlers
```

## 11. Direct-final regression test

Для нового LP хоча б один раз виконати strong variant:

```text
block save_lead.php
→ fill final form
→ submit
→ email success
→ completed SQLite row still created
→ Sheets receives completed snapshot
```

## 12. Deployment sanity checks

Перед release:

```text
php -l save_lead.php
php -l send.php
php -l lead_storage.php
php -l telegram_input.php
php -l telegram_checker.php
php -l cron/sync_sheets.php
```

Після release перевірити Network, Console, SQLite і Sheets. Один user action не повинен запускати duplicate `save_lead.php` handlers із двох JS-файлів.

---

# Documentation Boundary

Цей V3 документ є **architecture + behavior + integration contract**. Він описує, що система робить, чому вона так побудована, які invariants не можна ламати і як підключити її до іншого LP.

Він навмисно не замінює operational runbook із environment-specific деталями: production paths, permissions, secret placement, cron installation, rollback, log locations, VPS checker deployment/restart і incident troubleshooting.

Для цього сервісу корисно мати окремий документ:

```text
Partial Leads Integration & Operations Runbook.md
```

---

# Current Implementation Status

As of 2026-09-14:

```text
Partial lead storage                         implemented and tested
visitor_id dedup                             implemented and tested
Telegram normalization                      implemented and tested
Telegram format/junk validation              implemented and tested
Telegram remote checker                      implemented and tested
Telegram checker toggle                      tested
localized Telegram not-found UX              implemented and tested
Telegram technical failure fail-open         tested
full final snapshot merge                     implemented and tested
Google Sheets UPSERT                         implemented
Telegram clickable link in Sheets            implemented
Sheets stale-snapshot race guard              implemented and tested
direct-final-before-debounce persistence      fixed and regression-tested
GSAP/form decoupling                          fixed and regression-tested
lead business logic in js/lead-form.js        implemented and smoke-tested
LP integration checklist                      documented
lead resolution helper                        optional refactor
lead_id/visitor_id consistency hardening      deferred / optional
HTTPS checker endpoint                        production hardening pending
```

---

# Source of Truth

Єдине джерело правди:

```text
SQLite
```

При будь-яких розбіжностях між SQLite та Google Sheets правильними вважаються дані SQLite.

Google Sheets є похідним представленням даних для менеджерів.
