(function () {
  const backTop = document.querySelector('.back-top');
  if (backTop) {
    window.addEventListener('scroll', () => backTop.classList.toggle('show', window.scrollY > 400));
    backTop.addEventListener('click', (e) => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  // นับตัวเลขสถิติเมื่อเลื่อนมาถึง
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        const el = en.target, target = +el.dataset.count, start = performance.now();
        const step = (t) => {
          const p = Math.min(1, (t - start) / 1200);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('th-TH');
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        io.unobserve(el);
      });
    });
    counters.forEach((c) => io.observe(c));
  }

  // ปรับขนาดตัวอักษร A- A A+
  document.querySelectorAll('[data-fs]').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.documentElement.style.fontSize = btn.dataset.fs;
      try { localStorage.setItem('fontScale', btn.dataset.fs); } catch (e) {}
    });
  });

  // PDPA: ความยินยอมคุกกี้ + โหลดเนื้อหาภายนอก (Facebook / YouTube / Google Maps) เมื่อได้รับอนุญาตเท่านั้น
  window.pdpa = (() => {
    const cfg = document.getElementById('pdpaConfig');
    const banner = document.getElementById('pdpaBanner');
    const read = () => {
      if (!cfg) return { a: 1, m: 1 }; // ปิดระบบแจ้งคุกกี้ = ถือว่ายินยอม
      const m = document.cookie.match(/(?:^|; )pdpa_consent=([^;]*)/);
      try {
        const c = m ? JSON.parse(decodeURIComponent(m[1])) : null;
        return c && String(c.v) === cfg.dataset.version ? c : null;
      } catch (e) { return null; }
    };
    const has = (cat) => !!(read() || {})[cat === 'media' ? 'm' : 'a'];
    const makeFrame = (src, title) => {
      const f = document.createElement('iframe');
      Object.assign(f, { src, title, loading: 'lazy', allowFullscreen: true });
      f.allow = 'autoplay; encrypted-media; picture-in-picture';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      f.style.cssText = 'border:0;width:100%;height:100%';
      return f;
    };
    // แสดง iframe ใน holder ถ้ายินยอมแล้ว ไม่เช่นนั้นแสดงกล่องขออนุญาต
    const embed = (holder, src, provider, title) => {
      holder.innerHTML = '';
      if (has('media')) { holder.appendChild(makeFrame(src, title)); return; }
      const ph = document.createElement('div');
      ph.className = 'consent-embed';
      Object.assign(ph.dataset, { src, title });
      const esc = (s) => s.replace(/[&<>"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
      ph.innerHTML = '<div class="consent-ph"><i class="bi bi-shield-lock"></i><div>' + esc(cfg.dataset.phText.replace('%s', provider)) + '</div>'
        + '<button type="button" class="btn btn-sm btn-primary" data-consent-media>' + esc(cfg.dataset.phBtn) + '</button>'
        + '<a href="' + cfg.dataset.policyUrl + '" class="small">' + esc(cfg.dataset.phLink) + '</a></div>';
      holder.appendChild(ph);
    };
    const renderEmbeds = () => document.querySelectorAll('.consent-embed').forEach((ph) => ph.replaceWith(makeFrame(ph.dataset.src, ph.dataset.title)));
    const loadAnalytics = () => {
      const id = cfg && cfg.dataset.ga;
      if (!id || window.gtag) return;
      const s = document.createElement('script');
      s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { dataLayer.push(arguments); };
      gtag('js', new Date()); gtag('config', id, { anonymize_ip: true });
    };
    const save = (a, m) => {
      const prev = read();
      const c = { v: cfg.dataset.version, a: a ? 1 : 0, m: m ? 1 : 0, id: (prev && prev.id) || Math.random().toString(16).slice(2, 10) + Date.now().toString(16).slice(-8), t: Date.now() };
      document.cookie = 'pdpa_consent=' + encodeURIComponent(JSON.stringify(c)) + '; path=' + cfg.dataset.path + '; max-age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
      const fd = new FormData();
      fd.append('a', c.a); fd.append('m', c.m); fd.append('id', c.id);
      fetch(cfg.dataset.endpoint, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(() => {});
      if (banner) banner.hidden = true;
      document.body.classList.remove('pdpa-pending');
      document.querySelectorAll('[data-pdpa="analytics"]').forEach((el) => { el.checked = !!a; });
      document.querySelectorAll('[data-pdpa="media"]').forEach((el) => { el.checked = !!m; });
      // ถอนความยินยอมสื่อภายนอกหลังจากโหลดไปแล้ว → โหลดหน้าใหม่เพื่อเอา iframe ออก
      if (prev && prev.m && !m) { location.reload(); return; }
      if (m) renderEmbeds();
      if (a) loadAnalytics();
    };
    if (cfg) {
      if (!read()) document.body.classList.add('pdpa-pending');
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-consent], [data-consent-media]');
        if (!btn) return;
        if (btn.hasAttribute('data-consent-media')) { save(has('analytics'), true); return; }
        const kind = btn.dataset.consent;
        if (kind === 'all') save(true, true);
        else if (kind === 'necessary') save(false, false);
        else save(document.getElementById('pdpa_analytics').checked, document.getElementById('pdpa_media').checked);
        const modal = document.getElementById('pdpaModal');
        if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).hide();
      });
    }
    return { has, embed, save };
  })();

  // ปุ่มติดต่อลอย (Messenger / Facebook / LINE / โทร)
  const fab = document.getElementById('contactFab');
  if (fab) {
    const btn = document.getElementById('contactFabBtn');
    const panel = document.getElementById('contactPanel');
    const setOpen = (open) => {
      panel.hidden = !open;
      fab.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', String(open));
    };
    btn.addEventListener('click', () => setOpen(panel.hidden));
    document.addEventListener('click', (e) => { if (!fab.contains(e.target)) setOpen(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
  }

  // โฟกัสช่องค้นหาเมื่อเปิด
  const searchBar = document.getElementById('searchBar');
  if (searchBar) {
    searchBar.addEventListener('shown.bs.collapse', () => searchBar.querySelector('input').focus());
  }
})();
