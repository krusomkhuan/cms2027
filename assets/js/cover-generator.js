// สร้างภาพปกข่าวอัตโนมัติจากหัวข้อข่าว (วาดใน canvas ของเบราว์เซอร์ จึงแสดงสระ/วรรณยุกต์ภาษาไทยถูกต้อง)
(() => {
  const btn = document.getElementById('coverGenBtn');
  if (!btn) return;
  const W = 1200, H = 750;
  const cfg = btn.dataset;
  const THEMES = {
    college: ['#0f2a6b', '#1e3a8a', '#b4232a'],
    navy: ['#0b1a3f', '#1e3a8a', '#2b6cb0'],
    red: ['#5c0f14', '#b4232a', '#e0565c'],
    green: ['#064e3b', '#047857', '#10b981'],
    purple: ['#2e1065', '#6d28d9', '#c026d3'],
  };

  // โหลดฟอนต์ Kanit ตัวหนาสำหรับหัวข้อ
  const fontLink = document.createElement('link');
  fontLink.rel = 'stylesheet';
  fontLink.href = 'https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap';
  document.head.appendChild(fontLink);

  const loadImage = (src) => new Promise((ok) => {
    if (!src) return ok(null);
    const img = new Image();
    img.onload = () => ok(img);
    img.onerror = () => ok(null);
    img.src = src;
  });

  /** ตัดคำภาษาไทยเป็นบรรทัดตามความกว้าง */
  function wrapLines(ctx, text, maxWidth) {
    const words = window.Intl && Intl.Segmenter
      ? [...new Intl.Segmenter('th', { granularity: 'word' }).segment(text)].map((s) => s.segment)
      : text.split(/(\s+)/);
    const lines = [];
    let line = '';
    for (const w of words) {
      const test = line + w;
      if (ctx.measureText(test).width > maxWidth && line.trim() !== '') {
        lines.push(line.trim());
        line = w.trimStart();
      } else {
        line = test;
      }
    }
    if (line.trim()) lines.push(line.trim());
    return lines;
  }

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  /** วาดรูปแบบ cover (เต็มพื้นที่ ตัดส่วนเกิน) */
  function drawCover(ctx, img, x, y, w, h) {
    const s = Math.max(w / img.width, h / img.height);
    const iw = img.width * s, ih = img.height * s;
    ctx.drawImage(img, x + (w - iw) / 2, y + (h - ih) / 2, iw, ih);
  }

  async function render() {
    const title = (document.getElementById('title').value || '').trim();
    if (!title) {
      alert('กรุณากรอกหัวข้อข่าวก่อน');
      document.getElementById('title').focus();
      return null;
    }
    const theme = THEMES[document.querySelector('input[name=cover_theme]:checked')?.value] || THEMES.college;
    const catSel = document.getElementById('category_id');
    const category = catSel && catSel.value ? catSel.options[catSel.selectedIndex].text : 'ข่าวประชาสัมพันธ์';
    const dateVal = document.getElementById('published_at')?.value;
    const date = new Intl.DateTimeFormat('th-TH', { dateStyle: 'long' }).format(dateVal ? new Date(dateVal) : new Date());

    // รูปพื้นหลังจากเนื้อหาข่าว (รูปแรกในตัวแก้ไข)
    let photo = null;
    if (document.getElementById('coverUsePhoto')?.checked && window.tinymce?.activeEditor) {
      const first = tinymce.activeEditor.getBody().querySelector('img');
      if (first) photo = await loadImage(first.src);
    }
    await document.fonts.load('600 60px Kanit');
    await document.fonts.load('400 24px Kanit');
    const logo = await loadImage(cfg.logo);

    const c = document.createElement('canvas');
    c.width = W;
    c.height = H;
    const ctx = c.getContext('2d');

    // พื้นหลัง
    const g = ctx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, theme[0]);
    g.addColorStop(0.55, theme[1]);
    g.addColorStop(1, theme[2]);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
    if (photo) {
      drawCover(ctx, photo, 0, 0, W, H);
      const ov = ctx.createLinearGradient(0, 0, 0, H);
      ov.addColorStop(0, 'rgba(10,20,50,.55)');
      ov.addColorStop(0.45, 'rgba(10,20,50,.35)');
      ov.addColorStop(1, 'rgba(10,20,50,.92)');
      ctx.fillStyle = ov;
      ctx.fillRect(0, 0, W, H);
    } else {
      // ลวดลายวงกลมและเส้นทแยง
      ctx.globalAlpha = 0.08;
      ctx.fillStyle = '#fff';
      [[W - 120, 120, 260], [W - 40, H - 80, 180], [140, H + 40, 220]].forEach(([x, y, r]) => {
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fill();
      });
      ctx.globalAlpha = 0.05;
      ctx.setTransform(1, 0, -0.35, 1, 0, 0);
      for (let i = 0; i < W + H; i += 46) {
        ctx.fillRect(i, 0, 14, H);
      }
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.globalAlpha = 1;
    }

    // ส่วนหัว: โลโก้ + ชื่อวิทยาลัย
    let x = 64;
    if (logo) {
      ctx.save();
      ctx.beginPath(); ctx.arc(64 + 44, 64 + 44, 46, 0, Math.PI * 2); ctx.fillStyle = 'rgba(255,255,255,.95)'; ctx.fill();
      ctx.clip();
      const s = 84 / Math.max(logo.width, logo.height);
      ctx.drawImage(logo, 64 + 44 - (logo.width * s) / 2, 64 + 44 - (logo.height * s) / 2, logo.width * s, logo.height * s);
      ctx.restore();
      x = 64 + 88 + 22;
    }
    ctx.fillStyle = '#fff';
    ctx.textBaseline = 'alphabetic';
    ctx.font = '600 34px Kanit';
    ctx.fillText(cfg.name, x, 100);
    ctx.font = '400 20px Kanit';
    ctx.globalAlpha = 0.8;
    ctx.fillText(cfg.nameEn, x, 134);
    ctx.globalAlpha = 1;

    // หมวดหมู่
    ctx.font = '500 26px Kanit';
    const bw = ctx.measureText(category).width + 44;
    roundRect(ctx, 64, 240, bw, 50, 25);
    ctx.fillStyle = theme === THEMES.red ? '#fff' : '#d71920';
    ctx.fill();
    ctx.fillStyle = theme === THEMES.red ? '#b4232a' : '#fff';
    ctx.fillText(category, 86, 274);

    // หัวข้อข่าว: ลดขนาดตัวอักษรจนพอดีไม่เกิน 4 บรรทัด
    const maxW = W - 128;
    let size = 70, lines;
    do {
      ctx.font = `600 ${size}px Kanit`;
      lines = wrapLines(ctx, title, maxW);
      size -= 4;
    } while ((lines.length > 4 || lines.length * size * 1.45 > 330) && size > 34);
    size += 4;
    if (lines.length > 4) {
      lines = lines.slice(0, 4);
      lines[3] = lines[3].replace(/.{0,2}$/u, '') + '…';
    }
    ctx.fillStyle = '#fff';
    ctx.shadowColor = 'rgba(0,0,0,.35)';
    ctx.shadowBlur = photo ? 16 : 6;
    const lh = size * 1.42;
    let y = 330 + size;
    for (const l of lines) {
      ctx.fillText(l, 64, y);
      y += lh;
    }
    ctx.shadowBlur = 0;

    // ส่วนท้าย: แถบสีวิทยาลัย + วันที่ + เว็บไซต์
    const bar = ctx.createLinearGradient(0, 0, W, 0);
    bar.addColorStop(0, '#1e3a8a');
    bar.addColorStop(0.6, '#7b3a6e');
    bar.addColorStop(1, '#d71920');
    ctx.fillStyle = 'rgba(0,0,0,.28)';
    ctx.fillRect(0, H - 86, W, 86);
    ctx.fillStyle = bar;
    ctx.fillRect(0, H - 10, W, 10);
    ctx.fillStyle = '#fff';
    ctx.font = '400 24px Kanit';
    ctx.fillText(date, 64, H - 34);
    ctx.textAlign = 'right';
    ctx.globalAlpha = 0.85;
    ctx.fillText(cfg.site, W - 64, H - 34);
    ctx.globalAlpha = 1;
    ctx.textAlign = 'left';

    return c;
  }

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    const label = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังสร้าง...';
    try {
      const canvas = await render();
      if (!canvas) return;
      const blob = await new Promise((ok) => canvas.toBlob(ok, 'image/jpeg', 0.9));
      const input = document.querySelector('input[name=cover]');
      const dt = new DataTransfer();
      dt.items.add(new File([blob], 'cover-auto.jpg', { type: 'image/jpeg' }));
      input.files = dt.files;
      const prev = document.getElementById('coverPreview');
      prev.src = URL.createObjectURL(blob);
      prev.classList.remove('d-none');
      document.getElementById('coverGenNote').classList.remove('d-none');
    } catch (e) {
      // ภาพจากโดเมนอื่นทำให้ canvas ส่งออกไม่ได้ ลองใหม่โดยไม่ใช้รูปพื้นหลัง
      alert('สร้างภาพปกไม่สำเร็จ' + (document.getElementById('coverUsePhoto')?.checked ? ' — ลองยกเลิก "ใช้รูปแรกในเนื้อหาเป็นพื้นหลัง"' : ''));
    } finally {
      btn.disabled = false;
      btn.innerHTML = label;
    }
  });
})();
