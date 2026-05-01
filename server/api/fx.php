<?php
// FX rate cache + historical series. Used by accounts.php to convert balances
// to ARS, and by the analytics YoY widget to convert historical pesos into
// "pesos de hoy" via USD-anchoring.
//
// Schema: fx_rate is a time-series — one row per (currency_code, date). The
// "current rate" is just the most recent row. Daily history is filled by
// server/scripts/backfill-fx-history.php (argentinadatos.com, blue series
// since 2011). The live cache is also kept fresh via dolarapi.com on demand:
// if today's row is missing or older than the TTL, fx_get_rate() refreshes.
//
// Sources:
//   USD  → live: dolarapi.com/v1/dolares/blue   · history: argentinadatos.com
//   USDT → live: dolarapi.com/v1/dolares/cripto · history: not backfilled yet
// Rate-to-ARS = average(compra, venta) for both — closer to mid-market.

const FX_TTL_SECONDS = 86400;
const FX_SOURCES = [
    'USD'  => ['url' => 'https://dolarapi.com/v1/dolares/blue',   'source' => 'blue'],
    'USDT' => ['url' => 'https://dolarapi.com/v1/dolares/cripto', 'source' => 'cripto'],
];

/**
 * Returns the latest rate-to-ARS for the given currency. ARS is always 1.0.
 * For other currencies, refreshes the cache from dolarapi.com if the most
 * recent row is older than the TTL; falls back to the cached value on fetch
 * failure.
 */
function fx_get_rate(PDO $pdo, string $currency): float {
    if ($currency === 'ARS') return 1.0;
    if (!isset(FX_SOURCES[$currency])) return 1.0;

    $stmt = $pdo->prepare(
        "SELECT rate_to_ars, UNIX_TIMESTAMP(fetched_ts) AS ts
         FROM fx_rate WHERE currency_code = ?
         ORDER BY `date` DESC LIMIT 1"
    );
    $stmt->execute([$currency]);
    $cached = $stmt->fetch();

    $stale = !$cached || (time() - (int)$cached['ts']) > FX_TTL_SECONDS;
    if ($stale) {
        $fresh = fx_fetch_remote($currency);
        if ($fresh !== null) {
            $upsert = $pdo->prepare(
                "INSERT INTO fx_rate (currency_code, `date`, rate_to_ars, source)
                 VALUES (?, CURDATE(), ?, ?)
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
 * Historical rate lookup. Returns the rate-to-ARS for the given currency on
 * `$date` ('YYYY-MM-DD'). If that exact date is missing (weekends, holidays,
 * gaps) we fall back to the nearest preceding date — which is the natural
 * meaning of "the rate that was in effect on that day".
 *
 * Returns 1.0 for ARS, and falls through to fx_get_rate() (latest) when no
 * historical row exists at or before the date.
 */
function fx_get_rate_for_date(PDO $pdo, string $currency, string $date): float {
    if ($currency === 'ARS') return 1.0;
    if (!isset(FX_SOURCES[$currency])) return 1.0;

    $stmt = $pdo->prepare(
        "SELECT rate_to_ars FROM fx_rate
         WHERE currency_code = ? AND `date` <= ?
         ORDER BY `date` DESC LIMIT 1"
    );
    $stmt->execute([$currency, $date]);
    $row = $stmt->fetch();

    if ($row) return (float) $row['rate_to_ars'];
    return fx_get_rate($pdo, $currency);
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
if (($fx_action ?? null) === 'history') {
    if (method() !== 'GET') json_error('Method not allowed', 405);

    $currency = $_GET['currency'] ?? 'USD';
    if (!isset(FX_SOURCES[$currency])) json_error('unsupported currency');

    $start = $_GET['start'] ?? '';
    $end   = $_GET['end']   ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) json_error('start must be YYYY-MM-DD');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   json_error('end must be YYYY-MM-DD');

    // Pull every row in range. Frontends handle weekend/holiday gaps by
    // walking backward to the nearest preceding rate (same semantics as
    // fx_get_rate_for_date on the server).
    $stmt = $pdo->prepare(
        "SELECT `date`, rate_to_ars FROM fx_rate
         WHERE currency_code = ? AND `date` BETWEEN ? AND ?
         ORDER BY `date`"
    );
    $stmt->execute([$currency, $start, $end]);

    $rates = [];
    foreach ($stmt->fetchAll() as $row) {
        $rates[$row['date']] = (float)$row['rate_to_ars'];
    }

    // Edge-fill: include the rate for the day immediately before `start`
    // when the range begins on a gap (weekend/holiday). Frontend then has
    // one safe anchor to fall back to without an extra request.
    if (!isset($rates[$start])) {
        $stmt = $pdo->prepare(
            "SELECT `date`, rate_to_ars FROM fx_rate
             WHERE currency_code = ? AND `date` < ?
             ORDER BY `date` DESC LIMIT 1"
        );
        $stmt->execute([$currency, $start]);
        $row = $stmt->fetch();
        if ($row) $rates[$row['date']] = (float)$row['rate_to_ars'];
    }

    json_response([
        'currency' => $currency,
        'start'    => $start,
        'end'      => $end,
        'rates'    => $rates,
    ]);
}

if (($fx_action ?? null) === 'rates') {
    if (method() !== 'GET') json_error('Method not allowed', 405);

    $rates = fx_all_rates($pdo);
    // Latest fetched_ts per currency — fx_rate is time-series now, so we
    // need MAX(fetched_ts) grouped by code, not a flat scan.
    $stmt = $pdo->query("SELECT currency_code, MAX(fetched_ts) AS fetched_ts FROM fx_rate GROUP BY currency_code");
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
