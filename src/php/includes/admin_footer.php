<?php
/**
 * Admin layout footer — closes content area and includes scripts.
 */
?>
        </main>
    </div>
</div>

<script>
// ─── Initialize Lucide Icons ────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});

// ─── Sidebar Toggle (smart: desktop collapse / mobile off-canvas) ─
function toggleCollapse() {
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        toggleSidebar();
        return;
    }
    const layout = document.getElementById('appLayout');
    const isCollapsed = layout.classList.toggle('sidebar-collapsed');
    document.cookie = 'sidebar_collapsed=' + (isCollapsed ? '1' : '0') + ';path=/;max-age=31536000';
}

// ─── Mobile Off-Canvas Sidebar ───────────────────────────
function toggleSidebar(forceState) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen = forceState !== undefined ? forceState : !sidebar.classList.contains('open');

    sidebar.classList.toggle('open', isOpen);
    overlay.classList.toggle('show', isOpen);

    // Body scroll lock
    document.body.classList.toggle('sidebar-open', isOpen);
}

function closeSidebar() {
    toggleSidebar(false);
}

// Close sidebar on escape key + overlay click + focus trap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
        // Close profile dropdown
        document.getElementById('profileMenu')?.classList.remove('open');
        // Close notification panel
        const notifBtn = document.getElementById('notifBtn');
        if (notifBtn) notifBtn.classList.remove('notif-open');
    }
});

// Focus trap inside sidebar when open
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Tab') return;
    const sidebar = document.getElementById('sidebar');
    if (!sidebar || !sidebar.classList.contains('open')) return;

    const focusable = sidebar.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
    }
});

// ─── Profile Dropdown Toggle ─────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) {
        profileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            // Close notifications if open
            document.getElementById('notifBtn')?.classList.remove('notif-open');
            this.classList.toggle('open');
        });
    }
});

// ─── Notifications Panel Toggle ─────────────────────────
function toggleNotifications() {
    const btn = document.getElementById('notifBtn');
    if (btn) {
        // Close profile if open
        document.getElementById('profileMenu')?.classList.remove('open');
        btn.classList.toggle('notif-open');
    }
}

// ─── Theme Switcher ─────────────────────────────────────
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    document.cookie = 'theme=' + newTheme + ';path=/;max-age=31536000';
    updateThemeUI(newTheme);
}

function updateThemeUI(theme) {
    const label = document.getElementById('themeLabel');
    if (label) label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
}

// Restore saved theme
(function() {
    const saved = document.cookie.split('; ').find(r => r.startsWith('theme='));
    if (saved) {
        const theme = saved.split('=')[1];
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            updateThemeUI('dark');
        }
    }
})();

// ─── Mobile: Auto-generate data-label for tables ──────
(function() {
    function addTableLabels() {
        document.querySelectorAll('.data-table').forEach(function(table) {
            const headers = [];
            const thead = table.querySelector('thead');
            if (!thead) return;
            thead.querySelectorAll('th').forEach(function(th) {
                headers.push(th.textContent.trim());
            });
            if (headers.length === 0) return;

            table.querySelectorAll('tbody tr').forEach(function(tr) {
                tr.querySelectorAll('td').forEach(function(td, idx) {
                    if (idx < headers.length && !td.hasAttribute('data-label')) {
                        td.setAttribute('data-label', headers[idx]);
                    }
                });
            });
        });
    }
    addTableLabels();
    // Re-run on dynamic content changes
    const observer = new MutationObserver(addTableLabels);
    observer.observe(document.body, { childList: true, subtree: true });
})();

// ─── Mobile: enhance stepper scroll affordance ─────────
(function() {
    function addScrollHint() {
        const steppers = document.querySelectorAll('.studio-stepper');
        if (window.innerWidth <= 767) {
            steppers.forEach(function(s) {
                if (s.scrollWidth > s.clientWidth && !s.dataset.scrollHint) {
                    s.dataset.scrollHint = 'true';
                    s.style.setProperty('--scroll-hint', '1');
                }
            });
        }
    }
    addScrollHint();
    window.addEventListener('resize', addScrollHint);
})();

// ─── Close dropdowns on outside click ───────────────────
document.addEventListener('click', function(e) {
    // Profile dropdown
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu && !e.target.closest('.topnav-profile')) {
        profileMenu.classList.remove('open');
    }
    // Notification panel
    const notifBtn = document.getElementById('notifBtn');
    if (notifBtn && !e.target.closest('.topnav-btn')) {
        notifBtn.classList.remove('notif-open');
    }
    // General overflow menus
    document.querySelectorAll('.overflow-menu.open').forEach(m => m.classList.remove('open'));
});

