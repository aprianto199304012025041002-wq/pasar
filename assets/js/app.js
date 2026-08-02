/* PASAR — app.js | KOMENK design language */

document.addEventListener('DOMContentLoaded', function () {

    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebar-overlay');
    var btnMenu  = document.getElementById('btn-menu');
    var wrapper  = document.getElementById('app-wrapper');

    // ── Mobile sidebar ────────────────────────────────────────
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (btnMenu) btnMenu.addEventListener('click', function () {
        sidebar && sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Close on nav item click (mobile)
    if (sidebar) sidebar.querySelectorAll('.nav-item').forEach(function (el) {
        el.addEventListener('click', function () {
            if (window.innerWidth < 1024) closeSidebar();
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) closeSidebar();
    });

    // ── Desktop sidebar collapse ──────────────────────────────
    var btnCollapse = document.getElementById('btn-collapse');
    var KEY_COL = 'sidebar_collapsed';

    function setCollapsed(collapsed, save) {
        if (!sidebar) return;
        sidebar.classList.toggle('collapsed', collapsed);
        if (save) localStorage.setItem(KEY_COL, collapsed ? '1' : '0');
        // Recalculate map after transition
        setTimeout(function () {
            if (typeof setMapHeight === 'function') setMapHeight();
            if (typeof map !== 'undefined' && map && map.invalidateSize) map.invalidateSize();
        }, 360);
    }

    if (window.innerWidth >= 1024) {
        setCollapsed(localStorage.getItem(KEY_COL) === '1', false);
    }

    if (btnCollapse) {
        btnCollapse.addEventListener('click', function () {
            setCollapsed(!sidebar.classList.contains('collapsed'), true);
        });
    }

    // ── Theme toggle ──────────────────────────────────────────
    var btnTheme  = document.getElementById('btn-theme');
    var themeIcon = document.getElementById('theme-icon');
    var html      = document.documentElement;

    function setTheme(theme, save) {
        html.setAttribute('data-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
        }
        if (save) {
            document.cookie = 'theme=' + theme + ';path=/;max-age=31536000;SameSite=Lax';
        }
    }

    if (btnTheme) {
        btnTheme.addEventListener('click', function () {
            var cur = html.getAttribute('data-theme') || 'dark';
            setTheme(cur === 'dark' ? 'bright' : 'dark', true);
        });
    }

    // ── Avatar menu ───────────────────────────────────────────
    var btnAvatar  = document.getElementById('btn-avatar');
    var avatarMenu = document.getElementById('avatar-menu');

    if (btnAvatar && avatarMenu) {
        btnAvatar.addEventListener('click', function (e) {
            e.stopPropagation();
            avatarMenu.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            avatarMenu.classList.remove('open');
        });
        avatarMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // ── Auto-submit selects ───────────────────────────────────
    document.querySelectorAll('select.auto-submit').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    });

    // ── Search debounce ───────────────────────────────────────
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

    // ── Filter collapsible ────────────────────────────────────
    var STORAGE_KEY_PREFIX = 'filter_collapsed_';

    function initFilterToggle(compactEl, toggleEl) {
        if (!compactEl || !toggleEl) return;

        var storageKey = STORAGE_KEY_PREFIX + (compactEl.id || 'default');
        var hasBadge   = !!compactEl.querySelector('[style*="background:var(--blue)"]');
        var stored     = localStorage.getItem(storageKey);
        var isCollapsed;

        if (hasBadge) {
            isCollapsed = false;
        } else if (stored !== null) {
            isCollapsed = stored === '1';
        } else {
            isCollapsed = window.innerWidth < 768;
        }

        if (isCollapsed) {
            compactEl.classList.add('collapsed');
            toggleEl.setAttribute('aria-expanded', 'false');
        }

        toggleEl.addEventListener('click', function () {
            var collapsed = compactEl.classList.toggle('collapsed');
            toggleEl.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            localStorage.setItem(storageKey, collapsed ? '1' : '0');

            if (typeof setMapHeight === 'function') {
                setTimeout(function () {
                    setMapHeight();
                    if (typeof map !== 'undefined' && map && map.invalidateSize) {
                        map.invalidateSize();
                    }
                }, 270);
            }
        });
    }

    initFilterToggle(
        document.getElementById('data-filter-compact'),
        document.getElementById('data-filter-toggle')
    );
    initFilterToggle(
        document.getElementById('peta-filter-compact'),
        document.getElementById('peta-filter-toggle')
    );

    // ── Expand rows (dashboard) ───────────────────────────────
    document.querySelectorAll('.expand-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var target = row.dataset.target;
            if (!target) return;
            row.classList.toggle('open');
            var children = document.getElementById(target);
            if (children) children.classList.toggle('open');
        });
    });

    // ── Client search (dashboard) ─────────────────────────────
    var clientSearch = document.getElementById('client-search');
    if (clientSearch) {
        clientSearch.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.client-item').forEach(function (item) {
                var name = (item.querySelector('.client-name') || item).textContent.toLowerCase();
                item.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

});

// ── Helpers (global) ─────────────────────────────────────────
function serviceBadgeClass(service) {
    var map = {
        'Fixed Service':         'badge-fixed',
        'Land Mobile (public)':  'badge-lm-pub',
        'Land Mobile (private)': 'badge-lm-pri',
        'Broadcast':             'badge-broadcast',
        'Aeronautical':          'badge-aero',
    };
    return map[service] || 'badge-other';
}

function fmtNum(n) {
    return Number(n).toLocaleString('id-ID');
}

