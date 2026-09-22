</main>

<footer class="footer" id="footer">
<div class="container footer__inner">
<div class="footer__grid">
<div class="footer__brand">
<a href="#beranda" class="logo logo--footer">
<img src="/assets/1.png" alt="Logo Almutqon" class="logo__img">
<span class="logo__text">
<span class="logo__name">ALMUTQON</span>
<span class="logo__sub">ARABIC LEARNING</span>
</span>
</a>
<p class="footer__desc">Platform pendidikan bahasa Arab yang berkomitmen untuk menjaga kualitas pendidikan dan memberikan pengalaman belajar yang optimal.</p>
<div class="footer__social">
<a href="https://www.instagram.com/almutqon.id?igsi=MWxrNWlnbnM0YTgzcQ==" class="social-link" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/></svg></a>
<a href="#" class="social-link" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
<a href="#" class="social-link" aria-label="YouTube"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-2C18.88 4 12 4 12 4s-6.88 0-8.59.42a2.78 2.78 0 0 0-1.95 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.41 19c1.71.42 8.59.42 8.59.42s6.88 0 8.59-.42a2.78 2.78 0 0 0 1.95-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48" fill="#0B1120"/></svg></a>
<a href="https://wa.me/6282258442647" target="_blank" rel="noopener" class="social-link" aria-label="WhatsApp"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.88 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.8.97h.01a7.94 7.94 0 0 0 5.6-13.55zM12.05 18.5h-.01a6.6 6.6 0 0 1-3.36-.92l-.24-.14-2.5.65.67-2.43-.16-.25a6.6 6.6 0 1 1 5.6 3.09zm3.62-4.94c-.2-.1-1.17-.58-1.35-.64-.18-.07-.31-.1-.45.1-.13.2-.5.64-.62.77-.11.13-.23.15-.43.05-.2-.1-.84-.31-1.6-.99-.6-.53-1-1.18-1.12-1.38-.11-.2-.01-.31.09-.41.09-.09.2-.23.3-.35.1-.12.13-.2.2-.33.07-.13.03-.25-.02-.35-.05-.1-.45-1.08-.62-1.48-.16-.39-.33-.34-.45-.34h-.38c-.13 0-.34.05-.52.25-.18.2-.69.67-.69 1.64 0 .97.7 1.9.8 2.03.1.13 1.4 2.14 3.4 3 .47.2.84.33 1.14.42.48.15.92.13 1.26.08.39-.06 1.17-.48 1.34-.94.17-.46.17-.86.12-.94-.05-.08-.18-.13-.38-.23z"/></svg></a>
</div>
</div>
<div class="footer__col">
<h3 class="footer__heading">Navigasi</h3>
<ul class="footer__links">
<li><a href="#tentang">Tentang Kami</a></li>
<li><a href="#program">Program</a></li>
<li><a href="#kurikulum">Kurikulum</a></li>
<li><a href="#artikel">Artikel</a></li>
</ul>
</div>
<div class="footer__col"> 
<h3 class="footer__heading">Bantuan</h3>
<ul class="footer__links">
<li><a href="/pages/info/faq.php">FAQ</a></li>
<li><a href="/pages/info/kebijakan-privasi.php">Kebijakan Privasi</a></li>
<li><a href="/pages/info/syarat-ketentuan.php">Syarat & Ketentuan</a></li>
<li><a href="/pages/akun/ganti-password.php">Ganti Password</a></li>
<li><a href="#kontak">Kontak</a></li>
</ul>
</div>
<div class="footer__col">
<h3 class="footer__heading">Kontak</h3>
<ul class="footer__contact">
<li><span class="material-symbols-rounded">location_on</span><a href="https://maps.apple/p/DjIt..__TtN7uM">Jln. Nanas I Gerdu Giripurwo Wonogiri</a></li>
<li><span class="material-symbols-rounded">mail</span><a href="mailto:admin.almutqon@gmail.com">admin.almutqon@gmail.com</a></li>
<li><span class="material-symbols-rounded">call</span><a href="https://wa.me/6282258442647" target="_blank" rel="noopener">+62 822-5844-2647</a></li>
</ul>
</div>
</div>
<div class="footer__bottom">
<p>&copy; 2026 Al-Mutqon Arabic Learning. All rights reserved.</p>
</div>
</div>
</footer>

<?php require_once __DIR__ . '/../config/csrf.php'; ?>
<?php include __DIR__ . '/modals.php'; ?>

<script src="/js/ui.js?v=20260917e"></script>
<script src="/js/alfarahidi.js?v=20260917e"></script>
<script src="/js/app.js?v=20260917e"></script>
<script>

window.currentUser = <?php echo json_encode($currentUser); ?>;

</script>
</body>
</html>