        </main>
    </div><!-- /admin__main -->
</div><!-- /admin -->

<script>
(function(){
  var sb = document.getElementById('adminSidebar');
  var ov = document.getElementById('adminOverlay');
  var hb = document.getElementById('adminHamburger');
  if (!sb || !hb) return;
  function openMenu() { sb.classList.add('open'); if(ov) ov.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeMenu(){ sb.classList.remove('open'); if(ov) ov.classList.remove('open'); document.body.style.overflow = ''; }
  hb.addEventListener('click', openMenu);
  if (ov) ov.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
})();
</script>
</body>
</html>
