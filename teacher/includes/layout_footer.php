        </main>
    </div>
</div>
<script>
(function () {
    var toggle = document.getElementById('tpMenuToggle');
    var sidebar = document.getElementById('tpSidebar');
    var overlay = document.getElementById('tpOverlay');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }
})();
</script>
</body>
</html>
