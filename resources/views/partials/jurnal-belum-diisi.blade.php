{{-- Banner peringatan jurnal belum diisi. Butuh $jurnalBelumDiisi; opsional $tombol (true = tampilkan tombol ke halaman jurnal). --}}
@if(isset($jurnalBelumDiisi) && $jurnalBelumDiisi->isNotEmpty())
    <div class="alert alert-warning" role="alert">
        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Anda belum mengisi jurnal mengajar hari ini</strong> untuk jadwal yang sudah selesai:
        <ul class="mb-0 mt-1">
            @foreach($jurnalBelumDiisi as $b)
                <li>
                    {{ $b->kelas->nama_kelas ?? $b->id_kelas }} - {{ $b->mataPelajaran->nama_mapel ?? $b->id_mapel }}
                    ({{ substr($b->jam_mulai, 0, 5) }} - {{ substr($b->jam_selesai, 0, 5) }}@if($b->jam_ke), {{ $b->jam_ke }}@endif)
                    &ndash; <span class="text-danger fw-semibold">batas isi: {{ \App\Support\PelanggaranGuru::batasWaktu($b, date('Y-m-d'))->format('H:i') }}</span>
                </li>
            @endforeach
        </ul>
        <small class="d-block mt-2"><i class="bi bi-info-circle me-1"></i>Jurnal yang tidak diisi lebih dari {{ \App\Support\PelanggaranGuru::batasJam() }} jam setelah jam selesai jadwal akan tercatat sebagai <strong>pelanggaran</strong>.</small>
        @if(!empty($tombol))
            <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-warning mt-2"><i class="bi bi-journal-richtext me-1"></i>Isi Jurnal Sekarang</a>
        @else
            <small class="d-block mt-1">Isi lewat form di sebelah kanan: pilih jadwalnya, tulis topik, lalu unggah foto KBM.</small>
        @endif
    </div>
@endif