/* ===================================================================
APP LOGIC & AUTH
=================================================================== */
window.app = {
    db: {
        currentUser: null,
    },

    init() {
        /* ============ AUTH MODAL TOGGLE ============ */
        const authModal = document.getElementById('loginModal');
        if (authModal) {
            authModal.addEventListener('click', (e) => {
                const toggleBtn = e.target.closest('#authToggleBtn');
                if (toggleBtn) {
                    e.preventDefault();
                    this.toggleAuthMode();
                }
            });
        }

        /* ============ HARGA LIVE MODAL PENDAFTARAN ============ */
        if (typeof window.AMQ !== 'undefined' && window.AMQ.biaya) this.initPricing();

        /* ============ AUTH FORM SUBMIT ============ */
        const authForm = document.getElementById('authForm');
        const self = this;
        if (authForm) {
            authForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const title = document.getElementById('authTitle');
                const isRegister = title && title.innerText.trim() === 'Daftar';
                const url = isRegister ? '/auth/register.php' : '/auth/login.php';
                const formData = new FormData();

                formData.append('csrf_token', (document.querySelector('#authForm input[name="csrf_token"]') || {}).value || '');
                formData.append('email', document.getElementById('authEmail').value);
                formData.append('password', document.getElementById('authPass').value);
                const rememberEl = document.getElementById('authRemember');
                if (rememberEl) formData.append('remember', rememberEl.checked ? '1' : '');

                if (isRegister) {
                    const name = document.getElementById('authName').value;
                    const confirm = document.getElementById('authConfirm').value;
                    const pass = document.getElementById('authPass').value;
                    if (!name) return alert('Nama lengkap wajib diisi.');
                    if (!confirm) return alert('Konfirmasi password wajib diisi.');
                    if (pass !== confirm) return alert('Password dan konfirmasi tidak cocok.');
                    formData.append('name', name);
                }

                fetch(url, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            self.db.currentUser = data.user;
                            window.currentUser = data.user;
                            self.closeLoginModal();
                            self.updateUI();
                            authForm.reset();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(() => alert('Terjadi kesalahan. Coba lagi.'));
            });
        }

        /* ============ BUTTON BINDINGS ============ */
        const heroRegisterBtn = document.getElementById('heroRegisterBtn');
        if (heroRegisterBtn) heroRegisterBtn.addEventListener('click', () => this.openLoginModal());

        const navLoginBtn = document.getElementById('navLoginBtn');
        if (navLoginBtn) navLoginBtn.addEventListener('click', () => this.openLoginModal());

        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        if (mobileLoginBtn) {
            mobileLoginBtn.addEventListener('click', () => {
                if (window.closeMobileMenu) window.closeMobileMenu();
                this.openLoginModal();
            });
        }

        const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');
        if (mobileLogoutBtn) {
            mobileLogoutBtn.addEventListener('click', () => {
                if (window.closeMobileMenu) window.closeMobileMenu();
                this.logout();
            });
        }

        /* ============ NAV ACCOUNT DROPDOWN ============ */
        const accountBtn = document.getElementById('navAccountBtn');
        const accountMenu = document.getElementById('navAccountMenu');

        const setAccountMenu = (open) => {
            if (!accountBtn || !accountMenu) return;
            accountBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            accountMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
            accountMenu.classList.toggle('active', open);
        };

        if (accountBtn) {
            accountBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                setAccountMenu(!accountMenu.classList.contains('active'));
            });
        }

        document.addEventListener('click', (e) => {
            if (accountMenu && accountMenu.classList.contains('active') && !e.target.closest('.nav-account')) {
                setAccountMenu(false);
            }
        });

        const navLogoutBtn = document.getElementById('navLogoutBtn');
        if (navLogoutBtn) {
            navLogoutBtn.addEventListener('click', () => {
                setAccountMenu(false);
                this.logout();
            });
        }

        /* ============ CLOSE MODAL BINDINGS ============ */
        document.querySelectorAll('[data-close-login]').forEach(b => {
            b.addEventListener('click', () => this.closeLoginModal());
        });
        document.querySelectorAll('[data-close-offline]').forEach(b => {
            b.addEventListener('click', () => this.closeOfflineModal());
        });
        document.querySelectorAll('[data-close-online]').forEach(b => {
            b.addEventListener('click', () => this.closeOnlineModal());
        });
        document.querySelectorAll('[data-close-dauroh]').forEach(b => {
            b.addEventListener('click', () => this.closeDaurohModal());
        });
        document.querySelectorAll('[data-close]').forEach(b => {
            b.addEventListener('click', () => this.closeDonationModal());
        });

        document.querySelectorAll('[data-close-logout]').forEach(b => {
            b.addEventListener('click', () => {
                const m = document.getElementById('logoutModal');
                if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
            });
        });

        const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
        if (confirmLogoutBtn) confirmLogoutBtn.addEventListener('click', () => this.confirmLogout());

        /* ============ GET USER FROM PHP SESSION ============ */
        this.db.currentUser = window.currentUser || null;
        this.updateUI();
    },

    /* ============ AUTH MODE TOGGLE ============ */
    toggleAuthMode() {
        const title = document.getElementById('authTitle');
        if (!title) return;
        const isLogin = title.innerText === 'Masuk';

        document.getElementById('authTitle').innerText = isLogin ? 'Daftar' : 'Masuk';
        document.getElementById('authSubtitle').innerText = isLogin ? 'Buat akun baru untuk mulai belajar.' : 'Gunakan akun Anda untuk melanjutkan belajar.';
        document.getElementById('authSubmitBtn').innerText = isLogin ? 'Daftar' : 'Masuk';

        const nameGroup = document.getElementById('authNameGroup');
        const confirmGroup = document.getElementById('authConfirmGroup');
        const authOptions = document.getElementById('authOptions');

        if (nameGroup) nameGroup.style.display = isLogin ? 'block' : 'none';
        if (confirmGroup) confirmGroup.style.display = isLogin ? 'block' : 'none';
        if (authOptions) authOptions.style.display = isLogin ? 'none' : 'flex';

        const footer = document.getElementById('authFooterText');
        footer.innerHTML = isLogin
            ? 'Sudah punya akun? <a href="#" class="auth__link" id="authToggleBtn">Masuk</a>'
            : 'Belum punya akun? <a href="#" class="auth__link" id="authToggleBtn">Daftar</a>';
    },

    /* ============ MODAL CONTROLS ============ */
    openLoginModal() {
        const m = document.getElementById('loginModal');
        if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
    },
    closeLoginModal() {
        const m = document.getElementById('loginModal');
        if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
    },
    logout() {
        const m = document.getElementById('logoutModal');
        if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
    },
    confirmLogout() {
        fetch('/auth/logout.php').finally(() => {
            this.db.currentUser = null;
            window.currentUser = null;
            document.documentElement.dataset.theme = 'light';
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            this.updateUI();
        });
    },

    /* ============ UI UPDATE ============ */
    renderAvatar(el, user) {
        if (!el) return;
        const name = (user.name || user.email || '?').trim();
        if (user.avatar) {
            el.innerHTML = `<img src="/${user.avatar}" alt="${name}">`;
        } else {
            el.textContent = name.charAt(0).toUpperCase();
        }
    },

    updateUI() {
        const user = this.db.currentUser;
        const loginBtn = document.getElementById('navLoginBtn');
        const account = document.getElementById('navAccount');
        const heroRegisterBtn = document.getElementById('heroRegisterBtn');
        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const mobileAccount = document.getElementById('mobileAccount');

        if (user) {
            if (loginBtn) loginBtn.hidden = true;
            if (account) {
                account.hidden = false;
                const nameEl = document.getElementById('navName');
                const emailEl = document.getElementById('navEmail');
                if (nameEl) nameEl.textContent = user.name || '';
                if (emailEl) emailEl.textContent = user.email || '';
                this.renderAvatar(document.getElementById('navAvatar'), user);
                this.renderAvatar(document.getElementById('navAvatarLg'), user);
            }
            if (heroRegisterBtn) heroRegisterBtn.hidden = true;
            if (mobileLoginBtn) mobileLoginBtn.hidden = true;
            if (mobileAccount) {
                mobileAccount.hidden = false;
                const mName = document.getElementById('mobileAccountName');
                const mEmail = document.getElementById('mobileAccountEmail');
                if (mName) mName.textContent = user.name || '';
                if (mEmail) mEmail.textContent = user.email || '';
                this.renderAvatar(document.getElementById('mobileAvatar'), user);
            }
        } else {
            if (loginBtn) loginBtn.hidden = false;
            if (account) account.hidden = true;
            if (heroRegisterBtn) heroRegisterBtn.hidden = false;
            if (mobileLoginBtn) mobileLoginBtn.hidden = false;
            if (mobileAccount) mobileAccount.hidden = true;
        }
    },

    /* ============ DONATION MODAL ============ */
    closeDonationModal() { const m = document.getElementById('donationModal'); if (m) { m.classList.remove('active'); document.body.style.overflow = ''; } },

    /* ============ FORM MODALS ============ */
    openOfflineForm() {
        if (!this.db.currentUser) return this.openLoginModal();
        const m = document.getElementById('offlineModal');
        if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
    },
    closeOfflineModal() { const m = document.getElementById('offlineModal'); if (m) { m.classList.remove('active'); document.body.style.overflow = ''; } },

    openOnlineForm() {
        if (!this.db.currentUser) return this.openLoginModal();
        const m = document.getElementById('onlineModal');
        if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
    },
    closeOnlineModal() { const m = document.getElementById('onlineModal'); if (m) { m.classList.remove('active'); document.body.style.overflow = ''; } },

    openDaurohForm() {
        if (!this.db.currentUser) return this.openLoginModal();
        const m = document.getElementById('daurohModal');
        if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
    },
    closeDaurohModal() { const m = document.getElementById('daurohModal'); if (m) { m.classList.remove('active'); document.body.style.overflow = ''; } },

    initThemeSettings() {
        // Kontrol tema kini ditangani partial bersama layouts/theme-switch.php.
    },

    /* ============ HARGA LIVE + INFRASTRUKTUR MODAL ============ */
    initPricing() {
        const C = window.AMQ;
        if (!C || !C.biaya) return;
        const rp = window.AMQ_RP || (n => 'Rp' + n.toLocaleString('id-ID'));

        const bind = (type, inputs, compute) => {
            const box = document.querySelector(`[data-price-box="${type}"]`);
            if (!box) return;
            const totEl = box.querySelector('.pb-total');
            const ketEl = box.querySelector('.pb-ket');
            const update = () => { const [t, k] = compute(); totEl.textContent = rp(t); ketEl.textContent = k; };
            inputs.forEach(([sel, evt]) => { const el = document.getElementById(sel); if (el) el.addEventListener(evt, update); });
            update();
        };

        // offline / online — pilihan metode Privat/Non-Privat
        bind('offline', [['offline_metode', 'change']], () => {
            const m = (document.getElementById('offline_metode') || {}).value || 'Privat';
            const d = (C.biaya.offline || {})[m] || {};
            return [d.total || 0, d.ket || ''];
        });
        bind('online', [['online_metode', 'change']], () => {
            const m = (document.getElementById('online_metode') || {}).value || 'Privat';
            const d = (C.biaya.online || {})[m] || {};
            return [d.total || 0, d.ket || ''];
        });

        // dauroh — metode Online/Offline + jumlah peserta
        bind('dauroh', [['dauroh_metode', 'change'], ['dauroh_jumlah', 'input']], () => {
            const m = (document.getElementById('dauroh_metode') || {}).value || 'Online';
            const n = Math.max((C.biaya.dauroh || {}).min || 1, parseInt((document.getElementById('dauroh_jumlah') || {}).value, 10) || 1);
            const per = ((C.biaya.dauroh || {}).per_orang || {})[m] || 0;
            return [per * n, m + ' · ' + rp(per) + ' / orang × ' + n + ' peserta'];
        });
    },

    submitOffline(e) { e.preventDefault(); this.submitPendaftar(e.target, 'offline'); },
    submitOnline(e) { e.preventDefault(); this.submitPendaftar(e.target, 'online'); },
    submitDauroh(e) { e.preventDefault(); this.submitPendaftar(e.target, 'dauroh'); },

    submitPendaftar(form, program) {
        try {
            const token = form.querySelector('input[name="csrf_token"]').value;
            const fd = new FormData(form);
            const bayarRadio = form.querySelector('input[type="radio"][name$="_metode_bayar"]:checked');
            fd.append('metode_bayar', (bayarRadio && bayarRadio.value) || 'bank');
            fd.append('program', program);
            fetch('/api/pendaftar.php', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': token } })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) {
                        alert(data.message || 'Terjadi kesalahan.');
                        return;
                    }
                    form.reset();
                    const map = { offline: 'closeOfflineModal', online: 'closeOnlineModal', dauroh: 'closeDaurohModal' };
                    if (this[map[program]]) this[map[program]]();
                    window.location.href = data.redirect || '/pages/akun/profil.php';
                })
                .catch(() => alert('Terjadi kesalahan saat mengirim. Coba lagi.'));
        } catch (err) {
            alert('Form belum siap. Muat ulang halaman.');
        }
    }
};

// Inisialisasi App setelah DOM siap
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});