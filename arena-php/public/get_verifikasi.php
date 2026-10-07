<?php
// Tidak perlu session_start() karena ini API Publik untuk cek keaslian
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Menangkap parameter 'id' dari URL (contoh: get_verifikasi.php?id=1234-5678)
$id_reservasi = $_GET['id'] ?? '';

if (empty($id_reservasi)) {
    echo json_encode(["status" => "error", "message" => "ID Reservasi tidak ditemukan!"]);
    exit;
}

try {
    // Cari data reservasi berdasarkan ID
    $sql = "SELECT r.id_reservasi, u.nama_lengkap AS nama_peminjam, g.nama_gedung, 
                   r.nama_acara, r.tgl_acara, r.jam_mulai, r.jam_selesai, r.status_akhir 
            FROM reservasi r
            JOIN users u ON r.id_user = u.id_user
            JOIN gedung_fasilitas g ON r.id_gedung = g.id_gedung
            WHERE r.id_reservasi = :id_reservasi";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_reservasi' => $id_reservasi]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika ID ngawur atau hasil ketikan peretas
    if (!$data) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "DOKUMEN PALSU! Data tidak ditemukan di sistem."]);
        exit;
    }

    // Jika ID benar, cek status akhirnya
    if ($data['status_akhir'] === 'Approved') {
        echo json_encode([
            "status" => "success",
            "message" => "SURAT IZIN VALID (RESMI)",
            "data" => $data
        ]);
    } else {
        // Jika ada yang iseng nge-scan dokumen yang sudah di-Reject / masih Pending
        echo json_encode([
            "status" => "warning",
            "message" => "SURAT IZIN TIDAK BERLAKU (Status: " . $data['status_akhir'] . ")",
            "data" => $data
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Terjadi kesalahan sistem: " . $e->getMessage()]);
}
?>