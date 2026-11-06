/* UKK SCP - Mobile helper: ubah tabel menjadi kartu di layar kecil.
   Menyalin teks <th> ke atribut data-label pada setiap <td>. */
(function () {
    'use strict';
    // Geser bottom nav agar menu aktif terlihat di tengah
    document.addEventListener('DOMContentLoaded', function () {
        var nav = document.querySelector('.bottom-nav.bn-scroll');
        var act = nav && nav.querySelector('.bn-item.active');
        if (act) nav.scrollLeft = act.offsetLeft - (nav.clientWidth - act.offsetWidth) / 2;
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.content table').forEach(function (t) {
            if (t.classList.contains('no-stack') || t.closest('.sched-list')) return;
            var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) {
                return th.textContent.replace(/\s+/g, ' ').trim();
            });
            if (!heads.length) return;
            t.querySelectorAll('tbody tr').forEach(function (tr) {
                Array.prototype.forEach.call(tr.children, function (td, i) {
                    if (td.colSpan > 1) { td.classList.add('td-span'); return; }
                    if (heads[i]) td.setAttribute('data-label', heads[i]);
                });
            });
            t.classList.add('stack-table');
        });
    });
})();
