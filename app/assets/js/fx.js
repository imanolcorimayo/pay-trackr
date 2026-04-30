/**
 * Shared FX helper. Loads rates once from /api/fx/rates, exposes:
 *   - fx.ready          → Promise that resolves when rates are loaded.
 *   - fx.rateFor(cur)   → number (rate-to-ARS); ARS and unknowns return 1.
 *   - fx.toArs(amt, c)  → ARS-equivalent of `amt` in currency `c`.
 *   - fx.rates          → { ARS: 1, USD: …, USDT: … }.
 *   - fx.fetchedTs      → { USD: 'YYYY-MM-DD HH:MM:SS', … }.
 *   - fx.relativeAge()  → human "hace 4 h" string for the freshest rate.
 *
 * Caching is process-wide for the page; refresh on reload. The /api/fx/rates
 * endpoint already serves a daily-cached value, so we don't need extra layers.
 */
window.fx = (function () {
    let rates = { ARS: 1 };
    let fetchedTs = {};
    let loadPromise = null;

    function load() {
        if (loadPromise) return loadPromise;
        loadPromise = api.get('/fx-rates')
            .then(r => {
                if (r && r.rates) {
                    rates = Object.assign({ ARS: 1 }, r.rates);
                    fetchedTs = r.fetched_ts || {};
                }
                return rates;
            })
            .catch(() => rates);
        return loadPromise;
    }

    function rateFor(currency) {
        if (!currency) return 1;
        return Number(rates[currency]) || 1;
    }

    function toArs(amount, currency) {
        return Number(amount) * rateFor(currency);
    }

    // Cross-currency: amount in `from` → amount in `to`, routed through ARS.
    // Returns NaN when either rate is missing/zero so callers can show a hint.
    function convert(amount, from, to) {
        if (from === to) return Number(amount);
        const fromRate = rateFor(from);
        const toRate = rateFor(to);
        if (!toRate) return NaN;
        return (Number(amount) * fromRate) / toRate;
    }

    // "hace 4 h" for the most recent fetch across all non-ARS rates. Empty
    // string when no rates have ever been fetched.
    function relativeAge() {
        const stamps = Object.values(fetchedTs)
            .map(s => new Date(s.replace ? s.replace(' ', 'T') : s).getTime())
            .filter(Number.isFinite);
        if (stamps.length === 0) return '';
        const ageMs = Date.now() - Math.max(...stamps);
        const mins = Math.floor(ageMs / 60000);
        if (mins < 1) return 'recién';
        if (mins < 60) return `hace ${mins} min`;
        const hours = Math.floor(mins / 60);
        if (hours < 24) return `hace ${hours} h`;
        const days = Math.floor(hours / 24);
        return `hace ${days} d`;
    }

    return {
        load,
        rateFor,
        toArs,
        convert,
        relativeAge,
        get rates() { return rates; },
        get fetchedTs() { return fetchedTs; },
        get ready() { return load(); },
    };
})();
