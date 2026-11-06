/* =======================================================
   UKK SCP - UI Interaction Layer
   ======================================================= */
(function () {
    'use strict';

    /* ---------- Ripple effect on every button click ---------- */
    function attachRipple() {
        document.querySelectorAll('.btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var rect = btn.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height) * 1.4;
                var ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.width = ripple.style.height = size + 'px';
                var x = (e.clientX || (rect.left + rect.width / 2)) - rect.left - size / 2;
                var y = (e.clientY || (rect.top + rect.height / 2)) - rect.top - size / 2;
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                btn.appendChild(ripple);
                window.setTimeout(function () { ripple.remove(); }, 550);
            });
        });
    }

    /* ---------- Scroll reveal (fade-in) ---------- */
    function attachScrollReveal() {
        var targets = document.querySelectorAll('.reveal, .reveal-stagger');
        if (!targets.length) return;
        if (!('IntersectionObserver' in window)) {
            targets.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
        targets.forEach(function (el) { observer.observe(el); });
    }

    /* ---------- Top scroll progress bar ---------- */
    function attachScrollProgress() {
        var bar = document.createElement('div');
        bar.className = 'scroll-progress';
        document.body.appendChild(bar);
        function update() {
            var h = document.documentElement;
            var scrolled = h.scrollTop;
            var height = h.scrollHeight - h.clientHeight;
            var pct = height > 0 ? (scrolled / height) * 100 : 0;
            bar.style.width = pct + '%';
        }
        update();
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
    }

    /* ---------- Auto-close mobile sidebar after picking a nav link ---------- */
    function attachSidebarAutoClose() {
        var toggle = document.getElementById('sidebarToggle');
        if (!toggle) return;
        document.querySelectorAll('.sidebar .side-link').forEach(function (link) {
            link.addEventListener('click', function () { toggle.checked = false; });
        });
    }

    /* ---------- Desktop sidebar collapse state, persisted ---------- */
    function attachSidebarCollapsePersistence() {
        var collapse = document.getElementById('sidebarCollapse');
        if (!collapse) return;
        try {
            if (localStorage.getItem('ukkscp_sidebar_collapsed') === '1') {
                collapse.checked = true;
            }
        } catch (e) {}
        collapse.addEventListener('change', function () {
            try {
                localStorage.setItem('ukkscp_sidebar_collapsed', collapse.checked ? '1' : '0');
            } catch (e) {}
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        attachRipple();
        attachScrollReveal();
        attachScrollProgress();
        attachSidebarAutoClose();
        attachSidebarCollapsePersistence();
    });
})();
