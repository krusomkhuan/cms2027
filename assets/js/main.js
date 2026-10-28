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
