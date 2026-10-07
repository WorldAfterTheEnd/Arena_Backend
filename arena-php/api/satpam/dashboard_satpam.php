<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// 1. GERBANG KEAMANAN KHUSUS SATPAM
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Satpam') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Halaman ini khusus Satpam."]);
    exit;
}

try {
    $id_user_satpam = $_SESSION['id_user'];

    // 2. CARI TAHU SATPAM INI MENJAGA GEDUNG MANA
    $sql_cek_gedung = "SELECT id_gedung FROM users WHERE id_user = :id_user";
    $stmt_cek = $pdo->prepare($sql_cek_gedung);
    $stmt_cek->execute([':id_user' => $id_user_satpam]);
    $satpam = $stmt_cek->fetch();

    if (!$satpam || empty($satpam['id_gedung'])) {
        echo json_encode(["status" => "error", "message" => "Anda belum ditugaskan menjaga gedung manapun."]);
        exit;
    }

    $id_gedung_tugas = $satpam['id_gedung'];

    // 3. AMBIL DATA JADWAL & QR CODE KHUSUS GEDUNG TERSEBUT
    // Hanya tampilkan yang statusnya sudah 'Approved' oleh Wadir
    $sql = "SELECT r.id_reservasi, u.nama_lengkap AS nama_peminjam, r.nama_acara, 
                   r.tgl_acara, r.jam_mulai, r.jam_selesai, r.qr_code_surat 
            FROM reservasi r
            JOIN users u ON r.id_user = u.id_user
            WHERE r.id_gedung = :id_gedung 
            AND r.status_akhir = 'Approved'
            ORDER BY r.tgl_acara ASC, r.jam_mulai ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_gedung' => $id_gedung_tugas]);
    $jadwal_gedung = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "login_sebagai" => $_SESSION['nama'] . " (Satpam)",
        "jumlah_jadwal" => count($jadwal_gedung),
        "data" => $jadwal_gedung
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Terjadi kesalahan database: " . $e->getMessage()]);
}
?>