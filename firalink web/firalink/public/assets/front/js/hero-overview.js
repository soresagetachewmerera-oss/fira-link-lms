(() => {
    const tray = document.querySelector('[data-hero-tray]');
    const rm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!rm && 'IntersectionObserver' in window) {
        const run = (el) => {
            if (el.dataset.counted) return;
            el.dataset.counted = '1';
            const raw = el.textContent.trim();
            const m = raw.match(/^([^\d]*)([\d,]+(?:\.(\d+))?)(.*)$/);
            if (!m) return;
            const [, pre, numStr, decPart, suf] = m;
            const target = parseFloat(numStr.replace(/,/g, ''));
            if (!isFinite(target) || !target) return;
            const dec = decPart ? decPart.length : 0, t0 = performance.now();
            const step = (now) => {
                const p = Math.min(1, (now - t0) / 480);
                const v = target * (0.7 + 0.3 * (1 - Math.pow(1 - p, 3)));
                el.textContent = p >= 1 ? raw : pre + v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf;
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        const io = new IntersectionObserver((es) => es.forEach((e) => {
            if (e.isIntersecting) { run(e.target); io.unobserve(e.target); }
        }), { threshold: 0.35 });
        document.querySelectorAll('[data-hero-countup]').forEach((el) => io.observe(el));
    }
    const url = tray && tray.dataset.overviewUrl;
    if (!url) return;
    const lang = document.documentElement.lang || 'en';
    let timer = null;
    const patch = (key, p) => {
        const el = tray.querySelector(`[data-hero-card="${key}"]`);
        if (!el || !p) return;
        const set = (s, v) => { const n = el.querySelector(s); if (n && v !== undefined) n.textContent = v; };
        set('[data-hero-value]', p.value);
        set('[data-hero-status]', p.status);
        set('[data-hero-source]', p.source);
        const u = el.querySelector('[data-hero-unit]');
        if (u && p.unit !== undefined) { u.textContent = p.unit || ''; u.hidden = !p.unit; }
        const t = el.querySelector('[data-hero-time]');
        if (t && p.asOf) { t.setAttribute('datetime', p.asOf); t.textContent = String(p.asOf).slice(0, 10); }
    };
    const refresh = async () => {
        tray.classList.add('is-refreshing');
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json', 'Accept-Language': lang } });
            if (!res.ok) return;
            const c = (await res.json())?.data?.site_hero?.cards;
            if (!c) return;
            if (c.coffee_market) {
                const cm = c.coffee_market, off = cm.status === 'unavailable';
                patch('coffee_market', { value: off ? 'Unavailable' : cm.display_value, unit: off ? '' : (cm.unit_short || ''), status: cm.status_text, source: cm.source, asOf: cm.as_of_date || cm.as_of });
            }
            if (c.verified_lots?.mode === 'count') {
                patch('verified_lots', { value: c.verified_lots.display_value, status: c.verified_lots.status_text, source: c.verified_lots.source, asOf: c.verified_lots.as_of });
            }
            if (c.landed_cost) {
                patch('landed_cost', { value: c.landed_cost.display_value, unit: c.landed_cost.unit, status: c.landed_cost.status_text, source: c.landed_cost.source, asOf: c.landed_cost.as_of });
            }
        } catch (_) {} finally { tray.classList.remove('is-refreshing'); }
    };
    const schedule = () => {
        clearTimeout(timer);
        if (document.visibilityState === 'visible') timer = setTimeout(async () => { await refresh(); schedule(); }, 300000);
    };
    document.addEventListener('visibilitychange', schedule);
    schedule();
})();
