/* DAKPION IMC — 360° Marketing Solutions · v4
   Motion engine: GSAP + ScrollTrigger + SplitText + Lenis (self-hosted in /assets/js/vendor)
   Everything degrades gracefully: without GSAP or with reduced motion, content is simply shown. */
(function () {
  "use strict";

  const root = document.documentElement;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const hasGsap = typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined';
  const animate = hasGsap && !reduced;
  let lenis = null;

  /* ── Always-on UI ─────────────────────────────── */
  function setActiveNav() {
    const page = document.body.dataset.page;
    $$('[data-nav="' + page + '"]').forEach(a => a.closest('li')?.classList.add('active'));
    const y = $('#year'); if (y) y.textContent = new Date().getFullYear();
  }

  function initHeader() {
    const header = $('.site-header');
    if (!header) return;
    let lastY = window.scrollY;
    const onScroll = () => {
      const y = window.scrollY;
      header.classList.toggle('is-scrolled', y > 20);
      const hide = y > 240 && y > lastY && !root.classList.contains('menu-open');
      header.classList.toggle('is-hidden', hide);
      lastY = y;
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const btn = $('.menu-btn');
    const setMenu = open => {
      root.classList.toggle('menu-open', open);
      btn?.setAttribute('aria-expanded', String(open));
      btn?.querySelector('span') && (btn.querySelector('span').textContent = open ? 'Close' : 'Menu');
      if (lenis) open ? lenis.stop() : lenis.start();
      else document.body.style.overflow = open ? 'hidden' : '';
    };
    btn?.addEventListener('click', () => setMenu(!root.classList.contains('menu-open')));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });
    window.addEventListener('resize', () => { if (window.innerWidth > 980) setMenu(false); });
    $$('.menu a').forEach(a => a.addEventListener('click', () => setMenu(false)));
  }

  function initFaq() {
    $$('.faq-q').forEach(q => q.addEventListener('click', () => {
      const item = q.closest('.faq-item');
      const wasOpen = item.classList.contains('open');
      $$('.faq-item', item.parentElement).forEach(i => {
        i.classList.remove('open');
        $('.faq-q', i)?.setAttribute('aria-expanded', 'false');
      });
      if (!wasOpen) { item.classList.add('open'); q.setAttribute('aria-expanded', 'true'); }
      setTimeout(() => window.ScrollTrigger?.refresh(), 520);
    }));
  }

  function initFilter() {
    const bar = $('.filter');
    if (!bar) return;
    const cards = $$('[data-cat]');
    bar.addEventListener('click', e => {
      const btn = e.target.closest('button');
      if (!btn) return;
      $$('button', bar).forEach(b => {
        b.classList.toggle('active', b === btn);
        b.setAttribute('aria-pressed', String(b === btn));
      });
      const f = btn.dataset.filter;
      const shown = [];
      cards.forEach(c => {
        const on = f === 'all' || c.dataset.cat.split(' ').includes(f);
        c.style.display = on ? '' : 'none';
        if (on) shown.push(c);
      });
      if (animate) gsap.fromTo(shown, { opacity: 0, y: 40 }, { opacity: 1, y: 0, duration: .7, stagger: .06, ease: 'expo.out', clearProps: 'transform' });
      window.ScrollTrigger?.refresh();
    });
  }

  function initForms() {
    $$('form[data-form]').forEach(form => form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      const ok = form.parentElement.querySelector('.form-success');
      const err = $('.form-error', form);
      const btn = $('[type=submit]', form);
      if (btn.disabled) return;
      btn.disabled = true; btn.style.opacity = '.6';
      if (err) err.hidden = true;
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) throw new Error(data.error || 'Something went wrong. Please try again or email us.');
        window.gtag?.('event', 'generate_lead');
        window.fbq?.('track', 'Lead');
        form.reset();
        form.style.display = 'none';
        ok?.classList.add('show');
        if (animate && ok) gsap.from(ok.children, { y: 30, opacity: 0, stagger: .08, duration: .8, ease: 'expo.out' });
      } catch (ex) {
        if (err) { err.textContent = ex instanceof TypeError ? 'Network error — please try again.' : ex.message; err.hidden = false; }
      } finally {
        btn.disabled = false; btn.style.opacity = '';
      }
    }));
  }

  /* Offer popup: shows after its delay, at most once per session/day/week/ever (set in the admin). */
  function initPopup() {
    const pop = $('#offer-popup');
    if (!pop) return;
    const key = 'dk-pop-' + pop.dataset.key, every = pop.dataset.every;
    const ttl = { day: 864e5, week: 6048e5 }[every];
    const store = every === 'session' ? sessionStorage : localStorage;
    try {
      const seen = store.getItem(key);
      if (seen && (every !== 'day' && every !== 'week' || Date.now() - +seen < ttl)) return;
    } catch (e) {}
    const close = () => {
      pop.classList.remove('show');
      setTimeout(() => { pop.hidden = true; }, 350);
      document.removeEventListener('keydown', onKey);
    };
    const onKey = e => { if (e.key === 'Escape') close(); };
    setTimeout(() => {
      if (root.classList.contains('menu-open')) return;
      pop.hidden = false;
      requestAnimationFrame(() => pop.classList.add('show'));
      try { store.setItem(key, String(Date.now())); } catch (e) {}
      document.addEventListener('keydown', onKey);
      $('.popup-card', pop)?.focus({ preventScroll: true });
    }, Math.max(0, +pop.dataset.delay || 0) * 1000);
    pop.addEventListener('click', e => { if (e.target === pop || e.target.closest('[data-popup-close]')) close(); });
  }

  function initRotator() {
    $$('.rotator').forEach(r => {
      const words = $$('span', r);
      if (words.length < 2) return;
      let i = 0;
      words[0].classList.add('is-on');
      if (reduced) return;
      setInterval(() => {
        if (document.hidden || r.dataset.manual) return;
        const cur = words.find(w => w.classList.contains('is-on')) || words[i];
        i = words.indexOf(cur);
        i = (i + 1) % words.length;
        cur.classList.remove('is-on'); cur.classList.add('is-out');
        words[i].classList.remove('is-out'); words[i].classList.add('is-on');
        setTimeout(() => cur.classList.remove('is-out'), 900);
      }, 2200);
    });
  }

  function showCountersStatic() {
    $$('[data-count]').forEach(el => { el.textContent = el.dataset.count + (el.dataset.suffix || ''); });
  }

  function anchorLinks() {
    $$('a[href^="#"]').forEach(a => a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id.length < 2) return;
      const t = $(id);
      if (!t) return;
      e.preventDefault();
      if (lenis) lenis.scrollTo(t, { offset: -90, duration: 1.4 });
      else t.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
    }));
    $$('.to-top').forEach(b => b.addEventListener('click', e => {
      e.preventDefault();
      if (lenis) lenis.scrollTo(0, { duration: 1.8 }); else window.scrollTo({ top: 0, behavior: 'smooth' });
    }));
  }

  /* ── Motion ───────────────────────────────────── */
  function initLenis() {
    if (typeof window.Lenis === 'undefined') return;
    lenis = new Lenis({ lerp: .09, wheelMultiplier: 1 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(t => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
  }

  function initPageTransitions() {
    const pt = $('.pt');
    if (!pt) return;
    if (root.classList.contains('pt-enter')) {
      gsap.to(pt, { yPercent: -100, duration: .7, ease: 'expo.inOut', delay: .05, onComplete: () => {
        root.classList.remove('pt-enter'); gsap.set(pt, { clearProps: 'all' });
      } });
    }
    try { sessionStorage.removeItem('dk-pt'); } catch (e) {}

    document.addEventListener('click', e => {
      const a = e.target.closest('a');
      if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (a.target && a.target !== '_self') return;
      if (a.hasAttribute('download')) return;
      const href = a.getAttribute('href');
      if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
      const url = new URL(a.href, location.href);
      if (url.origin !== location.origin) return;
      if (url.pathname === location.pathname) return; // same page (hash links)
      e.preventDefault();
      gsap.fromTo(pt, { yPercent: 100 }, { yPercent: 0, duration: .55, ease: 'expo.inOut', onComplete: () => {
        try { sessionStorage.setItem('dk-pt', '1'); } catch (err) {}
        location.href = url.href;
      } });
    });
    window.addEventListener('pageshow', ev => {
      if (ev.persisted) { root.classList.remove('pt-enter'); gsap.set(pt, { clearProps: 'all' }); }
    });
  }

  function initLoader(done) {
    const loader = $('.loader');
    if (!loader || !root.classList.contains('show-loader')) { done(); return; }
    const num = $('b', loader);
    const o = { v: 0 };
    gsap.timeline({ onComplete: () => { root.classList.remove('show-loader'); done(); } })
      .to(o, { v: 360, duration: .85, ease: 'power2.inOut', onUpdate: () => { num.textContent = Math.round(o.v) + '°'; } })
      .to(loader, { yPercent: -100, duration: .8, ease: 'expo.inOut' }, '+=.08');
    try { sessionStorage.setItem('dk-seen', '1'); } catch (e) {}
  }

  function heroIntro() {
    const center = $('.hero-center');
    if (center) {
      gsap.set([center, '.hero-title'], { visibility: 'visible' });
      gsap.timeline({ defaults: { ease: 'expo.out' } })
        .from('.hero-kicker', { y: 16, opacity: 0, duration: .9 })
        .from('.hero-title .lni', { yPercent: 110, duration: 1.2, stagger: .12 }, .1)
        .from(['.hero-sub', '.hero-cta'], { y: 20, opacity: 0, duration: .9, stagger: .08 }, .5)
        .from(['.hero-deg', '.hero-pin .scroll-hint'], { opacity: 0, duration: .8 }, .9)
        .from('.hr-track', { scale: .9, opacity: 0, transformOrigin: '50% 50%', duration: 1.4 }, .2);
    }
    const ph = $('.page-hero h1');
    if (ph) splitReveal(ph, true);
  }

  /* Hero: pin while the 360° ring draws itself and the degree counter runs 0 → 360 */
  function initHeroScroll(mm) {
    const pin = $('.hero-pin'), prog = $('.hr-prog'), deg = $('.hero-deg b');
    if (!pin || !prog) return;
    const rot = $('.hero .rotator'), rwords = rot ? $$('span', rot) : [];
    const ringWords = $$('.rw');
    gsap.set(ringWords, { opacity: 0, scale: .5 });
    let curWord = 0;
    const showWord = idx => {
      if (idx === curWord || !rwords[idx]) return;
      const prev = rwords[curWord];
      prev.classList.remove('is-on'); prev.classList.add('is-out');
      setTimeout(() => prev.classList.remove('is-out'), 900);
      rwords[idx].classList.remove('is-out'); rwords[idx].classList.add('is-on');
      curWord = idx;
    };
    const len = 1759.3;
    const counter = { v: 0 };
    const setDeg = () => { if (deg) deg.textContent = Math.round(counter.v); };
    mm.add('(min-width: 981px)', () => {
      const tl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
          trigger: pin, start: 'top top', end: '+=160%', pin: true, scrub: .6,
          onUpdate: self => showWord(Math.min(rwords.length - 1, Math.floor(self.progress * rwords.length * 1.15)))
        }
      });
      if (rot) {
        rot.dataset.manual = '1';
        rwords.forEach(w => w.classList.remove('is-on', 'is-out'));
        if (rwords[0]) rwords[0].classList.add('is-on');
        curWord = 0;
      }
      ringWords.forEach(w => {
        tl.to(w, { opacity: 1, scale: 1, duration: .06, ease: 'back.out(2)' }, parseFloat(w.dataset.angle) / 360 * .98);
      });
      tl.to(prog, { strokeDashoffset: 0, duration: 1 }, 0)
        .to(counter, { v: 360, duration: 1, onUpdate: setDeg }, 0)
        .to('.hero-ring', { scale: 1.08, duration: 1 }, 0)
        .to('.hero-kicker', { y: -30, opacity: 0, duration: .35 }, .55)
        .to('.hero-title', { scale: .92, y: -20, duration: .45 }, .5)
        .to(['.hero-sub', '.hero-cta'], { y: -24, opacity: 0, duration: .35, stagger: .05 }, .6)
        .to('.hero-pin .scroll-hint', { opacity: 0, duration: .2 }, 0);
      return () => { gsap.set(prog, { strokeDashoffset: len }); counter.v = 0; setDeg(); if (rot) delete rot.dataset.manual; };
    });
    mm.add('(max-width: 980px)', () => {
      gsap.to(prog, { strokeDashoffset: 0, duration: 2.2, ease: 'power2.inOut', delay: .4 });
      gsap.to(counter, { v: 360, duration: 2.2, ease: 'power2.inOut', delay: .4, onUpdate: setDeg });
      ringWords.forEach(w => gsap.to(w, { opacity: 1, scale: 1, duration: .5, ease: 'back.out(2)', delay: .4 + 2.2 * parseFloat(w.dataset.angle) / 360 }));
    });
  }

  function splitReveal(el, immediate) {
    if (typeof window.SplitText === 'undefined') { gsap.set(el, { visibility: 'visible' }); return; }
    SplitText.create(el, {
      type: 'lines', mask: 'lines', linesClass: 'split-line', autoSplit: true,
      onSplit(self) {
        gsap.set(el, { visibility: 'visible' });
        return gsap.from(self.lines, {
          yPercent: 115, duration: 1.2, stagger: .09, ease: 'expo.out',
          delay: immediate ? .15 : 0,
          scrollTrigger: immediate ? null : { trigger: el, start: 'top 88%', once: true }
        });
      }
    });
  }

  function initReveals() {
    $$('[data-split]').forEach(el => { if (!el.closest('.page-hero')) splitReveal(el, false); });

    ScrollTrigger.batch('[data-reveal]', {
      start: 'top 90%', once: true,
      onEnter: els => gsap.to(els, { opacity: 1, y: 0, duration: 1.1, stagger: .08, ease: 'expo.out', overwrite: true })
    });

    $$('.hl').forEach(h => gsap.fromTo(h, { '--hl': 0 }, {
      '--hl': 1, duration: 1.1, ease: 'expo.inOut', delay: .4,
      scrollTrigger: { trigger: h, start: 'top 88%', once: true }
    }));

    $$('[data-count]').forEach(el => {
      const o = { v: 0 }; const end = parseFloat(el.dataset.count); const suf = el.dataset.suffix || '';
      el.textContent = '0' + suf;
      gsap.to(o, { v: end, duration: 2, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 90%', once: true },
        onUpdate: () => { el.textContent = Math.round(o.v) + suf; } });
    });

    $$('[data-speed]').forEach(el => gsap.to(el, {
      y: () => parseFloat(el.dataset.speed) * -120, ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true, invalidateOnRefresh: true }
    }));
  }

  function initManifesto() {
    const m = $('.manifesto');
    if (!m || typeof window.SplitText === 'undefined') { m && m.classList.add('no-split'); return; }
    const s = SplitText.create(m, { type: 'words', wordsClass: 'w' });
    gsap.to(s.words, { opacity: 1, stagger: .1, ease: 'none',
      scrollTrigger: { trigger: m, start: 'top 78%', end: 'bottom 45%', scrub: .6 } });
  }

  function initTapes() {
    const tapes = $$('.tape');
    if (!tapes.length) return;
    let vel = 0, dir = 1;
    ScrollTrigger.create({ trigger: '.tapes', start: 'top bottom', end: 'bottom top',
      onUpdate: self => { vel = Math.abs(self.getVelocity()); dir = self.direction; } });
    tapes.forEach((tape, idx) => {
      const tracks = $$('.tape-track', tape);
      const sign = idx % 2 ? 1 : -1;
      let x = sign > 0 ? -tracks[0].offsetWidth : 0;
      const setX = gsap.quickSetter(tracks, 'x', 'px');
      gsap.ticker.add((t, dt) => {
        const w = tracks[0].offsetWidth;
        if (!w) return;
        const speed = (0.9 + Math.min(vel, 4000) / 380) * (dt / 16.67);
        x += speed * sign * dir;
        if (x <= -w) x += w;
        if (x > 0) x -= w;
        setX(x);
      });
    });
    gsap.ticker.add(() => { vel *= .92; });
  }

  function initHScroll(mm) {
    const pin = $('.hs-pin'), track = $('.hs-track');
    if (!pin || !track) return;
    mm.add('(min-width: 981px)', () => {
      const dist = () => track.scrollWidth - window.innerWidth;
      const skew = gsap.quickTo(track, 'skewX', { duration: .5, ease: 'power3' });
      const tw = gsap.to(track, {
        x: () => -dist(), ease: 'none',
        scrollTrigger: {
          trigger: pin, start: 'top top', end: () => '+=' + dist(), pin: true, scrub: 1, invalidateOnRefresh: true,
          onUpdate: self => skew(gsap.utils.clamp(-6, 6, self.getVelocity() / -400))
        }
      });
      $$('.svc', track).forEach(card => {
        gsap.from(card, { rotate: 6, y: 80, opacity: .3, ease: 'none',
          scrollTrigger: { trigger: card, containerAnimation: tw, start: 'left 105%', end: 'left 60%', scrub: true } });
      });
    });
  }

  function initStack(mm) {
    const cards = $$('.stack-card');
    if (cards.length < 2) return;
    mm.add('(min-width: 981px)', () => {
      cards.forEach((card, i) => {
        const next = cards[i + 1];
        if (!next) return;
        gsap.to(card, { scale: .9 + i * .012, ease: 'none',
          scrollTrigger: { trigger: next, start: 'top bottom', end: 'top 30%', scrub: true } });
      });
    });
  }

  function initTimeline() {
    const t = $('.timeline .bar');
    if (!t) return;
    gsap.fromTo(t, { '--p': 0 }, { '--p': 1, ease: 'none',
      scrollTrigger: { trigger: '.timeline', start: 'top 80%', end: 'bottom 60%', scrub: true } });
  }

  function initFooterWord() {
    const w = $$('.f-word span');
    if (!w.length) return;
    gsap.from(w, { yPercent: 100, duration: 1.3, stagger: .08, ease: 'expo.out',
      scrollTrigger: { trigger: '.f-word', start: 'top 95%', once: true } });
  }

  function initProgress() {
    const bar = $('.progress');
    if (!bar) return;
    gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: .3 } });
  }

  function initBlobs() {
    const b1 = $('.blob--1'), b2 = $('.blob--2');
    if (!b1) return;
    gsap.to(b1, { yPercent: 30, scale: 1.2, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } });
    if (!finePointer) return;
    const x1 = gsap.quickTo(b1, 'x', { duration: 2, ease: 'power3' }), y1 = gsap.quickTo(b1, 'y', { duration: 2, ease: 'power3' });
    const x2 = b2 && gsap.quickTo(b2, 'x', { duration: 2.6, ease: 'power3' }), y2 = b2 && gsap.quickTo(b2, 'y', { duration: 2.6, ease: 'power3' });
    window.addEventListener('mousemove', e => {
      const dx = e.clientX / innerWidth - .5, dy = e.clientY / innerHeight - .5;
      x1(dx * -120); y1(dy * -80);
      if (x2) { x2(dx * 90); y2(dy * 60); }
    }, { passive: true });
  }

  /* ── Pointer niceties (desktop) ───────────────── */
  function initCursor() {
    const c = $('.cursor'), d = $('.cursor-dot');
    if (!c || !d) return;
    const label = $('span', c);
    const cx = gsap.quickTo(c, 'x', { duration: .45, ease: 'power3' }), cy = gsap.quickTo(c, 'y', { duration: .45, ease: 'power3' });
    const dx = gsap.quickSetter(d, 'x', 'px'), dy = gsap.quickSetter(d, 'y', 'px');
    window.addEventListener('mousemove', e => {
      root.classList.add('has-cursor');
      cx(e.clientX); cy(e.clientY); dx(e.clientX); dy(e.clientY);
    }, { passive: true });
    document.addEventListener('mouseleave', () => root.classList.remove('has-cursor'));
    document.addEventListener('mouseover', e => {
      const lab = e.target.closest('[data-cursor]');
      const hov = e.target.closest('a, button, label, input, select, textarea');
      c.classList.toggle('is-label', !!lab);
      c.classList.toggle('is-hover', !lab && !!hov);
      if (lab) label.textContent = lab.dataset.cursor;
    });
  }

  function initMagnetic() {
    $$('[data-magnetic]').forEach(el => {
      const s = parseFloat(el.dataset.magnetic) || .35;
      const xT = gsap.quickTo(el, 'x', { duration: .8, ease: 'elastic.out(1, .4)' });
      const yT = gsap.quickTo(el, 'y', { duration: .8, ease: 'elastic.out(1, .4)' });
      el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect();
        xT((e.clientX - r.left - r.width / 2) * s);
        yT((e.clientY - r.top - r.height / 2) * s);
      });
      el.addEventListener('mouseleave', () => { xT(0); yT(0); });
    });
  }

  function initTilt() {
    $$('[data-tilt]').forEach(el => {
      const card = $('.card', el) || el;
      const rx = gsap.quickTo(card, 'rotateX', { duration: .6, ease: 'power3' });
      const ry = gsap.quickTo(card, 'rotateY', { duration: .6, ease: 'power3' });
      el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect();
        ry(((e.clientX - r.left) / r.width - .5) * 14);
        rx(((e.clientY - r.top) / r.height - .5) * -14);
      });
      el.addEventListener('mouseleave', () => { rx(0); ry(0); });
    });
  }

  function initWorkPreview() {
    const list = $('.wl'), pv = $('.preview');
    if (!list || !pv) return;
    const thumbs = $$('.thumb', pv);
    const w = () => pv.offsetWidth, h = () => pv.offsetHeight;
    const xT = gsap.quickTo(pv, 'x', { duration: .6, ease: 'power3' });
    const yT = gsap.quickTo(pv, 'y', { duration: .6, ease: 'power3' });
    const rT = gsap.quickTo(pv, 'rotate', { duration: .8, ease: 'power3' });
    let lastX = 0;
    list.addEventListener('mousemove', e => {
      xT(e.clientX - w() / 2); yT(e.clientY - h() / 2);
      rT(gsap.utils.clamp(-12, 12, (e.clientX - lastX) * .6)); lastX = e.clientX;
    });
    list.addEventListener('mouseenter', e => {
      gsap.set(pv, { x: e.clientX - w() / 2, y: e.clientY - h() / 2 });
      gsap.to(pv, { opacity: 1, scale: 1, duration: .5, ease: 'expo.out' });
    });
    list.addEventListener('mouseleave', () => gsap.to(pv, { opacity: 0, scale: .6, duration: .4, ease: 'power3' }));
    $$('.wl-row', list).forEach((row, i) => row.addEventListener('mouseenter', () => {
      thumbs.forEach((t, j) => t.classList.toggle('on', j === i));
    }));
  }

  /* ── Boot ─────────────────────────────────────── */
  function boot() {
    setActiveNav();
    initHeader();
    initFaq();
    initFilter();
    initForms();
    initPopup();
    initRotator();
    window.__animReady = true;

    if (!animate) {
      root.classList.add('no-anim');
      root.classList.remove('show-loader', 'pt-enter');
      showCountersStatic();
      anchorLinks();
      return;
    }

    gsap.registerPlugin(ScrollTrigger);
    if (window.SplitText) gsap.registerPlugin(SplitText);
    initLenis();
    anchorLinks();
    initPageTransitions();
    initProgress();

    const mm = gsap.matchMedia();
    const start = () => {
      heroIntro();
      initHeroScroll(mm);
      initReveals();
      initManifesto();
      initTapes();
      initHScroll(mm);
      initStack(mm);
      initTimeline();
      initFooterWord();
      initBlobs();
      if (finePointer) { initCursor(); initMagnetic(); initTilt(); initWorkPreview(); }
      ScrollTrigger.refresh();
    };
    const fontsReady = document.fonts && document.fonts.ready ? Promise.race([document.fonts.ready, new Promise(r => setTimeout(r, 1200))]) : Promise.resolve();
    fontsReady.then(() => initLoader(start));
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
