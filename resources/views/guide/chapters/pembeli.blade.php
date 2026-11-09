<h2>Mencari barang</h2>
<ul>
    <li><strong>Per jenis</strong>: buka Katalog, pilih kategori, lalu saring kondisi, harga, dan area COD. Urutkan terbaru, termurah, atau termahal.</li>
    <li><strong>Per lapak</strong>: tab <strong>Lapak</strong> di Katalog menampilkan daftar penjual. Buka lapak untuk melihat semua barangnya, rating, dan ulasan.</li>
    <li>Barang berlabel <strong>Unggulan</strong> sedang dipromosikan penjualnya. Label <strong>Donasi</strong> berarti gratis.</li>
</ul>

<h2>Membaca kartu barang</h2>
<p>Kartu menampilkan foto, harga, area COD (Dalam ITK / Sekitar ITK), dan kondisi. Di halaman detail ada kartu penjual dengan rating dan <strong>skor keandalan</strong>: persentase booking yang benar-benar selesai.</p>
<p>Tekan ikon hati untuk menyimpan barang ke <strong>Favorit</strong>.</p>

<h2>Hubungi penjual</h2>
<p>Tekan <strong>Hubungi Penjual</strong>. Ada tiga pilihan:</p>
<ol>
    <li><strong>Diskusi via WhatsApp</strong>: untuk tanya kondisi atau nego. Maksimal {{ config('jualin.contact_daily_limit') }} barang baru per hari.</li>
    <li><strong>Ajukan Penawaran</strong>: tawar harga. Jika diterima, kamu punya {{ config('jualin.offer_booking_window_hours') }} jam untuk booking.</li>
    <li><strong>Ajukan Booking COD</strong>: kunci waktu dan titik bertemu.</li>
</ol>
<div class="warn">Diskusi boleh lewat WA, tapi kesepakatan hanya sah kalau dikunci di web lewat booking.</div>

<h2>Booking dan kode serah terima</h2>
<ol>
    <li>Pilih area, titik COD dari daftar admin, dan waktu (jam {{ config('jualin.cod_open_hour') }}.00–{{ config('jualin.cod_close_hour') }}.00 WITA, paling cepat {{ config('jualin.cod_min_lead_hours') }} jam dari sekarang).</li>
    <li>Penjual punya {{ config('jualin.booking_respond_hours') }} jam untuk menerima. Kalau tidak, booking batal otomatis.</li>
    <li>Mulai {{ config('jualin.confirm_opens_hours') }} jam sebelum jadwal, tekan <strong>Saya tetap datang</strong>.</li>
    <li>Di halaman booking kamu punya <strong>kode serah terima 4 digit</strong>. Sebutkan ke penjual <strong>setelah</strong> barang kamu cek.</li>
</ol>

<h2>Memberi rating</h2>
<p>Setelah booking Selesai, beri bintang 1–5, pilih tag (Ramah, Barang Sesuai, Tepat Waktu, Responsif, Harga Pas), dan tulis ulasan singkat. Penjual juga menilaimu sebagai pembeli. Rating bisa diberikan sampai {{ config('jualin.review_window_days') }} hari setelah transaksi.</p>
