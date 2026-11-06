(function () {
    var CEK_URL = '/notifikasi/cek';
    var INTERVAL_MS = 25000;

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : str;
        return div.innerHTML;
    }

    function render(data) {
        var badge = document.getElementById('notifBadge');
        var listWrap = document.getElementById('notifListWrap');
        var emptyState = document.getElementById('notifEmptyState');
        if (!badge || !listWrap) return;

        if (data.unread > 0) {
            badge.textContent = data.unread > 99 ? '99+' : data.unread;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }

        if (!data.items || data.items.length === 0) {
            listWrap.innerHTML = '';
            if (emptyState) emptyState.classList.remove('d-none');
            return;
        }
        if (emptyState) emptyState.classList.add('d-none');

        var html = data.items.map(function (n) {
            var unreadClass = n.is_read ? '' : ' notif-unread';
            return '' +
                '<a class="dropdown-item notif-item' + unreadClass + '" href="' + n.url + '">' +
                    '<div class="notif-title">' + escapeHtml(n.title) + '</div>' +
                    '<div class="notif-message text-muted small">' + escapeHtml(n.message) + '</div>' +
                    '<div class="notif-time text-muted">' + escapeHtml(n.waktu) + '</div>' +
                '</a>';
        }).join('');

        listWrap.innerHTML = html;
    }

    function cekNotifikasi() {
        fetch(CEK_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) { if (data) render(data); })
            .catch(function () { /* diamkan saja kalau gagal, coba lagi di polling berikutnya */ });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cekNotifikasi();
        setInterval(cekNotifikasi, INTERVAL_MS);
    });
})();
