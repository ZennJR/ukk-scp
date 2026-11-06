{{-- Filter jadwal per kelas (client-side). Dipakai di jadwal/index dan jadwal/rekap_waka. --}}
<div class="mb-4">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="filterKelas" class="form-control" list="daftarKelasFilter"
               placeholder="Cari kelas, mis. XII RPL 1" autocomplete="off">
        <button type="button" id="btnTampilSemua" class="btn btn-outline-secondary">Tampilkan Semua</button>
    </div>
    <datalist id="daftarKelasFilter">
        @foreach($kelas as $k)
            <option value="{{ $k->nama_kelas ?? trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? '')) }}"></option>
        @endforeach
    </datalist>
    <small id="infoFilterKelas" class="text-muted d-block mt-1"></small>
    <div id="filterKosong" class="alert alert-warning mt-2 mb-0 d-none">Tidak ada jadwal untuk kelas tersebut.</div>
    <div id="filterAwal" class="alert alert-info mt-2 mb-0 d-none">
        <i class="bi bi-search me-1"></i> Ketik atau pilih <strong>nama kelas</strong> pada kolom pencarian di atas untuk menampilkan jadwal.
    </div>
</div>

@push('scripts')
<script>
(function () {
    const input = document.getElementById('filterKelas');
    const btnAll = document.getElementById('btnTampilSemua');
    const info = document.getElementById('infoFilterKelas');
    const kosong = document.getElementById('filterKosong');
    const awal = document.getElementById('filterAwal');
    // true = jadwal disembunyikan sampai pengguna mencari/memilih kelas (atau menekan "Tampilkan Semua")
    const mulaiKosong = @json($mulaiKosong ?? false);
    let tampilSemua = false;
    if (!input) return;
    // Filter hanya mengatur kartu tempat kolom pencarian ini berada (bukan kartu "Jadwal Saya")
    const wadah = input.closest('.card-body') || document;

    function normal(t) { return t.toLowerCase().replace(/\s+/g, ' ').trim(); }

    function terapkan() {
        const q = normal(input.value);
        const aktif = q !== '';
        let total = 0;

        // Belum ada pencarian: sembunyikan semua jadwal dan tampilkan petunjuk
        if (mulaiKosong && !aktif && !tampilSemua) {
            wadah.querySelectorAll('.putaran-block').forEach(function (pb) { pb.style.display = 'none'; });
            kosong.classList.add('d-none');
            info.textContent = '';
            if (awal) awal.classList.remove('d-none');
            return;
        }
        if (awal) awal.classList.add('d-none');

        wadah.querySelectorAll('.putaran-block').forEach(function (pb) {
            let putaranTampil = 0;
            pb.querySelectorAll('.hari-block').forEach(function (hb) {
                let tampil = 0;
                hb.querySelectorAll('tbody tr').forEach(function (tr) {
                    if (tr.classList.contains('row-kosong')) { tr.style.display = aktif ? 'none' : ''; return; }
                    // data-kelas berisi beberapa penulisan nama kelas dipisah "|"
                    const cocok = !aktif || (tr.dataset.kelas || '').split('|').some(function (v) { return v.indexOf(q) !== -1; });
                    tr.style.display = cocok ? '' : 'none';
                    if (cocok) {
                        tampil++;
                        const no = tr.querySelector('.no-urut');
                        if (no) no.textContent = tampil;
                    }
                });
                hb.style.display = (aktif && tampil === 0) ? 'none' : '';
                putaranTampil += tampil;
            });
            pb.style.display = (aktif && putaranTampil === 0) ? 'none' : '';
            total += putaranTampil;
        });

        kosong.classList.toggle('d-none', !(aktif && total === 0));
        info.textContent = aktif ? ('Menampilkan ' + total + ' jadwal untuk "' + input.value.trim() + '"') : '';
    }

    input.addEventListener('input', function () { tampilSemua = false; terapkan(); });
    btnAll.addEventListener('click', function () { input.value = ''; tampilSemua = true; terapkan(); input.focus(); });

    // Tampilan awal
    if (mulaiKosong) terapkan();
})();
</script>
@endpush