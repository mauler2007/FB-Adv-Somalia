<?php

/**
 * Normalize Telegram contact input to canonical @username form.
 *
 * Accepted:
 *   username
 *   @username
 *   t.me/username
 *   http://t.me/username
 *   https://t.me/username
 *   https://www.t.me/username
 *
 * Returns:
 *   ''     for empty input
 *   null   for unsupported URL/path shape
 *   string canonical @username otherwise
 *
 * IMPORTANT:
 * This function only normalizes the input shape.
 * Existing Telegram format + junk validation must run after it.
 */
function normalizeTelegramInput($value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    $username = null;

    // @username
    if (substr($value, 0, 1) === '@') {
        $username = substr($value, 1);
    } else {
        // t.me/username
        // http://t.me/username
        // https://t.me/username
        // https://www.t.me/username/
        // Optional query/fragment is ignored: https://t.me/username?direct
        if (preg_match(
            '~^(?:https?://)?(?:www\\.)?t\\.me/([^/?#]+)/?(?:[?#].*)?$~i',
            $value,
            $matches
        ) === 1) {
            $username = $matches[1];

            // Telegram service routes are not profile usernames.
            $reservedPaths = [
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
            ];

            if (in_array(strtolower($username), $reservedPaths, true)) {
                return null;
            }
        }
        // It looks like t.me, but it is not a simple one-segment profile URL.
        elseif (preg_match('~^(?:https?://)?(?:www\\.)?t\\.me/~i', $value) === 1) {
            return null;
        }
        // Do not treat foreign URLs or arbitrary paths as usernames.
        elseif (strpos($value, '://') !== false || strpos($value, '/') !== false) {
            return null;
        }
        // Bare username.
        else {
            $username = $value;
        }
    }

    $username = trim((string) $username);

    if ($username === '' || strpos($username, '@') !== false) {
        return null;
    }

    return '@' . $username;
}
