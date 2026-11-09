<h3>Lupa password?</h3>
<p>Di halaman <a href="{{ route('login') }}">Masuk</a>, tekan "Lupa password" dan ikuti tautan yang dikirim ke email kampusmu.</p>

<h3>Bagaimana mengganti nomor WA?</h3>
<p>Buka <strong>Akun → Edit profil</strong>, ubah nomor WhatsApp, lalu simpan.</p>

<h3>Barangku dikembalikan dengan status Perlu Perbaikan.</h3>
<p>Baca catatan admin di Barang Saya, tekan <strong>Edit</strong>, perbaiki (biasanya foto kurang jelas atau deskripsi tidak lengkap), lalu kirim ulang.</p>

<h3>Pembeli tidak datang, lalu bagaimana?</h3>
<p>Setelah jadwal lewat, buka halaman booking dan tekan <strong>Pembeli tidak datang</strong>. Barang kembali tersedia dan booking cadangan (jika ada) bisa kamu terima.</p>

<h3>Penjual lupa memasukkan kode serah terima.</h3>
<p>Setelah jadwal lewat, pembeli bisa menekan <strong>Barang sudah saya terima · Tandai selesai</strong> di halaman booking.</p>

<h3>Kenapa aku tidak bisa booking?</h3>
<p>Kemungkinan kamu sudah punya {{ config('jualin.max_active_bookings') }} booking aktif, atau akunmu sedang terkena sanksi pelanggaran. Lihat bab <a href="{{ route('guide.show', 'aturan') }}">Aturan &amp; Sanksi</a>.</p>

<h3>Bagaimana menghapus akun?</h3>
<p>Buka <strong>Edit profil</strong>, gulir ke bawah, dan pilih hapus akun. Riwayat transaksi yang melibatkan orang lain tetap disimpan untuk keamanan.</p>

<h3>Ada saran atau masalah lain?</h3>
<p>Tekan <strong>Beri Masukan</strong> di bagian bawah halaman atau menu Akun. Masukanmu dipakai untuk memperbaiki web ini.</p>
