/* Fira Link market watch (Market Prices page). Progressive enhancement over server-rendered links:
   - a row, range or unit link swaps the detail panel (HTML from /market-prices/panel) without a page load;
   - arrow keys move between rows; the chart has a crosshair on hover and with arrow keys;
   - checks for new prices every 15 minutes while the page is visible (the data is a daily close or slower).
   Never draws or shows a number the server did not send. */
(() => {
  'use strict';
  const page = document.querySelector('[data-market-page]');
  if (!page) return;
  const slot = document.getElementById('market-detail-slot');
  const panelUrl = page.dataset.panelUrl;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── Detail panel swap ──────────────────────────────────────────────
  async function load(href, focusTitle) {
    const url = new URL(href, location.href);
    slot.setAttribute('aria-busy', 'true');
    try {
      const res = await fetch(panelUrl + url.search, { headers: { Accept: 'text/html' } });
      if (!res.ok) throw new Error(String(res.status));
      slot.innerHTML = await res.text();
      history.replaceState(null, '', url.pathname + url.search);
      const key = url.searchParams.get('market');
      page.querySelectorAll('.fira-mw__hit').forEach((a) => {
        const on = a.dataset.market === key;
        a.toggleAttribute('aria-current', on);
        if (on) a.setAttribute('aria-current', 'true');
        a.closest('.fira-mw__row').classList.toggle('is-current', on);
      });
      initChart();
      if (focusTitle) {
        const title = document.getElementById('fira-mwd-title');
        title.setAttribute('tabindex', '-1');
        title.focus({ preventScroll: reduced });
        if (window.innerWidth < 1024) title.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
      }
    } catch (e) {
      notice(page.dataset.textFailed);
    } finally {
      slot.removeAttribute('aria-busy');
    }
  }

  page.addEventListener('click', (e) => {
    const link = e.target.closest('a.fira-mw__hit, a.fira-mwd__chipbtn');
    if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
    e.preventDefault();
    load(link.href, link.classList.contains('fira-mw__hit'));
  });

  // Arrow keys move between rows (links stay in the normal tab order too).
  page.addEventListener('keydown', (e) => {
    const link = e.target.closest('.fira-mw__hit');
    if (!link || !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) return;
    const links = [...page.querySelectorAll('.fira-mw__hit')];
    const i = links.indexOf(link);
    const next = { ArrowDown: i + 1, ArrowUp: i - 1, Home: 0, End: links.length - 1 }[e.key];
    if (links[next]) {
      e.preventDefault();
      links[next].focus();
    }
  });

  // ── Chart crosshair ────────────────────────────────────────────────
  function initChart() {
    const fig = slot.querySelector('.fira-mwd__chart');
    if (!fig) return;
    let pts;
    try { pts = JSON.parse(fig.dataset.points || '[]'); } catch (e) { return; }
    if (pts.length < 2) return;
    const dec = parseInt(fig.dataset.decimals || '2', 10);
    const ref = parseFloat(fig.dataset.ref);
    const vals = pts.map((p) => p[1]).concat(Number.isFinite(ref) ? [ref] : []);
    const lo = Math.min(...vals), span = (Math.max(...vals) - lo) || 0;
    const W = 600, H = 220, P = 8;
    const xAt = (i) => P + (i / (pts.length - 1)) * (W - 2 * P);
    const yAt = (v) => (span > 0 ? P + (1 - (v - lo) / span) * (H - 2 * P) : H / 2);
    const cross = fig.querySelector('.fira-mwd__cross');
    const line = fig.querySelector('.fira-mwd__cross-line');
    const dot = fig.querySelector('.fira-mwd__dot');
    const tip = fig.querySelector('.fira-mwd__tip');
    const readout = slot.querySelector('[data-readout]');
    const fmt = (v) => v.toLocaleString('en', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    let index = pts.length - 1;

    function show(i, announce) {
      index = Math.max(0, Math.min(pts.length - 1, i));
      const x = xAt(index), y = yAt(pts[index][1]);
      line.setAttribute('x1', x); line.setAttribute('x2', x);
      cross.hidden = false; dot.hidden = false; tip.hidden = false;
      dot.style.left = (x / W * 100) + '%';
      dot.style.top = (y / H * 100) + '%';
      tip.textContent = pts[index][0] + ' · ' + fmt(pts[index][1]);
      tip.style.left = Math.min(80, Math.max(20, x / W * 100)) + '%';
      if (announce && readout) readout.textContent = tip.textContent;
    }
    function hide() { cross.hidden = true; dot.hidden = true; tip.hidden = true; }

    fig.addEventListener('pointermove', (e) => {
      const r = fig.getBoundingClientRect();
      const fx = (e.clientX - r.left) / r.width * W;
      show(Math.round((fx - P) / (W - 2 * P) * (pts.length - 1)), false);
    });
    fig.addEventListener('pointerleave', hide);
    fig.addEventListener('blur', hide);
    fig.addEventListener('keydown', (e) => {
      const step = { ArrowLeft: -1, ArrowRight: 1 }[e.key];
      if (step) { e.preventDefault(); show(index + step, true); }
      else if (e.key === 'Home') { e.preventDefault(); show(0, true); }
      else if (e.key === 'End') { e.preventDefault(); show(pts.length - 1, true); }
      else if (e.key === 'Escape') hide();
    });
  }
  initChart();

  // ── Check for new prices (15 minutes, only while visible) ─────────
  const notice = (text) => {
    const box = document.getElementById('market-notice');
    if (!box || !text) return;
    document.getElementById('market-notice-text').textContent = text;
    box.classList.add('is-visible');
  };
  let lastGood = new Date();
  async function check() {
    if (document.visibilityState !== 'visible') return;
    try {
      const res = await fetch(page.dataset.watchUrl, { headers: { Accept: 'application/json', 'Accept-Language': document.documentElement.lang || 'en' } });
      if (!res.ok) throw new Error(String(res.status));
      const body = await res.json();
      lastGood = new Date();
      const now = JSON.stringify((body.data.rows || []).map((r) => [r.key, r.price, r.status]));
      if (now !== JSON.stringify(JSON.parse(page.dataset.signature || '[]'))) location.reload();
    } catch (e) {
      const p = (n) => String(n).padStart(2, '0');
      notice((page.dataset.textRefreshFailed || '').replace('{time}', `${lastGood.getFullYear()}-${p(lastGood.getMonth() + 1)}-${p(lastGood.getDate())} ${p(lastGood.getHours())}:${p(lastGood.getMinutes())}`));
    }
  }
  setInterval(check, 15 * 60 * 1000);
})();
