document.addEventListener('DOMContentLoaded', function () {
    const LEAD_FORM_CONFIG = {
        formSelector: '#formHomeReg',
        partialUrl: 'save_lead.php',
        submitUrl: 'send.php',
        debounceMs: 1500,
    };

    const LOCAL_STORAGE_KEY = 'registration_draft';
    let currentLeadId = null;
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
            id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        localStorage.setItem(VISITOR_ID_KEY, id);

        return id;
    };

    visitorId = getVisitorId();
    const registrationLeadIdInput = document.querySelector('#registrationLeadId');
    const currentCountryInput = document.querySelector('#currentCountry');
    const phoneInput = document.querySelector('#phone');
    const emailInput = document.querySelector('#email');
    let phoneInputInstance = null;

    // ========================= TELEGRAM VALIDATION START =========================
    // Keep this logic in sync with lead_storage.php. Frontend is UX protection;
    // backend remains the authoritative validation layer.

    const telegramInput = document.querySelector('[name="messanger"]');

    const telegramValidationBlock = document.querySelector('#telegramValidationMessage');
    const telegramValidationText = telegramValidationBlock?.querySelector('span');
    const telegramInvalidText =
        telegramValidationBlock?.dataset.invalid ||
        '*Please enter a valid Telegram username.';

    const telegramNotFoundText =
        telegramValidationBlock?.dataset.notFound ||
        '*Please double-check that you entered the correct Telegram username.';

    let telegramRemoteErrorActive = false;

    const setTelegramValidationText = (message) => {
        if (!telegramValidationText) return;

        telegramValidationText.textContent = message;
    };

    const showTelegramRemoteError = () => {
        if (!telegramInput) return;

        telegramRemoteErrorActive = true;

        telegramInput.classList.add('form__input--invalid');

        setTelegramValidationText(telegramNotFoundText);
    };

    const clearTelegramRemoteError = () => {
        telegramRemoteErrorActive = false;

        setTelegramValidationText(telegramInvalidText);
    };

    const normalizeTelegramInput = (value) => {
        value = String(value ?? '').trim();

        if (value === '') {
            return '';
        }

        let username = null;

        if (value.startsWith('@')) {
            username = value.slice(1);
        } else {
            const match = value.match(
                /^(?:https?:\/\/)?(?:www\.)?t\.me\/([^/?#]+)\/?(?:[?#].*)?$/i
            );

            if (match) {
                username = match[1];

                const reservedPaths = new Set([
                    'joinchat',
                    'c',
                    's',
                    'addstickers',
                    'share',
                    'proxy',
                    'socks',
                    'login',
                    'iv',
                    'setlanguage',
                ]);

                if (reservedPaths.has(username.toLowerCase())) {
                    return null;
                }
            } else if (/^(?:https?:\/\/)?(?:www\.)?t\.me\//i.test(value)) {
                return null;
            } else if (value.includes('://') || value.includes('/')) {
                return null;
            } else {
                username = value;
            }
        }

        username = String(username ?? '').trim();

        if (!username || username.includes('@')) {
            return null;
        }

        return '@' + username;
    };
    // Form value must include @.
    // Telegram username itself:
    // - 5..32 chars;
    // - starts with a Latin letter;
    // - contains Latin letters, digits, underscores;
    // - does not end with underscore.
    const TELEGRAM_HANDLE_FORMAT = /^@[A-Za-z][A-Za-z0-9_]{3,30}[A-Za-z0-9]$/;

    // A single weak heuristic must not reject a lead.
    // High-confidence patterns get weight 3 and are enough on their own.
    // Weak/medium patterns get weight 1; threshold 3 reduces false positives.
    const TELEGRAM_JUNK_SCORE_THRESHOLD = 3;

    const TELEGRAM_EXACT_JUNK = new Set([
        'testtest',
        'test123',
        'test1234',
        'test12345',
        'asdf123',
        'zxcvbn123',
    ]);

    const TELEGRAM_STRONG_PATTERNS = [
        { name: 'keyboard_seq_en', regex: /qwerty|asdfgh|zxcvbn|wertyu|sdfghj|xcvbnm/i, weight: 3 },

        // Entire username is one repeated character: aaaaa, bbbbb, etc.
        // We intentionally do NOT reject any three repeated chars inside a real word.
        { name: 'full_repeated_char', regex: /^(.)\1{4,}$/, weight: 3 },

        // Entire username is a short block repeated 3+ times: ababab, abcabcabc.
        { name: 'full_repeated_block', regex: /^(.{2,4})\1{2,}$/, weight: 3 },
    ];

    const TELEGRAM_WEAK_WORDS = [
        'test', 'admin', 'user', 'telegram', 'null', 'undefined',
        'anon', 'noname', 'nobody', 'temp', 'trash', 'fake',
        'sample', 'account', 'profile', 'deleted',
    ];

    const TELEGRAM_WEAK_PATTERNS = [
        { name: 'digit_seq_asc', regex: /12345|23456|34567|45678|56789|67890/, weight: 1 },
        { name: 'digit_seq_desc', regex: /98765|87654|76543|65432|54321/, weight: 1 },
        { name: 'long_digit_run', regex: /\d{6,}/, weight: 1 },
        { name: 'consonant_run', regex: /[bcdfghjklmnpqrstvwxyz]{6,}/i, weight: 1 },
        ...TELEGRAM_WEAK_WORDS.map((word) => ({
            name: `filler_${word}`,
            // Filler only as a token/prefix, not an arbitrary substring:
            // test, test_abc, test123 -> match
            // contest, administrator, accountant -> do not match
            regex: new RegExp(`(?:^|_)${word}(?:_|\\d*$)`, 'i'),
            weight: 1,
        })),
    ];

    const checkTelegramJunk = (rawValue) => {
        const username = String(rawValue || '').replace(/^@/, '').trim();

        if (TELEGRAM_EXACT_JUNK.has(username.toLowerCase())) {
            return {
                isJunk: true,
                score: TELEGRAM_JUNK_SCORE_THRESHOLD,
                matched: ['exact_placeholder'],
            };
        }

        let score = 0;
        const matched = [];

        for (const pattern of [...TELEGRAM_STRONG_PATTERNS, ...TELEGRAM_WEAK_PATTERNS]) {
            if (pattern.regex.test(username)) {
                score += pattern.weight;
                matched.push(pattern.name);
            }
        }

        return {
            isJunk: score >= TELEGRAM_JUNK_SCORE_THRESHOLD,
            score,
            matched,
        };
    };

    const validateTelegram = ({ allowEmpty, writeBack = false }) => {
        if (!telegramInput) return true;

        const normalized = normalizeTelegramInput(telegramInput.value);

        if (normalized === null) {
            telegramInput.classList.add('form__input--invalid');

            if (!telegramRemoteErrorActive) {
                setTelegramValidationText(
                    '*Please enter a valid Telegram username.'
                );
            }

            return false;
        }

        if (normalized === '') {
            const valid = allowEmpty;

            telegramInput.classList.toggle(
                'form__input--invalid',
                !valid || telegramRemoteErrorActive
            );

            if (!telegramRemoteErrorActive) {
                setTelegramValidationText(
                    '*Please enter a valid Telegram username.'
                );
            }

            return valid;
        }

        const formatValid = TELEGRAM_HANDLE_FORMAT.test(normalized);

        const junkCheck = formatValid
            ? checkTelegramJunk(normalized)
            : { isJunk: false, score: 0, matched: [] };

        const valid = formatValid && !junkCheck.isJunk;

        telegramInput.classList.toggle(
            'form__input--invalid',
            !valid || telegramRemoteErrorActive
        );

        if (!telegramRemoteErrorActive) {
            setTelegramValidationText(
                '*Please enter a valid Telegram username.'
            );
        }

        if (valid && writeBack) {
            telegramInput.value = normalized;
        }

        if (!valid && formatValid && junkCheck.isJunk) {
            console.debug(
                'Telegram username rejected by junk filter:',
                junkCheck
            );
        }

        return valid;
    };

    const initTelegramValidationListeners = () => {
        if (!telegramInput) return;

        telegramInput.addEventListener('input', () => {
            clearTelegramRemoteError();
            validateTelegram({ allowEmpty: true });
        });

        telegramInput.addEventListener('blur', () => {
            validateTelegram({ allowEmpty: true });
        });
    };

    // ========================== TELEGRAM VALIDATION END ==========================

    const initPhoneInput = () => {
        if (phoneInputInstance || !window.intlTelInput || !phoneInput) return;

        phoneInputInstance = window.intlTelInput(phoneInput, {
            separateDialCode: true,
            initialCountry: currentCountryInput?.value || "us",
            nationalMode: false,
            utilsScript: "js/utils.js"
        });
    };

    const setContactFieldInvalid = (input, invalid) => {
        if (!input) return;

        input.classList.toggle('form__input--invalid', invalid);
    };

    const validatePhone = ({ allowEmpty }) => {
        if (!phoneInput) return true;

        const phoneValue = phoneInput.value.trim();
        const hasValue = phoneValue !== '';

        if (!hasValue) {
            setContactFieldInvalid(phoneInput, !allowEmpty);
            return allowEmpty;
        }

        const valid = phoneInputInstance
            ? phoneInputInstance.isValidNumber()
            : false;

        setContactFieldInvalid(phoneInput, !valid);
        return valid;
    };

    const validateEmail = ({ allowEmpty }) => {
        if (!emailInput) return true;

        const emailValue = emailInput.value.trim();
        const hasValue = emailValue !== '';

        if (!hasValue) {
            setContactFieldInvalid(emailInput, !allowEmpty);
            return allowEmpty;
        }

        const simpleEmailPattern = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,10}$/;
        const valid = emailInput.checkValidity() && simpleEmailPattern.test(emailValue);

        setContactFieldInvalid(emailInput, !valid);
        return valid;
    };

    const validateContactFields = ({ allowEmpty }) => {
        const phoneValid = validatePhone({ allowEmpty });
        const emailValid = validateEmail({ allowEmpty });

        return phoneValid && emailValid;
    };

    const applyFullPhoneToFormData = (formData) => {
        if (!phoneInputInstance || !phoneInputInstance.isValidNumber()) return;
        formData.set('phone', phoneInputInstance.getNumber());
    };

    const initContactValidationListeners = () => {
        if (phoneInput) {
            phoneInput.addEventListener('input', () => validatePhone({ allowEmpty: true }));
            phoneInput.addEventListener('change', () => validatePhone({ allowEmpty: true }));
            phoneInput.addEventListener('blur', () => validatePhone({ allowEmpty: true }));
        }

        if (emailInput) {
            emailInput.addEventListener('input', () => validateEmail({ allowEmpty: true }));
            emailInput.addEventListener('blur', () => validateEmail({ allowEmpty: true }));
        }
    };

    // --- Partial lead save logic ---

    // Debounce utility
    const debounce = (func, delay) => {
        let timeout;
        return function (...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    };

    const getStoredDraft = () => {
        try {
            return JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY));
        } catch (e) {
            console.error('Error parsing partial lead draft from localStorage:', e);
            localStorage.removeItem(LOCAL_STORAGE_KEY);
            return null;
        }
    };

    // Load existing draft on page load
    const loadDraft = () => {
        const draft = getStoredDraft();
        if (draft && draft.lead_id && draft.created_at) {
            const now = Date.now();
            const createdAt = parseInt(draft.created_at, 10);
            const TTL = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

            if (now - createdAt < TTL) {
                currentLeadId = draft.lead_id;
                if (registrationLeadIdInput) {
                    registrationLeadIdInput.value = currentLeadId;
                }
                console.log('Partial lead ID loaded from localStorage:', currentLeadId);
            } else {
                console.log('Partial lead draft expired. Removing from localStorage.');
                localStorage.removeItem(LOCAL_STORAGE_KEY);
            }
        } else if (draft) {
            // Invalid JSON structure, clean it up
            console.log('Invalid partial lead draft in localStorage. Removing.');
            localStorage.removeItem(LOCAL_STORAGE_KEY);
        }
    };

    // Save partial lead to backend and localStorage
    const savePartialLead = async () => {
        const form = document.querySelector(LEAD_FORM_CONFIG.formSelector)
        if (!form) return;

        const formData = new FormData(form);
        applyFullPhoneToFormData(formData);
        const phone = (formData.get('phone') || '').trim();
        const email = (formData.get('email') || '').trim();


        const contactFieldsValid = validateContactFields({ allowEmpty: true });
        const telegramValid = validateTelegram({
            allowEmpty: true,
            writeBack: true
        });

        if (!contactFieldsValid || !telegramValid) {
            return;
        }

        if (telegramInput) {
            formData.set('messanger', telegramInput.value);
        }

        // Create new lead only when phone or email exists.
        // Existing lead_id can update optional fields.
        if (!currentLeadId && !phone && !email) {
            return;
        }

        if (currentLeadId) {
            formData.set('lead_id', currentLeadId);
        }
        formData.set('visitor_id', visitorId);

        try {
            const response = await fetch(LEAD_FORM_CONFIG.partialUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success && result.lead_id) {
                currentLeadId = result.lead_id;
                const existingDraft = getStoredDraft();
                localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({
                    lead_id: currentLeadId,
                    created_at: existingDraft && existingDraft.created_at
                        ? existingDraft.created_at
                        : Date.now()
                }));
                if (registrationLeadIdInput) {
                    registrationLeadIdInput.value = currentLeadId;
                }
                console.log('Partial lead saved. ID:', currentLeadId);
            } else {
                console.error('Failed to save partial lead:', result.reason);
            }
        } catch (error) {
            console.error('Error saving partial lead:', error);
        }
    };

    // Initialize on page load
    loadDraft();
    initPhoneInput();
    initContactValidationListeners();
    initTelegramValidationListeners();

    // Attach debounced event listeners to form fields
    const formHomeReg = document.querySelector(LEAD_FORM_CONFIG.formSelector)
    if (formHomeReg) {
        const fieldsToWatch = formHomeReg.querySelectorAll('input:not([type="hidden"]), textarea, select');
        const debouncedSavePartialLead = debounce(
            savePartialLead,
            LEAD_FORM_CONFIG.debounceMs
        );

        fieldsToWatch.forEach(field => {
            field.addEventListener('input', debouncedSavePartialLead);
            field.addEventListener('change', debouncedSavePartialLead); // For select/checkboxes/radios
        });
    }

    // --- End Partial lead save logic ---


    // send form start
    const form = document.querySelector(LEAD_FORM_CONFIG.formSelector)
    const status = form.querySelector('.form__status');
    const button = form.querySelector('button[type="submit"]');

    const requiredInputs = form.querySelectorAll('input[required]:not(#phone):not(#email)');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        let isValid = true;

        requiredInputs.forEach((input) => {
            const valid = input.checkValidity();

            input.classList.toggle(
                'form__input--invalid',
                !valid
            );

            if (!valid) {
                isValid = false;
            }
        });

        const contactFieldsValid = validateContactFields({ allowEmpty: false });

        const telegramValid = validateTelegram({
            allowEmpty: false,
            writeBack: true
        });

        if (!isValid || !contactFieldsValid || !telegramValid) {
            return;
        }

        const defaultButtonText = button.textContent;

        status.textContent = 'Sending...';
        status.className = 'form__status form__status--pending';

        button.disabled = true;
        button.textContent = 'Sending...';

        try {
            const formData = new FormData(form);
            applyFullPhoneToFormData(formData);
            formData.set('visitor_id', visitorId);

            const response = await fetch(LEAD_FORM_CONFIG.submitUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            button.disabled = false;
            button.textContent = defaultButtonText;

            if (result.success) {
                clearTelegramRemoteError();

                status.textContent = 'Application sent successfully.';
                status.className = 'form__status form__status--success';

                form.reset();

                // Clear partial lead data after successful submission
                localStorage.removeItem(LOCAL_STORAGE_KEY);
                currentLeadId = null;
                if (registrationLeadIdInput) {
                    registrationLeadIdInput.value = '';
                }

                requiredInputs.forEach((input) => {
                    input.classList.remove('form__input--invalid');
                });

                validateContactFields({ allowEmpty: true });
                validateTelegram({ allowEmpty: true });

            } else {
                if (result.reason === 'telegram_not_found') {
                    showTelegramRemoteError();
                }

                status.textContent = 'Failed to send application.';
                status.className = 'form__status form__status--error';
            }

        } catch (error) {

            button.disabled = false;
            button.textContent = defaultButtonText;

            status.textContent = 'Failed to send application.';
            status.className = 'form__status form__status--error';
        }
    });

    requiredInputs.forEach((input) => {

        input.addEventListener('input', () => {

            if (input.checkValidity()) {
                input.classList.remove('form__input--invalid');
            }

        });

    });
    // send form end



});