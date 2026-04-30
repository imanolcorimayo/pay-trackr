/**
 * Sum-on-input enhancer for amount fields.
 *
 *   amountInput.attach(inputEl)   — wire one input (use for dynamic inputs)
 *   amountInput.autoAttach(root)  — scan for [data-amount] and wire all
 *
 * Behavior: typing "1500+800+200" resolves to "2300" on blur or Enter. While
 * typing, a small "= 2300" hint appears below the input (only when the value
 * contains 2+ segments separated by "+"). Resolution dispatches `input` and
 * `change` events so existing listeners pick up the new value.
 *
 * Number format follows the app convention: dots are thousand separators,
 * comma is the decimal mark (es-AR). Mixed inputs like "1.500+800,5" work.
 */
window.amountInput = (function () {
    function parseSegment(s) {
        const t = String(s || '').trim();
        if (!t) return NaN;
        const norm = t.replace(/\./g, '').replace(',', '.');
        const n = parseFloat(norm);
        return isFinite(n) ? n : NaN;
    }

    function splitSegments(raw) {
        return String(raw || '').split('+').map(s => s.trim()).filter(s => s.length > 0);
    }

    // Returns NaN unless there are 2+ valid segments. Single-number values are
    // not "sum expressions" — leave them alone so the user's input stays as-is.
    function evaluateSum(raw) {
        const parts = splitSegments(raw);
        if (parts.length < 2) return NaN;
        let sum = 0;
        for (const p of parts) {
            const n = parseSegment(p);
            if (!isFinite(n)) return NaN;
            sum += n;
        }
        return sum;
    }

    function formatSum(n) {
        const hasFraction = Math.abs(n - Math.round(n)) > 0.005;
        return n.toLocaleString('es-AR', {
            minimumFractionDigits: hasFraction ? 2 : 0,
            maximumFractionDigits: 2,
        });
    }

    function ensureHint(input) {
        if (input._sumHint) return input._sumHint;
        const hint = document.createElement('span');
        hint.className = 'amount-sum-hint pointer-events-none absolute text-[11px] text-muted bg-white/95 px-1 rounded';
        hint.style.display = 'none';
        // Anchor the hint to the input's offset parent: use absolute position
        // and align with the input's bottom-right corner via JS each render.
        // Falls back to inline-after-input if positioning fails.
        const parent = input.offsetParent || input.parentElement;
        if (parent) {
            // Make sure the parent can host an absolutely positioned child.
            const cs = window.getComputedStyle(parent);
            if (cs.position === 'static') parent.style.position = 'relative';
            parent.appendChild(hint);
        } else if (input.parentNode) {
            input.parentNode.insertBefore(hint, input.nextSibling);
        }
        input._sumHint = hint;
        return hint;
    }

    function positionHint(input, hint) {
        if (!hint || hint.style.display === 'none') return;
        const parent = hint.parentElement;
        if (!parent) return;
        const ir = input.getBoundingClientRect();
        const pr = parent.getBoundingClientRect();
        hint.style.top = (ir.bottom - pr.top + 2) + 'px';
        hint.style.right = Math.max(0, pr.right - ir.right) + 'px';
    }

    function updateHint(input) {
        const sum = evaluateSum(input.value);
        const hint = ensureHint(input);
        if (isFinite(sum)) {
            hint.textContent = '= ' + formatSum(sum);
            hint.style.display = '';
            positionHint(input, hint);
        } else {
            hint.style.display = 'none';
        }
    }

    function resolve(input) {
        const sum = evaluateSum(input.value);
        if (!isFinite(sum)) return;
        input.value = formatSum(sum);
        if (input._sumHint) input._sumHint.style.display = 'none';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function attach(input) {
        if (!input || input._amountAttached) return;
        input._amountAttached = true;
        input.addEventListener('input', () => updateHint(input));
        input.addEventListener('blur', () => {
            resolve(input);
            if (input._sumHint) input._sumHint.style.display = 'none';
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                resolve(input);
            }
        });
        // Best-effort cleanup: if the input is removed from DOM, drop the hint too.
        input.addEventListener('focus', () => updateHint(input));
    }

    function autoAttach(root) {
        (root || document).querySelectorAll('input[data-amount]').forEach(attach);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => autoAttach());
    } else {
        autoAttach();
    }

    return { attach, autoAttach, evaluateSum };
})();