// ─── Global Search ───────────────────────────────────────
(function() {
    const searchData = [
        <?php foreach ($navSections as $section): ?>
            <?php foreach ($section['items'] as $item): ?>
                { label: '<?= h($item['label']) ?>', section: '<?= h($section['label']) ?>', url: '<?= BASE_URL ?>/admin/<?= $item['url'] ?>', icon: '<?= $item['icon'] ?>' },
            <?php endforeach; ?>
        <?php endforeach; ?>
        { label: 'Dashboard', section: 'General', url: '<?= BASE_URL ?>/admin/dashboard.php', icon: 'dashboard' },
    ];

    const input = document.getElementById('globalSearch');
    const dropdown = document.getElementById('searchDropdown');
    let activeIdx = -1;
    let results = [];

    function fuzzyMatch(query, text) {
        const q = query.toLowerCase();
        const t = text.toLowerCase();
        let qi = 0;
        for (let ti = 0; ti < t.length && qi < q.length; ti++) {
            if (q[qi] === t[ti]) qi++;
        }
        return qi === q.length;
    }

    function buildResults() {
        const q = input.value.trim();
        if (!q) { dropdown.classList.remove('open'); return; }

        results = searchData.filter(item =>
            fuzzyMatch(q, item.label) || fuzzyMatch(q, item.section)
        );

        if (results.length === 0) {
            dropdown.innerHTML = '<div class="search-dropdown-empty">No results found</div>';
            dropdown.classList.add('open');
            activeIdx = -1;
            return;
        }

        // Group by section
        const grouped = {};
        results.forEach(r => {
            if (!grouped[r.section]) grouped[r.section] = [];
            grouped[r.section].push(r);
        });

        let html = '';
        let idx = 0;
        for (const [section, items] of Object.entries(grouped)) {
            html += '<div class="search-section-label">' + section + '</div>';
            items.forEach(item => {
                const re = new RegExp('(' + input.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                const highlighted = item.label.replace(re, '<em>$1</em>');
                html += '<a href="' + item.url + '" class="search-dropdown-item" data-index="' + idx + '">'
                    + '<span class="sdi-icon">' + getSearchIcon(item.icon) + '</span>'
                    + '<span class="sdi-label">' + highlighted + '</span>'
                    + '<span class="sdi-meta">' + item.section + '</span>'
                    + '</a>';
                idx++;
            });
        }
        dropdown.innerHTML = html;
        dropdown.classList.add('open');
        activeIdx = -1;

        // Click handler on items
        dropdown.querySelectorAll('.search-dropdown-item').forEach(el => {
            el.addEventListener('click', function(e) {
                // Let the native link handle navigation
            });
        });
    }

    function getSearchIcon(name) {
        // Map old icon names to Lucide names (matches the PHP getLucideMap())
        const lucideMap = {
            dashboard: 'layout-dashboard',
            college: 'building2',
            course: 'book-open',
            batch: 'layers',
            student: 'graduation-cap',
            test: 'file-text',
            clock: 'clock',
            external: 'external-link',
            plus: 'circle-plus',
            document: 'file-text',
            database: 'database',
            status: 'circle-dot',
            pulse: 'activity',
            grading: 'clipboard-check',
            chart: 'chart-bar-big',
            reports: 'chart-bar-big',
            activity: 'activity',
            warning: 'triangle-alert',
            settings: 'settings',
            help: 'circle-help',
        };
        const lucideName = lucideMap[name] || 'layout-dashboard';
        return '<i data-lucide="' + lucideName + '" width="16" height="16"></i>';
    }

    // Input handler
    input.addEventListener('input', buildResults);

    // Focus handler
    input.addEventListener('focus', function() {
        if (input.value.trim()) buildResults();
    });

    // Keyboard navigation
    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.search-dropdown-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
            if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
            if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIdx >= 0 && items[activeIdx]) {
                window.location.href = items[activeIdx].href;
            } else if (results.length === 1) {
                window.location.href = items[0].href;
            }
        } else if (e.key === 'Escape') {
            input.blur();
            dropdown.classList.remove('open');
        }
    });

    // Close on blur (delayed to allow click)
    input.addEventListener('blur', function() {
        setTimeout(function() { dropdown.classList.remove('open'); }, 200);
    });

    // Ctrl+K shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });
})();
</script>
</body>
</html>
