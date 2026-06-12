## Project-specific context — Partially Completed Forms MVP

Current landing is a PHP landing, not Nuxt for this task.

Existing files:

* `index.php` — landing page with form
* `send.php` — existing final form handler
* `data/` — already created
* hidden input `lead_id` already exists
* real form id: `formHomeReg`

Planned files:

* `config.php`
* `lead_storage.php`
* `save_lead.php`
* `data/.htaccess`
* `data/leads.db` created automatically

Implementation rules:

* Use POST only, no PUT.
* SQLite is the source of truth.
* Do not implement admin.php in MVP.
* Do not implement Google Sheets sync in MVP, only prepare DB fields.
* Do not implement Telegram sending in MVP, only prepare config and DB fields.
* Do not use `notified` as a status.
* Use `status`: partial / completed / spam.
* Use `tg_sent`, `tg_sent_at`.
* Keep typo `messanger`, do not rename.
* Do not break existing `send.php` behavior.
* `send.php` should only receive a safe completion update later, after successful existing send.


## Default Mode

READ ONLY

Never modify files without explicit approval.

Approval keywords:

* APPROVED
* Apply changes
* Можна вносити зміни

Without one of these phrases:

* do not edit files
* do not create files
* do not delete files
* do not generate patches
* do not save plans
* do not modify project state

## Workflow

For every task:

1. Analyze code.
2. Explain findings.
3. Identify risks.
4. Propose solution.
5. Show files that would change.
6. Wait for approval.

## Architecture Reviews

When reviewing architecture:

* prefer analysis over implementation
* challenge assumptions
* identify tradeoffs
* do not rewrite code unless requested

## Strict Read-Only Rules

Read-only means:

* reading files
* searching files
* analyzing code
* explaining findings
* proposing solutions in chat

Read-only does NOT allow:

* creating files
* modifying files
* deleting files
* generating patches
* saving plans
* updating TODO lists
* updating documentation
* creating temporary files
* creating .kilo files
* creating .md files
* creating .patch files
* creating .diff files

Do not create or modify:

* .kilo/*
* plans/*
* docs/*
* README.md
* CHANGELOG.md
* TODO.md

unless explicitly approved.

## Command Restrictions

Without approval do NOT execute commands that modify:

* files
* dependencies
* git state
* formatting
* generated output

Examples:

* npm install
* pnpm install
* yarn install
* npm update
* pnpm update
* prettier --write
* eslint --fix
* git checkout
* git reset
* git clean
* git commit
* git push

## Before Editing

Always output:

Files to modify:

* file1
* file2

Reason:
...

Risk:
...

Then wait for approval.

Plans must be written in chat only.

Never save plans to files.
