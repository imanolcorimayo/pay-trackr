<?php
// FX rate caching + lookup. Used by accounts.php to convert balances to ARS.
//
// Source: dolarapi.com (free, no auth). Per-currency endpoint mapping:
//   USD  -> /v1/dolares/blue
//   USDT -> /v1/dolares/cripto
// We use the average of compra+venta as a single rate-to-ARS for valuation
// (closer to mid-market than either side, stable enough for daily refresh).
//
// TTL: 24 hours. If a fetch fails and we have any cached value, fall back to
// it instead of erroring -- dashboards keep working through transient outages.

const FX_TTL_SECONDS = 86400;
const FX_SOURCES = [
    'USD'  => ['url' => 'https://dolarapi.com/v1/dolares/blue',   'source' => 'blue'],
    'USDT' => ['url' => 'https://dolarapi.com/v1/dolares/cripto', 'source' => 'cripto'],
];

/**
 * Returns rate-to-ARS for the given currency. ARS is always 1.0. For other
 * currencies, refreshes the cache from dolarapi.com if stale; falls back to
 * the cached value on fetch failure.
 */
function fx_get_rate(PDO $pdo, string $currency): float {
    if ($currency === 'ARS') return 1.0;
    if (!isset(FX_SOURCES[$currency])) return 1.0;

    $stmt = $pdo->prepare(
        "SELECT rate_to_ars, UNIX_TIMESTAMP(fetched_ts) AS ts
         FROM fx_rate WHERE currency_code = ?"
    );
    $stmt->execute([$currency]);
    $cached = $stmt->fetch();

    $stale = !$cached || (time() - (int)$cached['ts']) > FX_TTL_SECONDS;
    if ($stale) {
        $fresh = fx_fetch_remote($currency);
        if ($fresh !== null) {
            $upsert = $pdo->prepare(
                "INSERT INTO fx_rate (currency_code, rate_to_ars, source)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE rate_to_ars = VALUES(rate_to_ars), source = VALUES(source), fetched_ts = NOW()"
            );
            $upsert->execute([$currency, $fresh, FX_SOURCES[$currency]['source']]);
            return $fresh;
        }
        if (!$cached) return 1.0;
    }
    return (float) $cached['rate_to_ars'];
}

/**
 * Hits dolarapi.com for the given currency. Returns the avg(compra, venta)
 * as a float, or null if anything went wrong.
 */
function fx_fetch_remote(string $currency): ?float {
    $conf = FX_SOURCES[$currency] ?? null;
    if (!$conf) return null;

    $ch = curl_init($conf['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'mangos-fx/1.0',
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status !== 200) {
        error_log("[fx] $currency fetch failed: status=$status");
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['compra'], $data['venta'])) {
        error_log("[fx] $currency unexpected response: " . substr($body, 0, 200));
        return null;
    }
    $compra = (float)$data['compra'];
    $venta  = (float)$data['venta'];
    if ($compra <= 0 || $venta <= 0) return null;
    return ($compra + $venta) / 2.0;
}

/**
 * Returns a {USD: rate, USDT: rate, ARS: 1} dict.
 */
function fx_all_rates(PDO $pdo): array {
    $rates = ['ARS' => 1.0];
    foreach (array_keys(FX_SOURCES) as $code) {
        $rates[$code] = fx_get_rate($pdo, $code);
    }
    return $rates;
}

// Endpoint dispatch fires only when index.php sets $fx_action — otherwise this
// file is just a helper module and shouldn't terminate the request.
if (($fx_action ?? null) === 'rates') {
    if (method() !== 'GET') json_error('Method not allowed', 405);

    $rates = fx_all_rates($pdo);
    $stmt = $pdo->query("SELECT currency_code, fetched_ts FROM fx_rate");
    $ts_by_code = [];
    foreach ($stmt->fetchAll() as $row) {
        $ts_by_code[$row['currency_code']] = $row['fetched_ts'];
    }

    json_response([
        'rates' => $rates,
        'fetched_ts' => $ts_by_code,
        'sources' => array_combine(
            array_keys(FX_SOURCES),
            array_column(FX_SOURCES, 'source')
        ),
    ]);
}
