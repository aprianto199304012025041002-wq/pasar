/* SIMS Monitor — app.js */
document.addEventListener('DOMContentLoaded', function () {

    var btnMenu = document.getElementById('btn-menu');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var menuIcon = document.getElementById('menu-icon');
    var wrapper = document.getElementById('app-wrapper');

    /* ── Mobile sidebar ── */
    if (btnMenu && sidebar && overlay) {
        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            if (menuIcon) menuIcon.className = 'ti ti-x';
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            if (menuIcon) menuIcon.className = 'ti ti-menu-2';
            document.body.style.overflow = '';
        }
        btnMenu.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        overlay.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) closeSidebar();
        });
    }

    /* ── Desktop collapse ── */
    var btnCollapse = document.getElementById('btn-collapse');
    var colIcon = document.getElementById('collapse-icon');
    var KEY = 'sidebar_collapsed';

    function setCollapsed(collapsed, save) {
        if (!sidebar || !wrapper) return;
        if (collapsed) {
            sidebar.classList.add('collapsed');
            wrapper.classList.add('sidebar-collapsed');
            if (colIcon) colIcon.className = 'ti ti-chevron-right';
        } else {
            sidebar.classList.remove('collapsed');
            wrapper.classList.remove('sidebar-collapsed');
            if (colIcon) colIcon.className = 'ti ti-chevron-left';
        }
        if (save) localStorage.setItem(KEY, collapsed ? '1' : '0');
    }

    if (window.innerWidth >= 768) {
        setCollapsed(localStorage.getItem(KEY) === '1', false);
    }

    if (btnCollapse) {
        btnCollapse.addEventListener('click', function () {
            setCollapsed(!sidebar.classList.contains('collapsed'), true);
        });
    }

    /* ── Filter buttons ── */
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

    /* ── Auto-submit selects ── */
    document.querySelectorAll('select.auto-submit').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    });

    /* ── Search debounce ── */
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

function fmtNum(n) {
    return Number(n).toLocaleString('id-ID');
}

// ── Filter Collapsible ────────────────────────────────────────────────────────
// Reusable untuk semua halaman yang punya .filter-compact + .filter-toggle-bar
(function () {
    var STORAGE_KEY_PREFIX = 'filter_collapsed_';

    function isMobile() {
        return window.innerWidth < 768;
    }

    function initFilterToggle(compactEl, toggleEl) {
        if (!compactEl || !toggleEl) return;

        var storageKey = STORAGE_KEY_PREFIX + (compactEl.id || 'default');

        // Tentukan state awal:
        // - Kalau ada filter aktif (badge count ada) → selalu expanded
        // - Kalau ada state tersimpan di localStorage → pakai itu
        // - Default: mobile = collapsed, desktop = expanded
        var hasBadge   = !!compactEl.querySelector('[style*="background:var(--blue)"]');
        var stored     = localStorage.getItem(storageKey);
        var isCollapsed;

        if (hasBadge) {
            isCollapsed = false; // ada filter aktif → paksa expand
        } else if (stored !== null) {
            isCollapsed = stored === '1';
        } else {
            isCollapsed = isMobile(); // default: mobile collapsed
        }

        if (isCollapsed) {
            compactEl.classList.add('collapsed');
            toggleEl.setAttribute('aria-expanded', 'false');
        }

        toggleEl.addEventListener('click', function () {
            var collapsed = compactEl.classList.toggle('collapsed');
            toggleEl.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            localStorage.setItem(storageKey, collapsed ? '1' : '0');

            // Kalau di peta, recalculate tinggi map setelah animasi selesai
            if (typeof setMapHeight === 'function') {
                setTimeout(function () {
                    setMapHeight();
                    if (typeof map !== 'undefined' && map.invalidateSize) {
                        map.invalidateSize();
                    }
                }, 270); // setelah transition 0.25s selesai
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // data.php
        initFilterToggle(
            document.getElementById('data-filter-compact'),
            document.getElementById('data-filter-toggle')
        );
        // peta.php
        initFilterToggle(
            document.getElementById('peta-filter-compact'),
            document.getElementById('peta-filter-toggle')
        );
    });
})();
