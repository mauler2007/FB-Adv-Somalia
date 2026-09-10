# Partial Leads + SQLite + Google Sheets Architecture

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

```text
User Form
   ↓
Frontend validation
   ↓
save_lead.php
   ↓
SQLite leads.db
   ↓
cron/sync_sheets.php
   ↓
Google Apps Script
   ↓
Google Sheets
```

Форма не пише напряму в Google Sheets.

Це важливо, бо Google Sheets / Apps Script можуть бути тимчасово недоступні, але заявка користувача не повинна втрачатися.

---

# Components

## Frontend

Відповідає за:

* debounce partial save;
* frontend-валідацію телефону та email;
* створення `visitor_id`;
* збереження `lead_id` у `localStorage`;
* передачу `lead_id` та `visitor_id` у backend;
* фінальний submit.

Основний JS-файл:

```text
js/main.min.js
```

Назва `main.min.js` історична. Файл може бути не мінімізований.

---

## Backend

Основні файли:

```text
save_lead.php
send.php
lead_storage.php
config.php
```

Призначення:

```text
save_lead.php     → partial save
send.php          → final submit
lead_storage.php  → SQLite storage layer
config.php        → конфігурація
```

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
```

Якщо phone або email невалідні:

```text
send.php request is not sent
```

---

# Completed Lead

Після натискання `Submit`:

```text
send.php
```

обробляє заявку як і раніше:

```text
validation
email send
mark completed
```

Після успішної відправки email:

```text
status = completed
completed_at = timestamp
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
Email sent
 ↓
partial_leads_mark_completed(lead_id)
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
partial_leads_mark_completed(found lead_id)
```

Це закриває кейс втрати `registration_draft`.

---

## Submit Without lead_id And Without Matching visitor_id

Якщо не знайдено ні `lead_id`, ні актуальний lead по `visitor_id`, submit все одно працює.

```text
Email sent
Success response
No existing SQLite lead marked completed
```

Це design decision.

Фінальна заявка не повинна залежати від partial leads.

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

## No phone/email dedup in MVP

Phone/email dedup can create false merges.

It may be added later as a separate, controlled iteration.

---

# Future Improvements

Possible next iterations:

```text
backend phone validation
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

# Source of Truth

Єдине джерело правди:

```text
SQLite
```

При будь-яких розбіжностях між SQLite та Google Sheets правильними вважаються дані SQLite.

Google Sheets є похідним представленням даних для менеджерів.
