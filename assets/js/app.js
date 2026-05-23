/* SIMF Monitor — app.js */

document.addEventListener('DOMContentLoaded', function () {

    // ── Hamburger / mobile drawer ───────────────────────────────
    var btnMenu = document.getElementById('btn-menu');
    var drawer = document.getElementById('mobile-drawer');
    var backdrop = document.getElementById('nav-backdrop');
    var menuIcon = document.getElementById('menu-icon');

    if (btnMenu && drawer && backdrop) {
        function openNav() {
            drawer.style.display = 'flex';
            backdrop.classList.add('show');
            if (menuIcon) menuIcon.className = 'ti ti-x';
            document.body.style.overflow = 'hidden';
        }
        function closeNav() {
            drawer.style.display = 'none';
            backdrop.classList.remove('show');
            if (menuIcon) menuIcon.className = 'ti ti-menu-2';
            document.body.style.overflow = '';
        }

        btnMenu.addEventListener('click', function () {
            drawer.style.display === 'none' ? openNav() : closeNav();
        });
        backdrop.addEventListener('click', closeNav);
        drawer.querySelectorAll('.drawer-link').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) closeNav();
        });
    }

    // ── Service filter pills — sync to hidden select / URL param ─
    document.querySelectorAll('.filter-btn[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.dataset.group || 'service';
            document.querySelectorAll('.filter-btn[data-group="' + group + '"]')
                .forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var target = document.getElementById(btn.dataset.target);
            if (target) {
                target.value = btn.dataset.filter;
                var form = target.closest('form');
                if (form) form.submit();
            }
        });
    });

    // ── Auto-submit select filters on change ─────────────────────
    document.querySelectorAll('select.auto-submit').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    });

    // ── Debounced search input ────────────────────────────────────
    var searchInput = document.getElementById('q');
    if (searchInput) {
        var timeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                var form = searchInput.closest('form');
                if (form) form.submit();
            }, 400);
        });
    }

});

// ── Service badge class helper (used in peta.php) ────────────
function serviceBadgeClass(service) {
    var map = {
        'Fixed Service': 'badge-fixed',
        'Land Mobile (public)': 'badge-lm-pub',
        'Land Mobile (private)': 'badge-lm-pri',
        'Broadcast': 'badge-broadcast',
        'Aeronautical': 'badge-aero',
    };
    return map[service] || 'badge-other';
}

// ── Format number with thousand separator ────────────────────
function fmtNum(n) {
    return Number(n).toLocaleString('id-ID');
}