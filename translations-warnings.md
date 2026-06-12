# Translations Warnings

## Numeric Conflicts

| Key | EN | RU | UZ | SO | Issue |
|-----|----|----|----|----|-------|
| step_3 | 5% from deposits | 5% с депозитов | 8% depozitlardan | 5% dhigaallada | Deposit percentage differs: UZ shows 8%, while EN/RU/SO and PHP template show 5%. PHP template uses **5%** (line 167 of `meta-adv-so/index.php` says `8% from deposits` but that appears to be the original template value; EN/RU/SO are coherent with 5%). |

## Removed Rows (not found in PHP template)

| Key | Reason |
|-----|--------|
| smm_banner_title | Service document header, not present in `meta-adv-so/index.php` |
| close_popup_label | Service document header, not present in `meta-adv-so/index.php` |
