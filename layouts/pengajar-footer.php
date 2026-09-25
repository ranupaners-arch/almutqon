        </main>
    </div><!-- /admin__main -->
</div><!-- /admin -->

<script>
(function(){
  var sb = document.getElementById('adminSidebar');
  var ov = document.getElementById('adminOverlay');
  var hb = document.getElementById('adminHamburger');
  function openMenu() { if(sb) sb.classList.add('open'); if(ov) ov.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeMenu(){ if(sb) sb.classList.remove('open'); if(ov) ov.classList.remove('open'); document.body.style.overflow = ''; }
  if (hb) hb.addEventListener('click', openMenu);
  if (ov) ov.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });

  var out = document.getElementById('pgLogoutBtn');
  if (out) out.addEventListener('click', function(){
    if (!confirm('Keluar dari dashboard pengajar?')) return;
    fetch('/auth/logout-pengajar.php').finally(function(){ window.location.href = '/auth/login-pengajar.php'; });
  });

  var EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
  var EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a10.44 10.44 0 0 1-1.75 3.02"/><path d="M6.61 6.61A10.44 10.44 0 0 0 2.458 12c1.274 4.057 5.064 7 9.542 7a10.4 10.4 0 0 0 3.968-.806"/><path d="M2 2l20 20"/></svg>';
  document.querySelectorAll('.admin__password-toggle').forEach(function(btn){
    btn.innerHTML = EYE;
    btn.addEventListener('click', function(){
      var input = document.getElementById(btn.getAttribute('data-toggle'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? EYE_OFF : EYE;
      btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });
})();
</script>
</body>
</html>
