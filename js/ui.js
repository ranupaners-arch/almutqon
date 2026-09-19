/* ===================================================================
UI INTERACTIONS & LANDING PAGE LOGIC
=================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    
    /* ============ NAVBAR SCROLL ============ */
    const navbar = document.getElementById('navbar');
    function handleScroll() {
        const scrolled = window.scrollY > 20;
        navbar.classList.toggle('scrolled', scrolled);
    }
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();

    /* ============ MOBILE MENU ============ */
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileClose = document.getElementById('mobileClose');
    const mobileLinks = document.querySelectorAll('.mobile-menu__link');

    function openMobileMenu() {
        mobileMenu.classList.add('active');
        mobileOverlay.classList.add('active');
        hamburger.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
        mobileMenu.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    
    // Kita expose fungsi ini ke window supaya bisa dipanggil dari app.js jika perlu
    window.closeMobileMenu = function() {
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        mobileMenu.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    hamburger.addEventListener('click', openMobileMenu);
    mobileClose.addEventListener('click', window.closeMobileMenu);
    mobileOverlay.addEventListener('click', window.closeMobileMenu);
    mobileLinks.forEach(link => link.addEventListener('click', window.closeMobileMenu));

    /* ============ SMOOTH SCROLL ============ */
    document.querySelectorAll('a[href*="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const hashIndex = href.indexOf('#');
            if (hashIndex === -1) return;
            
            // Ambil hanya bagian hash-nya (misal: "#tentang")
            const targetId = href.substring(hashIndex);
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            
            // HANYA jalankan smooth scroll jika section tersebut ADA di halaman ini (Beranda)
            if (target) {
            e.preventDefault();
            const offset = 72;
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
            const startPosition = window.scrollY;
            const distance = targetPosition - startPosition;
            const duration = 1500;
            let startTimestamp = null;
            
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const elapsed = timestamp - startTimestamp;
                const progress = Math.min(elapsed / duration, 1);
                const easeInOutCubic = (t) => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
                window.scrollTo(0, startPosition + distance * easeInOutCubic(progress));
                if (elapsed < duration) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
            }
            // Jika target tidak ada (misal klik /#tentang dari halaman Artikel), biarkan browser pindah halaman secara normal
        });
    });

    /* ============ ACTIVE NAV ON SCROLL (HANYA DI HOMEPAGE) ============ */
    // Cek apakah kita sedang berada di halaman utama (root)
    const isHomePage = (window.location.pathname === '/' || 
                        window.location.pathname === '/index.php' || 
                        window.location.pathname === '/almutqon/' || 
                        window.location.pathname === '/almutqon/index.php');

    if (isHomePage) {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav__link');
        const navObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
            const id = entry.target.getAttribute('id');
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                // Cek apakah href BERAKHIRAN dengan '#id' (Aman untuk '#tentang' maupun '/#tentang')
                link.classList.toggle('active', linkHref.endsWith('#' + id));
            });
            }
        });
        }, { rootMargin: '-30% 0px -60% 0px' });
        sections.forEach(section => navObserver.observe(section));
    }

    /* ============ SCROLL REVEAL ============ */
    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    revealElements.forEach(el => revealObserver.observe(el));

    /* ============ COUNTER ANIMATION ============ */
    const counters = document.querySelectorAll('.stats__num');
    let countersAnimated = false;
    function animateCounter(el) {
        const target = parseInt(el.dataset.target);
        const suffix = el.dataset.suffix || '';
        const duration = 1500;
        const startTime = performance.now();
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(target * eased);
            el.textContent = current.toLocaleString('id-ID') + suffix;
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target.toLocaleString('id-ID') + suffix;
        }
        requestAnimationFrame(update);
    }
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !countersAnimated) {
                countersAnimated = true;
                counters.forEach(c => animateCounter(c));
                counterObserver.disconnect();
            }
        });
    }, { threshold: 0.4 });
    const statsSection = document.querySelector('.stats');
    if (statsSection) counterObserver.observe(statsSection);

    /* ============ TESTIMONIAL CAROUSEL ============ */
    const track = document.getElementById('storiesTrack');
    const cards = track ? Array.from(track.children) : [];
    const prevBtn = document.getElementById('storiesPrev');
    const nextBtn = document.getElementById('storiesNext');
    const dotsContainer = document.getElementById('storiesDots');
    let currentIndex = 0;
    let cardsPerView = 3;

    function getCardsPerView() {
        const w = window.innerWidth;
        if (w <= 768) return 1;
        if (w <= 1024) return 2;
        return 3;
    }
    function getMaxIndex() { return Math.max(0, cards.length - cardsPerView); }
    function buildDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        const dotsCount = getMaxIndex() + 1;
        for (let i = 0; i < dotsCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'stories__dot' + (i === currentIndex ? ' active' : '');
            dot.setAttribute('aria-label', `Cerita ${i + 1}`);
            dot.addEventListener('click', () => goTo(i));
            dotsContainer.appendChild(dot);
        }
    }
    function measureCard(track) {
        const host = track.closest('.stories__carousel, .programs__carousel, .articles__carousel') || track;
        host.classList.add('no-anim');
        void host.offsetWidth;
        const width = track.children[0].getBoundingClientRect().width;
        const gap = parseFloat(getComputedStyle(track).gap) || 28;
        host.classList.remove('no-anim');
        return { width, gap };
    }
    function updateCarousel() {
        if (!track || cards.length === 0) return;
        const m = measureCard(track);
        const offset = currentIndex * (m.width + m.gap);
        track.style.transform = `translateX(-${offset}px)`;
        const dots = dotsContainer ? dotsContainer.querySelectorAll('.stories__dot') : [];
        dots.forEach((d, i) => d.classList.toggle('active', i === currentIndex));
        if (prevBtn) prevBtn.disabled = currentIndex === 0;
        if (nextBtn) nextBtn.disabled = currentIndex === getMaxIndex();
    }
    function goTo(index) {
        currentIndex = Math.max(0, Math.min(index, getMaxIndex()));
        updateCarousel();
    }
    function next() { goTo(currentIndex + 1); }
    function prev() { goTo(currentIndex - 1); }

    if (nextBtn) nextBtn.addEventListener('click', next);
    if (prevBtn) prevBtn.addEventListener('click', prev);

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            cardsPerView = getCardsPerView();
            currentIndex = Math.min(currentIndex, getMaxIndex());
            buildDots();
            updateCarousel();
        }, 150);
    });

    let autoPlay = setInterval(() => {
        if (currentIndex >= getMaxIndex()) goTo(0);
        else next();
    }, 5000);

    const carousel = document.querySelector('.stories__carousel');
    if (carousel) {
        carousel.addEventListener('mouseenter', () => clearInterval(autoPlay));
        carousel.addEventListener('mouseleave', () => {
            autoPlay = setInterval(() => {
                if (currentIndex >= getMaxIndex()) goTo(0);
                else next();
            }, 5000);
        });
    }

    let touchStartX = 0;
    if (track) {
        track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) next();
                else prev();
            }
        }, { passive: true });
    }

    if (cards.length > 0) {
        cardsPerView = getCardsPerView();
        buildDots();
        requestAnimationFrame(() => updateCarousel());
    }

    /* ============ PROGRAMS CAROUSEL ============ */
    const pTrack = document.getElementById('programsTrack');
    const pCards = pTrack ? Array.from(pTrack.children) : [];
    const pPrev = document.getElementById('programsPrev');
    const pNext = document.getElementById('programsNext');
    const pDotsContainer = document.getElementById('programsDots');
    let pIndex = 0;
    let pPerView = 3;

    function getPPerView() {
        const w = window.innerWidth;
        if (w <= 768) return 1;
        if (w <= 1024) return 2;
        return 3;
    }
    function getPMaxIndex() { return Math.max(0, pCards.length - pPerView); }
    function buildPDots() {
        if (!pDotsContainer) return;
        pDotsContainer.innerHTML = '';
        for (let i = 0; i <= getPMaxIndex(); i++) {
            const dot = document.createElement('button');
            dot.className = 'stories__dot' + (i === pIndex ? ' active' : '');
            dot.setAttribute('aria-label', `Lanjut ke program ${i + 1}`);
            dot.addEventListener('click', () => { pIndex = i; updatePCarousel(); });
            pDotsContainer.appendChild(dot);
        }
    }
    function updatePCarousel() {
        if (!pTrack || pCards.length === 0) return;
        const m = measureCard(pTrack);
        pTrack.style.transform = `translateX(-${pIndex * (m.width + m.gap)}px)`;
        pDotsContainer.querySelectorAll('.stories__dot').forEach((d, i) => d.classList.toggle('active', i === pIndex));
        if(pPrev) pPrev.disabled = pIndex === 0;
        if(pNext) pNext.disabled = pIndex === getPMaxIndex();
    }

    if(pNext) pNext.addEventListener('click', () => { pIndex = Math.min(pIndex + 1, getPMaxIndex()); updatePCarousel(); });
    if(pPrev) pPrev.addEventListener('click', () => { pIndex = Math.max(pIndex - 1, 0); updatePCarousel(); });

    let pResizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(pResizeTimer);
        pResizeTimer = setTimeout(() => {
            pPerView = getPPerView();
            pIndex = Math.min(pIndex, getPMaxIndex());
            buildPDots();
            updatePCarousel();
        }, 150);
    });

    if (pCards.length > 0) {
        pPerView = getPPerView();
        buildPDots();
        requestAnimationFrame(() => updatePCarousel());
    }

    let pAutoPlay = setInterval(() => {
        if (pIndex >= getPMaxIndex()) pIndex = 0;
        else pIndex++;
        updatePCarousel();
    }, 7000);

    const pCarousel = document.querySelector('.programs__carousel');
    if (pCarousel) {
        pCarousel.addEventListener('mouseenter', () => clearInterval(pAutoPlay));
        pCarousel.addEventListener('mouseleave', () => {
            pAutoPlay = setInterval(() => {
                if (pIndex >= getPMaxIndex()) pIndex = 0;
                else pIndex++;
                updatePCarousel();
            }, 7000);
        });
    }

    /* ============ ARTICLES CAROUSEL ============ */
    const aTrack = document.getElementById('articlesTrack');
    const aCards = aTrack ? Array.from(aTrack.children) : [];
    const aPrev = document.getElementById('articlesPrev');
    const aNext = document.getElementById('articlesNext');
    const aDotsContainer = document.getElementById('articlesDots');
    const aNav = document.querySelector('.articles__carousel .stories__nav');
    let aIndex = 0;
    let aPerView = 3;

    function getAPerView() {
        const w = window.innerWidth;
        if (w <= 768) return 1;
        if (w <= 1024) return 2;
        return 3;
    }
    function getAMaxIndex() { return Math.max(0, aCards.length - aPerView); }
    function buildADots() {
        if (!aDotsContainer) return;
        aDotsContainer.innerHTML = '';
        for (let i = 0; i <= getAMaxIndex(); i++) {
            const dot = document.createElement('button');
            dot.className = 'stories__dot' + (i === aIndex ? ' active' : '');
            dot.setAttribute('aria-label', `Artikel ${i + 1}`);
            dot.addEventListener('click', () => { aIndex = i; updateACarousel(); });
            aDotsContainer.appendChild(dot);
        }
    }
    function updateACarousel() {
        if (!aTrack || aCards.length === 0) return;
        const m = measureCard(aTrack);
        aTrack.style.transform = `translateX(-${aIndex * (m.width + m.gap)}px)`;
        if (aDotsContainer) {
            aDotsContainer.querySelectorAll('.stories__dot').forEach((d, i) => d.classList.toggle('active', i === aIndex));
        }
        if (aPrev) aPrev.disabled = aIndex === 0;
        if (aNext) aNext.disabled = aIndex === getAMaxIndex();
        // Sembunyikan nav bila tidak perlu digeser (mis. 3 kartu di layar besar)
        if (aNav) aNav.hidden = getAMaxIndex() === 0;
    }

    if (aNext) aNext.addEventListener('click', () => { aIndex = Math.min(aIndex + 1, getAMaxIndex()); updateACarousel(); });
    if (aPrev) aPrev.addEventListener('click', () => { aIndex = Math.max(aIndex - 1, 0); updateACarousel(); });

    let aResizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(aResizeTimer);
        aResizeTimer = setTimeout(() => {
            aPerView = getAPerView();
            aIndex = Math.min(aIndex, getAMaxIndex());
            buildADots();
            updateACarousel();
        }, 150);
    });

    let aTouchStartX = 0;
    if (aTrack) {
        aTrack.addEventListener('touchstart', (e) => { aTouchStartX = e.touches[0].clientX; }, { passive: true });
        aTrack.addEventListener('touchend', (e) => {
            const diff = aTouchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) aIndex = Math.min(aIndex + 1, getAMaxIndex());
                else aIndex = Math.max(aIndex - 1, 0);
                updateACarousel();
            }
        }, { passive: true });
    }

    if (aCards.length > 0) {
        aPerView = getAPerView();
        buildADots();
        requestAnimationFrame(() => updateACarousel());
    }

    /* ============ KURIKULUM MARQUEE (geser kontinu ke kiri, loop seamless) ============ */
    const kTrack = document.querySelector('.kurikulum-track');
    if (kTrack && kTrack.children.length > 0) {
        const kViewport = kTrack.closest('.kurikulum__viewport') || kTrack;
        const originals = Array.from(kTrack.children);
        originals.forEach(card => {
            const clone = card.cloneNode(true);
            clone.classList.remove('reveal', 'reveal-left', 'reveal-right', 'reveal-scale');
            clone.style.transitionDelay = '0s';
            clone.setAttribute('aria-hidden', 'true');
            kTrack.appendChild(clone);
        });

        const moveReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (moveReduced.matches) {
            kViewport.style.overflowX = 'auto';
        } else {
            kViewport.style.overflowX = 'hidden';
            const setWidth = () => {
                const cards = kTrack.children;
                if (cards.length < 2) return 0;
                const first = cards[0].getBoundingClientRect().left;
                const lastOfFirstSet = cards[originals.length - 1].getBoundingClientRect().right;
                const gap = parseFloat(getComputedStyle(kTrack).gap) || 24;
                return lastOfFirstSet - first + gap;
            };
            let total = 0;
            let lastTs = null;
            let hoverPause = false;
            let dragPause = false;
            let flickVel = 0;
            let inView = false;
            let drag = null;
            let supClick = false;

            const wrap = (v, w) => ((v % w) + w) % w;

            const step = (ts) => {
                if (lastTs !== null) {
                    const delta = ts - lastTs;
                    if (delta < 1000 && inView && !hoverPause && !dragPause) {
                        if (flickVel !== 0) {
                            total += flickVel * delta;
                            flickVel *= Math.pow(0.90, delta / 16);
                            if (Math.abs(flickVel) < 0.02) flickVel = 0;
                        } else {
                            const speed = parseFloat(kTrack.dataset.speed) || 0.06;
                            total += delta * speed;
                        }
                    }
                }
                lastTs = ts;
                requestAnimationFrame(step);
                const w = setWidth();
                if (w > 0) {
                    kTrack.style.transform = `translateX(-${total % w}px)`;
                }
            };
            requestAnimationFrame(step);

            const kSection = kTrack.closest('.kurikulum');
            if (kSection && 'IntersectionObserver' in window) {
                new IntersectionObserver((entries) => {
                    inView = entries.some(e => e.isIntersecting);
                }, { threshold: 0.1 }).observe(kSection);
            } else {
                inView = true;
            }

            kTrack.addEventListener('mouseenter', () => { hoverPause = true; });
            kTrack.addEventListener('mouseleave', () => { hoverPause = false; });
            kTrack.addEventListener('focusin', () => { hoverPause = true; });
            kTrack.addEventListener('focusout', () => { hoverPause = false; });

            /* --- GESER MANUAL (drag + flick) --- */
            const DRAG_CLICK_TOL = 6;
            kTrack.addEventListener('pointerdown', (e) => {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                const w = setWidth();
                if (w <= 0) return;
                drag = {
                    pointerId: e.pointerId,
                    startX: e.clientX,
                    startOffset: wrap(total, w),
                    lastX: e.clientX,
                    lastT: performance.now(),
                    moved: 0,
                    isTouch: e.pointerType !== 'mouse'
                };
                supClick = false;
                dragPause = true;
                flickVel = 0;
                kTrack.classList.add('is-dragging');
                kTrack.setPointerCapture(e.pointerId);
                e.preventDefault();
            });

            kTrack.addEventListener('pointermove', (e) => {
                if (!drag || e.pointerId !== drag.pointerId) return;
                const w = setWidth();
                if (w <= 0) return;
                const dx = drag.startX - e.clientX;
                drag.moved = Math.max(drag.moved, Math.abs(e.clientX - drag.lastX));
                const now = performance.now();
                const dt = now - drag.lastT;
                if (dt > 0) {
                    const v = -(e.clientX - drag.lastX) / dt; // px/ms
                    if (Math.abs(v) < 1.5) flickVel = 0.08 * v + 0.92 * flickVel;
                }
                drag.lastX = e.clientX;
                drag.lastT = now;
                total = wrap(drag.startOffset + dx, w);
                e.preventDefault();
            });

            const endDrag = (e) => {
                if (!drag || (e && e.pointerId !== drag.pointerId)) return;
                const w = setWidth();
                const isClick = drag.moved <= DRAG_CLICK_TOL;
                supClick = !isClick;
                drag = null;
                dragPause = false;
                kTrack.classList.remove('is-dragging');
                if (w > 0) total = wrap(total, w);
            };

            kTrack.addEventListener('pointerup', endDrag);
            kTrack.addEventListener('pointercancel', (e) => {
                if (drag && e.pointerId === drag.pointerId) {
                    dragPause = false;
                    flickVel = 0;
                    kTrack.classList.remove('is-dragging');
                    drag = null;
                    supClick = false;
                }
            });
            // klik (link di dalam kartu) yang menyusul drag jauh tidak boleh navigasi
            kTrack.addEventListener('click', (e) => {
                if (supClick) {
                    supClick = false;
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            });
        }
    }

    /* ============ NEWSLETTER FORM ============ */
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterEmail = document.getElementById('newsletterEmail');
    const newsletterMessage = document.getElementById('newsletterMessage');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = newsletterEmail.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            newsletterMessage.className = 'newsletter__message';
            
            if (!email) {
                newsletterMessage.textContent = 'Mohon masukkan alamat email Anda.';
                newsletterMessage.classList.add('error');
                return;
            }
            if (!emailRegex.test(email)) {
                newsletterMessage.textContent = 'Format email tidak valid. Periksa kembali penulisan alamat email Anda.';
                newsletterMessage.classList.add('error');
                return;
            }
            
            newsletterMessage.textContent = 'Terima kasih! Anda telah berlangganan kabar terbaru dari Almutqon.';
            newsletterMessage.classList.add('success');
            newsletterEmail.value = '';
            
            setTimeout(() => {
                newsletterMessage.textContent = '';
                newsletterMessage.className = 'newsletter__message';
            }, 5000);
        });
    }

    /* ============ DONATION MODAL ============ */
    const donationModal = document.getElementById('donationModal');
    const donationBtns = document.querySelectorAll('[data-donate]');
    const closeBtns = document.querySelectorAll('[data-close]');
    const presetBtns = document.querySelectorAll('.preset-btn');
    const customAmountWrap = document.getElementById('customAmountWrap');
    const customAmountInput = document.getElementById('customAmountInput');
    const donationSubmit = document.getElementById('donationSubmit');
    let lastFocused = null;

    function openModal() {
        lastFocused = document.activeElement;
        const frm = document.getElementById('donationForm');
        const suc = document.getElementById('donationSuccess');
        if (frm) frm.hidden = false;
        if (suc) suc.hidden = true;
        const customReset = document.getElementById('customAmountInput');
        if (customReset) customReset.value = '';
        donationModal.querySelectorAll('.preset-btn').forEach((b) => {
            b.classList.toggle('active', b.dataset.amount === '100000');
        });
        donationModal.classList.add('active');
        donationModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const firstPreset = donationModal.querySelector('.preset-btn.active');
            if (firstPreset) firstPreset.focus();
        }, 100);
    }
    function closeModal() {
        donationModal.classList.remove('active');
        donationModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    donationBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (mobileMenu.classList.contains('active')) window.closeMobileMenu();
            openModal();
        });
    });
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

    if (presetBtns) {
        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                presetBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (btn.dataset.amount === 'other') {
                    customAmountWrap.classList.add('active');
                    setTimeout(() => customAmountInput.focus(), 100);
                } else {
                    customAmountWrap.classList.remove('active');
                    customAmountInput.value = '';
                }
            });
        });
    }

    if (donationSubmit) {
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const flash = (el, msg) => { el.style.borderColor = '#dc2626'; el.focus(); if (msg) alert(msg); setTimeout(() => { el.style.borderColor = ''; }, 2000); };

        donationSubmit.addEventListener('click', () => {
            const namaEl = document.getElementById('donasi_nama');
            const waEl = document.getElementById('donasi_whatsapp');
            if (!namaEl || !namaEl.value.trim()) return flash(namaEl, 'Nama donatur wajib diisi.');
            if (!waEl || !waEl.value.trim()) return flash(waEl, 'Nomor WhatsApp wajib diisi.');

            const activePreset = donationModal.querySelector('.preset-btn.active');
            let jumlah;
            if (activePreset && activePreset.dataset.amount !== 'other') {
                jumlah = parseInt(activePreset.dataset.amount, 10);
            } else {
                jumlah = parseInt(customAmountInput.value, 10);
                if (!jumlah || jumlah < 10000) return flash(customAmountInput, 'Nominal donasi minimal Rp10.000.');
            }

            const metode = donationModal.querySelector('input[name="payment"]:checked').value;
            const token = (donationModal.querySelector('input[name="csrf_token"]') || {}).value || '';

            const fd = new FormData();
            fd.append('csrf_token', token);
            fd.append('nama', namaEl.value.trim());
            fd.append('whatsapp', waEl.value.trim());
            fd.append('jumlah', jumlah);
            fd.append('metode', metode);
            if (metode === 'bank') {
                const r = donationModal.querySelector('[data-pay-panel="bank"] input[name="bank_tujuan"]:checked');
                if (r) fd.append('bank_tujuan', r.value);
            } else if (metode === 'ewallet') {
                const r = donationModal.querySelector('[data-pay-panel="ewallet"] input[name="ewallet_tujuan"]:checked');
                if (r) fd.append('ewallet_tujuan', r.value);
            }

            donationSubmit.disabled = true;
            donationSubmit.innerHTML = '<span class="material-symbols-rounded">sync</span> Memproses...';

            fetch('/api/donasi.php', { method: 'POST', body: fd })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) { alert(data.message); return; }

                    const targetEl = document.getElementById('donationTarget');
                    let html = data.target.baris.map((b) =>
                        '<label class="method"><span class="method__content"><span>' + esc(b) + '</span></span></label>'
                    ).join('');
                    if (data.target.gambar) {
                        html += '<img src="' + esc(data.target.gambar) + '" alt="QRIS Almutqon" style="width: 180px; display: block; margin: 8px auto;">';
                    }
                    targetEl.innerHTML = html;

                    document.getElementById('donasiAmount').textContent = 'Rp' + data.jumlah;
                    document.getElementById('donasiUniqueNote').innerHTML =
                        '<span class="material-symbols-rounded">info</span> Tambahkan kode unik <strong>' + esc(data.unique) +
                        '</strong> di akhir nominal agar mudah dilacak. Konfirmasi otomatis setelah kami terima.';

                    document.getElementById('donationForm').hidden = true;
                    document.getElementById('donationSuccess').hidden = false;
                })
                .catch(() => alert('Terjadi kesalahan saat mencatat donasi. Coba lagi.'))
                .finally(() => {
                    donationSubmit.disabled = false;
                    donationSubmit.innerHTML = 'Lanjutkan Donasi <span class="material-symbols-rounded btn__icon">arrow_forward</span>';
                });
        });
    }

    /* ============ TOGGLE PANEL BANK / E-WALLET ============ */
    document.querySelectorAll('[data-pay-group]').forEach((group) => {
        const methods = group.querySelectorAll('input[type="radio"][name$="_metode_bayar"], input[type="radio"][name="payment"]');
        const sync = () => {
            const checked = group.querySelector('input[type="radio"][name$="_metode_bayar"]:checked, input[type="radio"][name="payment"]:checked');
            const val = checked ? checked.value : 'bank';
            group.querySelectorAll('[data-pay-panel]').forEach((panel) => {
                const active = panel.dataset.payPanel === val;
                panel.hidden = !active;
                panel.querySelectorAll('input, select').forEach((el) => { el.disabled = !active; });
            });
        };
        methods.forEach((r) => r.addEventListener('change', sync));
        const form = group.closest('form');
        if (form) form.addEventListener('reset', () => setTimeout(sync, 0));
        sync();
    });

    const donationDone = document.getElementById('donationDone');
    if (donationDone) {
        donationDone.addEventListener('click', () => closeModal());
    }

    /* ============ ESC KEY & FOCUS TRAP ============ */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeShareMenu();
            if (donationModal.classList.contains('active')) closeModal();
            if (mobileMenu.classList.contains('active')) window.closeMobileMenu();
            if (document.getElementById('loginModal')?.classList.contains('active')) app.closeLoginModal();
            if (document.getElementById('logoutModal')?.classList.contains('active')) {
                document.getElementById('logoutModal').classList.remove('active');
                document.body.style.overflow = '';
            }
            const navAccountBtn = document.getElementById('navAccountBtn');
            if (navAccountBtn && navAccountBtn.getAttribute('aria-expanded') === 'true') {
                navAccountBtn.click();
            }
        }
    });

    /* ============ SHARE MENU (bagikan artikel) ============ */
    let shareMenu = null;
    let shareMenuBtn = null;
    let shareOpenedAt = 0;
    const shareData = { url: '', title: '' };

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise((resolve, reject) => {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(ta);
            }
        });
    }

    function showShareToast(message) {
        let toast = document.querySelector('.share-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'share-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('active');
        clearTimeout(showShareToast._t);
        showShareToast._t = setTimeout(() => toast.classList.remove('active'), 2200);
    }

    function getShareMenu() {
        if (shareMenu) return shareMenu;
        const icons = {
            wa: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.88 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.8.97h.01a7.94 7.94 0 0 0 5.6-13.55zM12.05 18.5h-.01a6.6 6.6 0 0 1-3.36-.92l-.24-.14-2.5.65.67-2.43-.16-.25a6.6 6.6 0 1 1 5.6 3.09zm3.62-4.94c-.2-.1-1.17-.58-1.35-.64-.18-.07-.31-.1-.45.1-.13.2-.5.64-.62.77-.11.13-.23.15-.43.05-.2-.1-.84-.31-1.6-.99-.6-.53-1-1.18-1.12-1.38-.11-.2-.01-.31.09-.41.09-.09.2-.23.3-.35.1-.12.13-.2.2-.33.07-.13.03-.25-.02-.35-.05-.1-.45-1.08-.62-1.48-.16-.39-.33-.34-.45-.34h-.38c-.13 0-.34.05-.52.25-.18.2-.69.67-.69 1.64 0 .97.7 1.9.8 2.03.1.13 1.4 2.14 3.4 3 .47.2.84.33 1.14.42.48.15.92.13 1.26.08.39-.06 1.17-.48 1.34-.94.17-.46.17-.86.12-.94-.05-.08-.18-.13-.38-.23z"/></svg>',
            x: '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            fb: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
            tg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 3.36a1.42 1.42 0 0 0-1.5-.2L2.6 9.9c-.88.34-.84 1.6.06 1.9l4.66 1.5 1.8 5.74c.25.77 1.18 1.02 1.82.51l2.53-2.02 4.4 3.24c.69.5 1.65.17 1.87-.64l3-13.6c.14-.64-.29-1.25-.9-1.42z"/></svg>'
        };
        const items = [
            ['wa', 'WhatsApp'],
            ['x', 'X (Twitter)'],
            ['fb', 'Facebook'],
            ['tg', 'Telegram'],
            ['copy', 'Salin tautan']
        ];
        shareMenu = document.createElement('div');
        shareMenu.className = 'share-menu';
        shareMenu.setAttribute('role', 'menu');
        shareMenu.setAttribute('aria-label', 'Pilihan bagikan artikel');
        shareMenu.innerHTML = items.map(([k, label]) =>
            '<button type="button" class="share-menu__item share-menu__item--' + k + '" data-action="' + k + '" role="menuitem">' +
            (k === 'copy' ? '<span class="material-symbols-rounded share-menu__icon share-menu__icon--copy" aria-hidden="true">link</span>' : '<span class="share-menu__icon share-menu__icon--' + k + '" aria-hidden="true">' + icons[k] + '</span>') +
            '<span>' + label + '</span></button>'
        ).join('');
        document.body.appendChild(shareMenu);
        return shareMenu;
    }

    function closeShareMenu() {
        if (shareMenu && shareMenu.classList.contains('active')) {
            shareMenu.classList.remove('active');
            if (shareMenuBtn) shareMenuBtn.setAttribute('aria-expanded', 'false');
        }
    }

    function onShareViewportChange() {
        if (Date.now() - shareOpenedAt > 150) closeShareMenu();
    }

    function positionShareMenu(btn) {
        const menu = getShareMenu();
        const r = btn.getBoundingClientRect();
        menu.style.visibility = 'hidden';
        menu.classList.remove('active');
        const mw = menu.offsetWidth || 210;
        const mh = menu.offsetHeight || 240;
        menu.style.visibility = '';
        menu.classList.add('active');
        let top = r.top - mh - 8;
        if (top < 8) top = Math.min(r.bottom + 8, window.innerHeight - mh - 8);
        let left = r.right - mw;
        if (left < 8) left = 8;
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
    }

    function openShareMenu(btn) {
        shareMenuBtn = btn;
        shareData.url = btn.getAttribute('data-share-url') || '';
        shareData.title = btn.getAttribute('data-share-title') || 'Al-Mutqon Arabic Learning';
        const menu = getShareMenu();
        if (menu.classList.contains('active')) {
            closeShareMenu();
            return;
        }
        menu.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
        shareOpenedAt = Date.now();
        positionShareMenu(btn);
    }

    document.addEventListener('click', (e) => {
        const item = e.target.closest('.share-menu__item');
        if (item) {
            const action = item.getAttribute('data-action');
            if (action === 'copy') {
                copyToClipboard(shareData.url).then(() => {
                    showShareToast('Tautan disalin ke papan klip!');
                }).catch(() => {
                    showShareToast('Gagal menyalin tautan.');
                });
            } else {
                const build = {
                    wa: 'https://wa.me/?text=' + encodeURIComponent(shareData.title + ' ' + shareData.url),
                    x: 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareData.url) + '&text=' + encodeURIComponent(shareData.title),
                    fb: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareData.url),
                    tg: 'https://t.me/share/url?url=' + encodeURIComponent(shareData.url) + '&text=' + encodeURIComponent(shareData.title)
                }[action];
                if (build) window.open(build, '_blank', 'noopener');
            }
            closeShareMenu();
            return;
        }

        const btn = e.target.closest('.article-card__share');
        if (btn) {
            e.preventDefault();
            openShareMenu(btn);
            return;
        }

        const copyBtn = e.target.closest('[data-copy]');
        if (copyBtn) {
            copyToClipboard(copyBtn.getAttribute('data-copy')).then(() => {
                showShareToast('Tautan disalin ke papan klip!');
            }).catch(() => {
                showShareToast('Gagal menyalin tautan.');
            });
            return;
        }

        closeShareMenu();
    });

    window.addEventListener('scroll', onShareViewportChange, { passive: true });
    window.addEventListener('resize', onShareViewportChange);

    if (donationModal) {
        donationModal.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            const focusable = donationModal.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }
});