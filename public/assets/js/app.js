/* ==========================================================================
   SignalAtlas — Global UI behaviours
   ========================================================================== */
(() => {
    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => [...el.querySelectorAll(s)];
    const csrf = () => $('meta[name="csrf-token"]')?.content;

    window.SA = window.SA || {};
    SA.$ = $; SA.$$ = $$;
    SA.fmt = (v, d = 0) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }).format(v ?? 0);
    SA.colors = { mint: '#60e1bd', blue: '#76a9ff', violet: '#b99aff', yellow: '#f2cc72', red: '#ff8f83', orange: '#ffb27a', cyan: '#67c9e8', pink: '#f59ac8' };
    SA.series = Object.values(SA.colors);
    SA.levelColor = { low: '#60e1bd', medium: '#f2cc72', high: '#ffb27a', critical: '#ff8f83' };

    /* ---------- Leaflet base layers (100% gratis tanpa API key) ---------- */
    SA.initMap = (el, center = [5, 112], zoom = 4, extra = {}) => {
        const map = L.map(el, { zoomControl: true, ...extra }).setView(center, zoom);
        const dark = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            className: 'osm-dark',
            maxZoom: 19,
        }).addTo(map);
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri · Maxar · Earthstar Geographics',
            maxZoom: 18,
        });
        const light = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19,
        });
        L.control.layers({ '🌙 Peta Gelap': dark, '🛰️ Satelit': satellite, '☀️ Peta Terang': light }, null, { position: 'bottomright' }).addTo(map);
        return map;
    };

    /* ---------- Icons ---------- */
    SA.icons = () => window.lucide && lucide.createIcons();

    /* ---------- Fetch helper ---------- */
    SA.request = async (url, options = {}) => {
        const res = await fetch(url, {
            ...options,
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw Object.assign(new Error(data.message || 'Terjadi kesalahan'), { data, status: res.status });
        return data;
    };

    /* ---------- Toasts ---------- */
    const toastIcons = { success: 'check-circle-2', danger: 'alert-octagon', warning: 'alert-triangle', info: 'info' };
    SA.toast = (message, tone = 'info') => {
        let box = $('.toasts');
        if (!box) { box = document.createElement('div'); box.className = 'toasts'; document.body.append(box); }
        const el = document.createElement('div');
        el.className = `toast ${tone}`;
        el.innerHTML = `<span class="t-icon"><i data-lucide="${toastIcons[tone] || 'info'}"></i></span><div>${message}</div><button class="t-close" aria-label="Tutup"><i data-lucide="x"></i></button><span class="t-bar"></span>`;
        box.append(el);
        SA.icons();
        const close = () => { el.classList.add('out'); setTimeout(() => el.remove(), 400); };
        el.querySelector('.t-close').onclick = close;
        setTimeout(close, 5000);
    };

    /* ---------- Modals ---------- */
    SA.openModal = (id) => { const m = document.getElementById(id); if (!m) return; m.classList.add('open'); document.body.style.overflow = 'hidden'; setTimeout(() => m.querySelector('input:not([type=hidden]),textarea,select')?.focus(), 80); };
    SA.closeModal = (m) => { m.classList.remove('open'); document.body.style.overflow = ''; };
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-modal]');
        if (opener) { e.preventDefault(); SA.openModal(opener.dataset.modal); }
        if (e.target.closest('[data-close]') || e.target.classList.contains('modal-backdrop')) {
            const m = e.target.closest('.modal'); if (m) SA.closeModal(m);
        }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.modal.open').forEach(SA.closeModal); });

    /* ---------- Confirm dialog (replaces window.confirm) ---------- */
    SA.confirm = ({ title = 'Yakin?', message = '', confirmText = 'Ya, lanjutkan', tone = 'danger', input = null }) => new Promise((resolve) => {
        const wrap = document.createElement('div');
        wrap.className = 'modal open';
        const color = tone === 'danger' ? 'var(--red)' : 'var(--mint)';
        wrap.innerHTML = `<div class="modal-backdrop"></div><div class="modal-box" style="width:min(440px,100%)">
            <div class="modal-icon" style="background:color-mix(in srgb, ${color} 15%, transparent);color:${color}"><i data-lucide="${tone === 'danger' ? 'alert-triangle' : 'help-circle'}"></i></div>
            <h3 class="modal-title">${title}</h3><p class="muted" style="margin:0 0 18px">${message}</p>
            ${input ? `<textarea class="textarea" style="min-height:80px;margin-bottom:16px" placeholder="${input}"></textarea>` : ''}
            <div class="row" style="justify-content:flex-end"><button class="btn" data-x>Batal</button><button class="btn ${tone === 'danger' ? 'btn-danger' : 'btn-primary'}" data-ok>${confirmText}</button></div></div>`;
        document.body.append(wrap); SA.icons();
        const done = (v) => { wrap.remove(); resolve(v); };
        wrap.querySelector('[data-x]').onclick = () => done(false);
        wrap.querySelector('.modal-backdrop').onclick = () => done(false);
        wrap.querySelector('[data-ok]').onclick = () => done(input ? (wrap.querySelector('textarea').value || '') : true);
        (wrap.querySelector('textarea') || wrap.querySelector('[data-ok]')).focus();
    });
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form.dataset.confirm || form.dataset.confirmed) return;
        e.preventDefault();
        const ok = await SA.confirm({ title: form.dataset.confirm, message: form.dataset.confirmText || 'Tindakan ini tidak dapat dibatalkan.', confirmText: form.dataset.confirmBtn || 'Ya, hapus', tone: form.dataset.tone || 'danger' });
        if (ok) { form.dataset.confirmed = 1; SA.progress(); form.submit(); }
    });

    /* ---------- Top progress bar on navigation ---------- */
    SA.progress = () => {
        let bar = $('.top-progress');
        if (!bar) { bar = document.createElement('div'); bar.className = 'top-progress'; document.body.append(bar); }
        bar.style.opacity = 1; bar.style.width = '0';
        requestAnimationFrame(() => { bar.style.width = '70%'; });
    };
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download') || e.ctrlKey || e.metaKey) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || a.dataset.noProgress !== undefined || /ekspor|template|backup|cetak/.test(href)) return;
        SA.progress();
    });
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented || e.target.dataset.noProgress !== undefined) return;
        SA.progress();
        const btn = e.target.querySelector('button[type=submit]:not([data-keep])');
        if (btn) { btn.disabled = true; btn.dataset.label = btn.innerHTML; btn.innerHTML = '<span class="loader-ring" style="width:16px;height:16px"><i></i></span> Memproses…'; setTimeout(() => { btn.disabled = false; btn.innerHTML = btn.dataset.label; }, 8000); }
    });

    /* ---------- Ripple effect ---------- */
    document.addEventListener('pointerdown', (e) => {
        const btn = e.target.closest('.btn');
        if (!btn) return;
        const r = btn.getBoundingClientRect();
        const s = document.createElement('span');
        const size = Math.max(r.width, r.height);
        s.className = 'ripple';
        Object.assign(s.style, { width: size + 'px', height: size + 'px', left: e.clientX - r.left - size / 2 + 'px', top: e.clientY - r.top - size / 2 + 'px' });
        btn.append(s); setTimeout(() => s.remove(), 600);
    });

    /* ---------- Spotlight hover cards ---------- */
    document.addEventListener('pointermove', (e) => {
        const c = e.target.closest('.spotlight');
        if (!c) return;
        const r = c.getBoundingClientRect();
        c.style.setProperty('--x', `${e.clientX - r.left}px`);
        c.style.setProperty('--y', `${e.clientY - r.top}px`);
    });

    /* ---------- Reveal on scroll + counters + progress bars ---------- */
    const animateCount = (el) => {
        const target = parseFloat(el.dataset.count);
        const dec = parseInt(el.dataset.decimals || 0, 10);
        const suffix = el.dataset.suffix || '';
        const prefix = el.dataset.prefix || '';
        const dur = 1400; const t0 = performance.now();
        const step = (t) => {
            const p = Math.min(1, (t - t0) / dur);
            const eased = 1 - Math.pow(1 - p, 4);
            el.textContent = prefix + SA.fmt(target * eased, dec) + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
            if (!en.isIntersecting) return;
            const el = en.target;
            if (el.hasAttribute('data-reveal')) el.classList.add('in');
            if (el.dataset.count !== undefined) animateCount(el);
            if (el.dataset.width !== undefined) el.style.width = el.dataset.width + '%';
            if (el.dataset.ring !== undefined) el.style.setProperty('--v', el.dataset.ring);
            io.unobserve(el);
        });
    }, { threshold: .12 });
    SA.observe = (root = document) => $$('[data-reveal],[data-count],[data-width],[data-ring]', root).forEach((el) => io.observe(el));

    /* ---------- Tabs with sliding ink ---------- */
    SA.tabs = (root = document) => {
        $$('[data-tabs]', root).forEach((tabs) => {
            const group = tabs.dataset.tabs;
            let ink = tabs.querySelector('.tab-ink');
            if (!ink) { ink = document.createElement('span'); ink.className = 'tab-ink'; tabs.prepend(ink); }
            const move = (t) => { ink.style.left = t.offsetLeft + 'px'; ink.style.width = t.offsetWidth + 'px'; };
            const activate = (t, push = true) => {
                $$('.tab', tabs).forEach((x) => x.classList.toggle('active', x === t));
                $$(`[data-panel-group="${group}"]`).forEach((p) => p.classList.toggle('active', p.dataset.panel === t.dataset.tab));
                move(t);
                if (push && tabs.dataset.hash !== undefined) history.replaceState(null, '', '#' + t.dataset.tab);
                document.dispatchEvent(new CustomEvent('tab:change', { detail: { group, tab: t.dataset.tab } }));
            };
            $$('.tab', tabs).forEach((t) => t.addEventListener('click', () => activate(t)));
            const fromHash = location.hash && tabs.querySelector(`.tab[data-tab="${location.hash.slice(1)}"]`);
            const initial = fromHash || tabs.querySelector('.tab.active') || tabs.querySelector('.tab');
            requestAnimationFrame(() => activate(initial, false));
            window.addEventListener('resize', () => move(tabs.querySelector('.tab.active')));
        });
    };

    /* ---------- Dropzone ---------- */
    SA.dropzones = () => $$('.dropzone').forEach((dz) => {
        const input = dz.querySelector('input[type=file]');
        const label = dz.querySelector('.dz-file');
        ['dragenter', 'dragover'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('drag'); }));
        ['dragleave', 'drop'].forEach((ev) => dz.addEventListener(ev, () => dz.classList.remove('drag')));
        input?.addEventListener('change', () => {
            const f = input.files[0];
            if (label) label.textContent = f ? `✓ ${f.name} (${(f.size / 1024).toFixed(1)} KB)` : '';
            const preview = dz.dataset.preview && document.getElementById(dz.dataset.preview);
            if (preview && f && f.type.startsWith('image/')) { preview.src = URL.createObjectURL(f); preview.style.display = 'block'; }
        });
    });

    /* ---------- Command palette (Ctrl/⌘ + K) ---------- */
    SA.palette = () => {
        const modal = document.getElementById('palette');
        if (!modal) return;
        const input = modal.querySelector('input');
        const items = () => $$('.palette-item', modal).filter((i) => i.style.display !== 'none');
        let sel = 0;
        const paint = () => items().forEach((i, idx) => i.classList.toggle('sel', idx === sel));
        input.addEventListener('input', () => {
            const q = input.value.toLowerCase();
            $$('.palette-item', modal).forEach((i) => { i.style.display = i.textContent.toLowerCase().includes(q) ? '' : 'none'; });
            sel = 0; paint();
        });
        input.addEventListener('keydown', (e) => {
            const list = items();
            if (e.key === 'ArrowDown') { sel = (sel + 1) % list.length; paint(); e.preventDefault(); }
            if (e.key === 'ArrowUp') { sel = (sel - 1 + list.length) % list.length; paint(); e.preventDefault(); }
            if (e.key === 'Enter' && list[sel]) { SA.progress(); location.href = list[sel].href; }
        });
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); SA.openModal('palette'); input.value = ''; input.dispatchEvent(new Event('input')); }
        });
        paint();
    };

    /* ---------- Chart.js defaults ---------- */
    SA.chartDefaults = () => {
        if (!window.Chart) return;
        Chart.defaults.color = '#8c9bb1';
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.borderColor = 'rgba(120,150,190,.1)';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.legend.labels.padding = 14;
        Object.assign(Chart.defaults.plugins.tooltip, { backgroundColor: '#0b1322', borderColor: 'rgba(120,150,190,.3)', borderWidth: 1, padding: 12, cornerRadius: 10, titleColor: '#edf3fb', bodyColor: '#b9c7d9', titleFont: { weight: 700 } });
        Chart.defaults.animation.duration = 1200;
        Chart.defaults.animation.easing = 'easeOutQuart';
        Chart.defaults.maintainAspectRatio = false;
    };
    SA.gradient = (ctx, color) => {
        const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 300);
        g.addColorStop(0, color + '55'); g.addColorStop(1, color + '00');
        return g;
    };

    /* ---------- Mobile nav ---------- */
    document.addEventListener('click', (e) => {
        if (e.target.closest('.menu-toggle')) document.body.classList.toggle('nav-open');
        else if (document.body.classList.contains('nav-open') && !e.target.closest('.sidebar')) document.body.classList.remove('nav-open');
    });

    /* ---------- Notification quick-read ---------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-read-notif]');
        if (!btn) return;
        e.preventDefault();
        try {
            const res = await SA.request(btn.dataset.readNotif, { method: 'POST' });
            btn.closest('.notif')?.classList.remove('unread');
            btn.remove();
            const badge = $('#notifDot'); if (badge && !res.unread) badge.remove();
            SA.toast('Notifikasi ditandai dibaca', 'success');
        } catch (err) { SA.toast(err.message, 'danger'); }
    });

    /* ---------- Boot ---------- */
    document.addEventListener('DOMContentLoaded', () => {
        SA.icons();
        SA.chartDefaults();
        SA.observe();
        SA.tabs();
        SA.dropzones();
        SA.palette();
        (window.__toasts || []).forEach(([tone, msg]) => setTimeout(() => SA.toast(msg, tone), 350));
    });
    window.addEventListener('load', () => setTimeout(() => $('.page-loader')?.classList.add('done'), 250));
    window.addEventListener('pageshow', (e) => { if (e.persisted) { $('.page-loader')?.classList.add('done'); const b = $('.top-progress'); if (b) b.style.width = '0'; } });
})();
