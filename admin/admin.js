/* Dakpion admin — small, dependency-free helpers. */
(function () {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const A = window.ADMIN || {};
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function toast(msg) {
    const t = document.createElement('div');
    t.className = 'toast'; t.textContent = msg; t.setAttribute('role', 'status');
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2200);
  }

  async function post(url, data) {
    const fd = data instanceof FormData ? data : Object.entries(data).reduce((f, [k, v]) => {
      [].concat(v).forEach(x => f.append(k, x)); return f;
    }, new FormData());
    fd.append('_csrf', A.csrf);
    const res = await fetch(url, { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
    const json = await res.json().catch(() => ({ ok: false, error: 'Server error (' + res.status + ')' }));
    if (!res.ok && json.ok !== false) json.ok = false;
    return json;
  }

  /* Confirm destructive actions */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);

  /* Warn before leaving a form with unsaved changes */
  $$('form[data-dirty]').forEach(form => {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  /* Client-side search over table rows */
  $$('[data-filter-rows]').forEach(input => {
    const body = $(input.dataset.filterRows);
    if (!body) return;
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      $$('tr', body).forEach(tr => { tr.hidden = q && !tr.textContent.toLowerCase().includes(q); });
    });
  });

  /* Drag to reorder (pointer events: mouse and touch) */
  $$('[data-sortable]').forEach(body => {
    let row = null, moved = false;
    body.addEventListener('pointerdown', e => {
      const h = e.target.closest('.handle');
      if (!h) return;
      e.preventDefault();
      row = h.closest('tr'); moved = false;
      row.classList.add('dragging');
      h.setPointerCapture(e.pointerId);
    });
    body.addEventListener('pointermove', e => {
      if (!row) return;
      const rows = $$('tr', body).filter(r => r !== row && !r.hidden);
      for (const r of rows) {
        const b = r.getBoundingClientRect();
        if (e.clientY > b.top && e.clientY < b.bottom) {
          const after = e.clientY > b.top + b.height / 2;
          if (after && r.nextElementSibling !== row) { r.after(row); moved = true; }
          else if (!after && r.previousElementSibling !== row) { r.before(row); moved = true; }
          break;
        }
      }
    });
    const end = async () => {
      if (!row) return;
      row.classList.remove('dragging');
      row = null;
      if (!moved) return;
      const ids = $$('tr', body).map(r => r.dataset.id);
      const res = await post(body.dataset.sortable, { action: 'order', 'ids[]': ids });
      toast(res.ok ? 'Order saved' : 'Order save hoy nai — reload korun');
    };
    body.addEventListener('pointerup', end);
    body.addEventListener('pointercancel', end);
  });

  /* Copy link */
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copy); toast('Link copied'); }
    catch { prompt('Copy this link:', b.dataset.copy); }
  });

  /* ── Media picker (image fields + editor) ── */
  const modal = $('#media-modal');
  function pickMedia() {
    return new Promise(resolve => {
      modal.innerHTML = `<div class="modal-card" role="dialog" aria-modal="true" aria-label="Media library">
        <div class="modal-head"><h3>Media library</h3>
          <label class="search">${'<svg class="ai" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>'}<input type="search" placeholder="Search…" aria-label="Search images"></label>
          <label class="btn btn--main btn--sm">Upload new<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden></label>
          <button class="ib" data-close aria-label="Close" style="font-size:22px">×</button></div>
        <div class="modal-body"><div class="pick-grid"><p class="muted">Loading…</p></div></div></div>`;
      modal.hidden = false;
      const grid = $('.pick-grid', modal), search = $('input[type=search]', modal);
      const ac = new AbortController(), signal = ac.signal;
      const done = val => { ac.abort(); modal.hidden = true; modal.innerHTML = ''; resolve(val); };
      document.addEventListener('keydown', e => { if (e.key === 'Escape') done(null); }, { signal });
      const load = async q => {
        const res = await fetch(A.base + 'media/list?q=' + encodeURIComponent(q || ''), { headers: { Accept: 'application/json' } });
        const { items = [] } = await res.json().catch(() => ({}));
        grid.innerHTML = items.length ? items.map(m => `<button type="button" data-path="${esc(m.path)}" data-url="${esc(m.url)}" title="${esc(m.name || m.path)} · ${m.width}×${m.height}"><img src="${esc(m.url)}" alt="" loading="lazy"></button>`).join('')
          : '<p class="muted">Kono chobi nai — "Upload new" diye add korun.</p>';
      };
      let t; search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => load(search.value), 200); });
      grid.addEventListener('click', e => { const b = e.target.closest('button[data-path]'); if (b) done({ path: b.dataset.path, url: b.dataset.url }); });
      modal.addEventListener('click', e => { if (e.target === modal || e.target.closest('[data-close]')) done(null); }, { signal });
      $('input[type=file]', modal).addEventListener('change', async e => {
        const f = e.target.files[0]; if (!f) return;
        grid.innerHTML = '<p class="muted">Uploading…</p>';
        const fd = new FormData(); fd.append('action', 'upload'); fd.append('file', f);
        const res = await post(A.base + 'media', fd);
        if (res.ok) done({ path: res.item.path, url: res.item.url });
        else { toast(res.error || 'Upload failed'); load(''); }
      });
      load('');
      search.focus();
    });
  }

  $$('[data-img-field]').forEach(box => {
    const hidden = $('input[type=hidden]', box), file = $('input[type=file]', box), prev = $('.img-prev', box), clear = $('[data-clear]', box);
    const show = url => { prev.innerHTML = url ? `<img src="${esc(url)}" alt="">` : '<span>No image</span>'; clear.hidden = !url; };
    file.addEventListener('change', () => { if (file.files[0]) show(URL.createObjectURL(file.files[0])); });
    $('[data-pick]', box).addEventListener('click', async () => {
      const m = await pickMedia();
      if (m) { hidden.value = m.path; file.value = ''; show(m.url); hidden.dispatchEvent(new Event('change', { bubbles: true })); }
    });
    clear.addEventListener('click', () => { hidden.value = ''; file.value = ''; show(''); hidden.dispatchEvent(new Event('change', { bubbles: true })); });
  });

  /* ── Media page: drag & drop upload ── */
  const dz = $('[data-dropzone]');
  if (dz) {
    const input = $('input[type=file]', dz), prog = $('.dz-progress', dz);
    const upload = async files => {
      files = Array.from(files).filter(f => f.type.startsWith('image/'));
      if (!files.length) return;
      prog.hidden = false;
      let ok = 0; const errors = [];
      for (const [i, f] of files.entries()) {
        prog.textContent = `Uploading ${i + 1} / ${files.length}…`;
        const fd = new FormData(); fd.append('action', 'upload'); fd.append('file', f);
        const res = await post(A.base + 'media', fd);
        res.ok ? ok++ : errors.push(f.name + ': ' + (res.error || 'failed'));
      }
      prog.textContent = `${ok} uploaded` + (errors.length ? ` · ${errors.length} failed` : '');
      if (errors.length) alert(errors.join('\n'));
      setTimeout(() => location.reload(), 500);
    };
    input.addEventListener('change', () => upload(input.files));
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('over'); }));
    dz.addEventListener('drop', e => upload(e.dataTransfer.files));
  }

  /* ── SEO: counters + live Google preview ── */
  const counter = (el, ideal) => {
    const c = document.createElement('span'); c.className = 'count';
    const lbl = el.closest('.field')?.querySelector('label');
    if (lbl) lbl.appendChild(c);
    const upd = () => { const n = el.value.length; c.textContent = n + ' / ' + ideal; c.classList.toggle('over', n > ideal); };
    el.addEventListener('input', upd); upd();
  };
  $$('[data-seo-title]').forEach(el => counter(el, 60));
  $$('[data-seo-desc]').forEach(el => counter(el, 160));
  const serp = $('[data-serp]');
  if (serp) {
    const titleSrc = $('[data-seo-title]'), descSrc = $('[data-seo-desc]'), main = $('[data-slug-source]'), excerpt = $('[name="f[excerpt]"]');
    const upd = () => {
      $('[data-serp-title]', serp).textContent = (titleSrc?.value || main?.value || 'Title').slice(0, 70);
      $('[data-serp-desc]', serp).textContent = (descSrc?.value || excerpt?.value || '').slice(0, 165);
    };
    [titleSrc, descSrc, main, excerpt].forEach(el => el?.addEventListener('input', upd));
  }

  /* ── Blog: slug from title ── */
  const slugSrc = $('[data-slug-source]'), slug = $('[data-slug]');
  if (slugSrc && slug) {
    let auto = slug.value === '';
    const make = s => s.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
    const out = $('[data-serp-slug]');
    slugSrc.addEventListener('input', () => { if (auto) { slug.value = make(slugSrc.value); if (out) out.textContent = slug.value; } });
    slug.addEventListener('input', () => { auto = slug.value === ''; if (out) out.textContent = slug.value; });
  }

  /* ── Rich text editor ── */
  $$('[data-rte]').forEach(rte => {
    const area = $('.rte-area', rte), src = $('.rte-src', rte), form = rte.closest('form'), block = $('[data-block]', rte);
    let sourceMode = false;
    document.execCommand('defaultParagraphSeparator', false, 'p');
    const sync = () => { if (!sourceMode) src.value = area.innerHTML; };
    const exec = (cmd, arg = null) => { area.focus(); document.execCommand(cmd, false, arg); sync(); refresh(); };
    const refresh = () => {
      ['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'].forEach(c => {
        $(`[data-cmd="${c}"]`, rte)?.classList.toggle('on', document.queryCommandState(c));
      });
      const b = (document.queryCommandValue('formatBlock') || 'p').toLowerCase();
      if (block) block.value = ['h2', 'h3'].includes(b) ? b : 'p';
    };
    let savedRange = null;
    const save = () => { const s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) savedRange = s.getRangeAt(0).cloneRange(); };
    const restore = () => { area.focus(); if (savedRange) { const s = getSelection(); s.removeAllRanges(); s.addRange(savedRange); } };

    rte.addEventListener('mousedown', e => { if (e.target.closest('.rte-bar button')) e.preventDefault(); });
    $$('[data-cmd]', rte).forEach(b => b.addEventListener('click', () => {
      if (b.dataset.arg === 'blockquote' && (document.queryCommandValue('formatBlock') || '').toLowerCase() === 'blockquote') exec('formatBlock', 'p');
      else exec(b.dataset.cmd, b.dataset.arg || null);
    }));
    block?.addEventListener('change', () => { restore(); exec('formatBlock', block.value); });
    $('[data-link]', rte).addEventListener('click', () => {
      save();
      const url = prompt('Link URL (https://… ba /contact):', 'https://');
      restore();
      if (url === null) return;
      if (url === '' || url === 'https://') exec('unlink');
      else if (getSelection().isCollapsed) exec('insertHTML', `<a href="${esc(url)}">${esc(url)}</a>`);
      else exec('createLink', url);
    });
    $('[data-image]', rte).addEventListener('click', async () => {
      save();
      const m = await pickMedia();
      restore();
      if (m) {
        const alt = prompt('Chobir short description (SEO / alt text):', '') || '';
        exec('insertHTML', `<figure><img src="${esc(m.url)}" alt="${esc(alt)}"></figure><p><br></p>`);
      }
    });
    $('[data-source]', rte).addEventListener('click', e => {
      sourceMode = !sourceMode;
      if (sourceMode) { src.value = area.innerHTML; area.hidden = true; src.hidden = false; src.focus(); }
      else { area.innerHTML = src.value; src.hidden = true; area.hidden = false; }
      e.currentTarget.classList.toggle('on', sourceMode);
      $$('.rte-bar button:not([data-source]), .rte-bar select', rte).forEach(b => { b.disabled = sourceMode; });
    });
    // Paste as clean text with paragraphs (Word/Google Docs styles are dropped)
    area.addEventListener('paste', e => {
      const text = e.clipboardData?.getData('text/plain');
      if (text == null) return;
      e.preventDefault();
      const html = text.split(/\n{2,}/).map(p => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>').join('');
      exec('insertHTML', html);
    });
    area.addEventListener('input', sync);
    area.addEventListener('keyup', refresh);
    area.addEventListener('mouseup', refresh);
    area.addEventListener('blur', save);
    form?.addEventListener('submit', () => { if (sourceMode) area.innerHTML = src.value; src.value = area.innerHTML; });
    sync();
  });

  /* ── Chart tooltips ── */
  const tip = $('.chart-tip');
  if (tip) {
    let last = null;
    document.addEventListener('mouseover', e => {
      const hit = e.target.closest('.hit');
      if (last) last.classList.remove('hover');
      if (!hit) { tip.hidden = true; return; }
      const bar = hit.previousElementSibling?.classList.contains('bar') ? hit.previousElementSibling : null;
      if (bar) { bar.classList.add('hover'); last = bar; }
      const b = hit.getBoundingClientRect();
      tip.textContent = hit.dataset.tip;
      tip.style.left = b.left + b.width / 2 + 'px';
      tip.style.top = (bar ? bar.getBoundingClientRect().top : b.bottom - 4) + 'px';
      tip.hidden = false;
    });
  }
})();
