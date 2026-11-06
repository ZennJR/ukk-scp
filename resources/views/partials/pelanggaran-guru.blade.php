{{-- Banner pelanggaran jurnal milik guru (7 hari terakhir). Butuh $pelanggaranTerbaru. --}}
@if(isset($pelanggaranTerbaru) && $pelanggaranTerbaru->isNotEmpty())
    <div class="alert alert-danger" role="alert">
        <strong><i class="bi bi-exclamation-octagon-fill me-1"></i>Peringatan: Anda tercatat melanggar aturan pengisian jurnal</strong>
        <div class="small mt-1">Jurnal wajib diisi paling lambat {{ \App\Support\PelanggaranGuru::batasJam() }} jam setelah jam jadwal selesai. Pelanggaran dilaporkan ke Waka Kurikulum.</div>
        <ul class="mb-0 mt-2">
            @foreach($pelanggaranTerbaru as $p)
                <li>
                    {{ \Illuminate\Support\Carbon::parse($p->tanggal)->format('d-m-Y') }} &ndash;
                    {{ $p->kelas }} / {{ $p->mapel }}
                    ({{ substr($p->jam_mulai, 0, 5) }}&ndash;{{ substr($p->jam_selesai, 0, 5) }}):
                    @if($p->jenis === 'Terlambat')
                        <strong>diisi terlambat</strong> (pukul {{ \Illuminate\Support\Carbon::parse($p->jurnal_diisi_at)->format('H:i') }},
                        batas {{ \Illuminate\Support\Carbon::parse($p->batas_waktu)->format('H:i') }})
                    @else
                        <strong>tidak diisi</strong> (batas {{ \Illuminate\Support\Carbon::parse($p->batas_waktu)->format('d-m H:i') }})
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
