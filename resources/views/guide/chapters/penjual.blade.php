<h2>Memotret barang yang bagus</h2>
<ul>
    <li>Pakai cahaya siang dari jendela, hindari lampu kuning.</li>
    <li>Latar polos (meja, seprai, dinding) supaya barang menonjol.</li>
    <li>Ambil minimal 3 sudut: depan, belakang/samping, dan detail cacat bila ada.</li>
</ul>

<h2>Ambil Foto atau Pilih dari Galeri</h2>
<p>Di form <strong>Jual Barang</strong> ada dua tombol: <strong>Ambil Foto</strong> membuka kamera HP langsung, <strong>Pilih dari Galeri</strong> untuk foto yang sudah ada. Di laptop, foto bisa diseret ke area foto. Maksimal {{ config('jualin.max_photos') }} foto; foto pertama jadi sampul dan urutannya bisa digeser. Foto dikompres otomatis supaya hemat kuota.</p>

<h2>Mengisi form</h2>
<ul>
    <li>Nama, deskripsi jujur (sebutkan cacat), kategori, kondisi, dan harga.</li>
    <li><strong>Area COD</strong>: Dalam ITK, Sekitar ITK, atau keduanya. Pembeli hanya bisa memilih area yang kamu izinkan.</li>
    <li>Centang <strong>Donasi</strong> bila barang diberikan gratis.</li>
</ul>

<h2>Arti status moderasi</h2>
<ul>
    <li><strong>Menunggu Moderasi</strong>: admin sedang mengecek.</li>
    <li><strong>Disetujui</strong>: tampil di katalog dan lapakmu.</li>
    <li><strong>Perlu Perbaikan</strong>: baca catatan admin, tekan Edit, lalu kirim ulang.</li>
    <li><strong>Terjual</strong>: tidak tampil lagi di katalog.</li>
    <li><strong>Disembunyikan</strong>: diturunkan admin karena laporan pengguna.</li>
</ul>
<p>Mengedit barang yang sudah disetujui mengembalikannya ke Menunggu Moderasi.</p>

<h2>Menerima penawaran dan booking</h2>
<p>Semua penawaran dan booking masuk ada di <strong>Pesan → Masuk</strong>. Sebelum menerima, lihat rating dan skor keandalan pembeli. Satu barang hanya bisa punya satu booking diterima; booking lain tetap antre sebagai cadangan.</p>

<h2>Memasukkan kode serah terima</h2>
<p>Saat bertemu, minta pembeli menyebutkan 4 digit kodenya, lalu masukkan di halaman booking. Kode benar → booking Selesai dan barang otomatis Terjual. Setelah itu, beri rating untuk pembeli.</p>

<h2>Promosi dan Bagikan</h2>
<p>Lihat bab <a href="{{ route('guide.show', 'promosi') }}">Iklan &amp; Promosi</a>.</p>
