        </main>
    </div><!-- /admin__main -->
</div><!-- /admin -->

<script>
(function(){
  // Sidebar mobile (pola yang sama dengan dashboard admin).
  var sb = document.getElementById('adminSidebar');
  var ov = document.getElementById('adminOverlay');
  var hb = document.getElementById('adminHamburger');
  function openMenu() { if(sb) sb.classList.add('open'); if(ov) ov.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeMenu(){ if(sb) sb.classList.remove('open'); if(ov) ov.classList.remove('open'); document.body.style.overflow = ''; }
  if (hb) hb.addEventListener('click', openMenu);
  if (ov) ov.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
})();
</script>
</body>
</html>
