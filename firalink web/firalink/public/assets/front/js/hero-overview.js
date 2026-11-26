(() => {
    const tray = document.querySelector('[data-hero-tray]');
    const url = tray && tray.dataset.overviewUrl;
    if (!url) return;
    const lang = document.documentElement.lang || 'en';
    let timer = null;

    const patchCard = (key, p) => {
        const el = tray.querySelector(`[data-hero-card="${key}"]`);
        if (!el || !p) return;
        const set = (sel, txt) => { const n = el.querySelector(sel); if (n && txt !== undefined) n.textContent = txt; };
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
                patchCard('coffee_market', { value: off ? 'Unavailable' : cm.display_value, unit: off ? '' : (cm.unit_short || ''), status: cm.status_text, source: cm.source, asOf: cm.as_of_date || cm.as_of });
            }
            if (c.verified_lots?.mode === 'count') {
                patchCard('verified_lots', { value: c.verified_lots.display_value, status: c.verified_lots.status_text, source: c.verified_lots.source, asOf: c.verified_lots.as_of });
            }
            if (c.landed_cost) {
                patchCard('landed_cost', { value: c.landed_cost.display_value, unit: c.landed_cost.unit, status: c.landed_cost.status_text, source: c.landed_cost.source, asOf: c.landed_cost.as_of });
            }
        } catch (_) {} finally {
            tray.classList.remove('is-refreshing');
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        if (document.visibilityState === 'visible') timer = setTimeout(async () => { await refresh(); schedule(); }, 300000);
    };
    document.addEventListener('visibilitychange', schedule);
    schedule();
})();
