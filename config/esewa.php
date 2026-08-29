<?php

/**
 * eSewa ePay v2 configuration.
 *
 * The values below under "sandbox" are eSewa's own public UAT test
 * credentials (documented at https://developer.esewa.com.np) — they are
 * meant for testing and are safe to keep as-is while you develop.
 *
 * BEFORE GOING LIVE:
 *   1. Get your real merchant (product) code + secret key from eSewa.
 *   2. Set ESEWA_ENV to "production" below.
 *   3. Put the real credentials in the "production" branch (ideally read
 *      from environment variables instead of hardcoding them here).
 */

define("ESEWA_ENV", "sandbox"); // "sandbox" | "production"

if (ESEWA_ENV === "production") {
    define("ESEWA_PRODUCT_CODE", "YOUR_LIVE_MERCHANT_CODE");
    define("ESEWA_SECRET_KEY", "YOUR_LIVE_SECRET_KEY");
    define("ESEWA_FORM_URL", "https://epay.esewa.com.np/api/epay/main/v2/form");
    define("ESEWA_STATUS_URL", "https://epay.esewa.com.np/api/epay/transaction/status/");
} else {
    define("ESEWA_PRODUCT_CODE", "EPAYTEST");
    define("ESEWA_SECRET_KEY", "8gBm/:&EnhH.1/q");
    define("ESEWA_FORM_URL", "https://rc-epay.esewa.com.np/api/epay/main/v2/form");
    define("ESEWA_STATUS_URL", "https://rc.esewa.com.np/api/epay/transaction/status/");
}

/**
 * Build the base64 HMAC-SHA256 signature eSewa requires.
 *
 * eSewa signs a comma-separated "name=value" string built from a specific
 * field order (given by $signedFieldNames, itself a comma-separated list
 * of field names). The order matters — it must match exactly.
 *
 * @param array  $fields            Associative array containing at least
 *                                   the keys listed in $signedFieldNames.
 * @param string $signedFieldNames  e.g. "total_amount,transaction_uuid,product_code"
 */
function esewaSign(array $fields, string $signedFieldNames): string
{
    $parts = [];
    foreach (explode(",", $signedFieldNames) as $name) {
        $parts[] = $name . "=" . ($fields[$name] ?? "");
    }
    $message = implode(",", $parts);

    return base64_encode(hash_hmac("sha256", $message, ESEWA_SECRET_KEY, true));
}

/**
 * Small GET helper (cURL if available, falls back to file_get_contents)
 * used to call eSewa's transaction status-check API.
 */
function esewaHttpGet(string $url): ?array
{
    if (function_exists("curl_init")) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $ok = $response !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
    } else {
        $response = @file_get_contents($url);
        $ok = $response !== false;
    }

    if (!$ok || !$response) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}
