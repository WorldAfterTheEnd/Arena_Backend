<?php
require_once '../config/koneksi.php';

$id_reservasi = $_GET['id'] ?? '';
$data = null;
$pesan_error = "";

if (empty($id_reservasi)) {
    $pesan_error = "ID Reservasi tidak valid.";
} else {
    try {
        $sql = "SELECT r.id_reservasi, u.nama_lengkap AS nama_peminjam, g.nama_gedung, 
                       r.nama_acara, r.tgl_acara, r.jam_mulai, r.jam_selesai, r.status_akhir 
                FROM reservasi r
                JOIN users u ON r.id_user = u.id_user
                JOIN gedung_fasilitas g ON r.id_gedung = g.id_gedung
                WHERE r.id_reservasi = :id_reservasi";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_reservasi' => $id_reservasi]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data || $data['status_akhir'] !== 'Approved') {
            $pesan_error = "DOKUMEN TIDAK VALID ATAU BELUM DISETUJUI SEPENUHNYA.";
        }
    } catch (PDOException $e) {
        $pesan_error = "Terjadi kesalahan sistem.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin Peminjaman Gedung</title>
    <style>
        /* CSS untuk mereplika tampilan kertas A4 */
        body { background-color: #f0f0f0; font-family: 'Times New Roman', Times, serif; }
        .kertas-a4 { 
            background: white; 
            width: 21cm; 
            min-height: 29.7cm; 
            padding: 2cm; 
            margin: 20px auto; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        .kop-surat { text-align: center; border-bottom: 3px solid black; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2, .kop-surat h3, .kop-surat p { margin: 2px; }
        .isi-surat { line-height: 1.5; font-size: 12pt; }
        .tanda-tangan { margin-top: 50px; width: 100%; display: table; }
        .kolom-ttd { display: table-cell; text-align: center; width: 50%; }
        .watermark { 
            color: #28a745; 
            font-size: 1.5em; 
            font-weight: bold; 
            text-align: center; 
            border: 3px solid #28a745; 
            padding: 10px; 
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<?php if ($pesan_error): ?>
    <div style="text-align: center; margin-top: 50px; color: red;">
        <h1>❌ AKSES DITOLAK</h1>
        <p><?= $pesan_error ?></p>
    </div>
<?php else: ?>
    <div class="kertas-a4">
        <!-- KOP SURAT -->
        <div class="kop-surat">
            <h2>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</h2>
            <h3>POLITEKNIK NEGERI MALANG</h3>
            <p>Jalan Soekarno Hatta No. 9 Jatimulyo, Lowokwaru, Malang</p>
        </div>

        <div class="watermark">
            ✔️ SURAT IZIN VALID & RESMI
        </div>

        <!-- ISI SURAT -->
        <div class="isi-surat">
            <p><strong>Perihal:</strong> Persetujuan Peminjaman Fasilitas Kampus</p>
            <br>
            <p>Dengan hormat,</p>
            <p>Berdasarkan pengajuan permohonan peminjaman gedung yang masuk ke sistem ARENA, maka dengan ini kami menyatakan persetujuan penggunaan fasilitas kepada:</p>
            
            <table style="margin-left: 20px; margin-bottom: 20px;">
                <tr><td width="150">Nama Peminjam</td><td>: <strong><?= htmlspecialchars($data['nama_peminjam']) ?></strong></td></tr>
                <tr><td>Kegiatan</td><td>: <?= htmlspecialchars($data['nama_acara']) ?></td></tr>
                <tr><td>Fasilitas/Gedung</td><td>: <strong><?= htmlspecialchars($data['nama_gedung']) ?></strong></td></tr>
                <tr><td>Tanggal Pelaksanaan</td><td>: <?= date('d-m-Y', strtotime($data['tgl_acara'])) ?></td></tr>
                <tr><td>Waktu</td><td>: <?= htmlspecialchars($data['jam_mulai']) ?> s.d <?= htmlspecialchars($data['jam_selesai']) ?> WIB</td></tr>
            </table>

            <p>Surat persetujuan ini diterbitkan secara otomatis oleh sistem ARENA dan telah melewati proses hierarki persetujuan secara elektronik (DosPem, PresBEM, Kajur, hingga Wakil Direktur). Dokumen ini sah digunakan sebagai bukti izin akses bagi petugas keamanan (Satpam) di lokasi.</p>
            <p>Demikian surat persetujuan ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
        </div>

        <!-- BLOK TANDA TANGAN -->
        <div class="tanda-tangan">
            <div class="kolom-ttd">
                <p>Mengetahui,<br>Petugas Keamanan</p>
                <br><br><br>
                <p>_______________________</p>
            </div>
            <div class="kolom-ttd">
                <p>Malang, <?= date('d F Y') ?><br>Sistem ARENA Polinema</p>
                <br><br><br>
                <p><strong>Disetujui secara Elektronik</strong></p>
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>