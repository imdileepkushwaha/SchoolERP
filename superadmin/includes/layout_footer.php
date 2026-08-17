        </main>
    </div>
</div>
<script>
(function () {
    var toggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    function closeMenu() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('show', sidebar.classList.contains('open'));
        });
    }
    if (overlay) overlay.addEventListener('click', closeMenu);

    document.querySelectorAll('[data-dropdown]').forEach(function (wrap) {
        var btn = wrap.querySelector('[data-dropdown-toggle]');
        var menu = wrap.querySelector('[data-dropdown-menu]');
        if (!btn) return;
        function setOpen(open) {
            wrap.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = !wrap.classList.contains('open');
            document.querySelectorAll('[data-dropdown].open').forEach(function (other) {
                if (other !== wrap) {
                    other.classList.remove('open');
                    var otherBtn = other.querySelector('[data-dropdown-toggle]');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });
            setOpen(willOpen);
        });
        if (menu) {
            menu.addEventListener('click', function (e) { e.stopPropagation(); });
        }
        document.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
    });
})();
</script>
</body>
</html>
