<p>Aturan ini dibuat supaya penjual tidak dirugikan pembeli yang bilang "jadi beli" lalu menghilang, dan sebaliknya.</p>

<h2>Konfirmasi kehadiran</h2>
<p>Mulai {{ config('jualin.confirm_opens_hours') }} jam sebelum jadwal COD, pembeli dan penjual menekan <strong>Saya tetap datang</strong>. Kalau sampai {{ config('jualin.confirm_deadline_hours') }} jam sebelum jadwal salah satu belum konfirmasi, booking batal otomatis dan yang tidak konfirmasi dicatat pelanggaran.</p>

<h2>Yang dihitung pelanggaran</h2>
<ul>
    <li>Tidak konfirmasi kehadiran sampai batas waktu.</li>
    <li>Tidak datang ke COD (dilaporkan pihak lain).</li>
    <li>Membatalkan booking yang sudah diterima kurang dari {{ config('jualin.late_cancel_hours') }} jam sebelum jadwal.</li>
</ul>
<p>Aturan ini berlaku sama untuk pembeli dan penjual.</p>

<h2>Tahapan sanksi</h2>
<ol>
    <li><strong>1 pelanggaran</strong>: peringatan.</li>
    @foreach (config('jualin.sanctions') as $count => $days)
        <li><strong>{{ $count }} pelanggaran</strong> dalam {{ config('jualin.violation_window_days') }} hari: tidak bisa booking {{ $days }} hari{{ $loop->last ? ' dan akun ditinjau admin' : '' }}.</li>
    @endforeach
</ol>
<p>Pelanggaran yang lebih lama dari {{ config('jualin.violation_window_days') }} hari tidak dihitung lagi.</p>

<h2>Membantah laporan "tidak datang"</h2>
<p>Setelah jadwal lewat, salah satu pihak bisa menekan <strong>Pembeli/Penjual tidak datang</strong>. Kalau laporan itu tidak benar, buka halaman booking dan tulis bantahan dalam {{ config('jualin.dispute_hours') }} jam. Admin meninjau sengketa: bila bantahan diterima, pelanggaranmu dicabut.</p>

<h2>Barang terlarang</h2>
<p>Barang curian, obat-obatan, senjata, rokok/vape, minuman keras, barang bajakan, dan joki tugas/ujian tidak boleh dijual. Barang seperti ini disembunyikan admin dan akunnya bisa dinonaktifkan.</p>
