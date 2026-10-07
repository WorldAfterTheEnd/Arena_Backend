<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// 1. GERBANG KEAMANAN & ROLE
if (!isset($_SESSION['id_user'])) {
    echo json_encode(["status" => "error", "message" => "Anda belum login!"]);
    exit;
}

// Pastikan hanya Mahasiswa yang bisa mengakses riwayat pribadinya
if ($_SESSION['role'] !== 'Mahasiswa') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Halaman ini khusus Mahasiswa."]);
    exit;
}

try {
    $id_user = $_SESSION['id_user'];

    // 2. AMBIL DATA KHUSUS MILIK MAHASISWA YANG SEDANG LOGIN
    $sql = "SELECT r.id_reservasi, g.nama_gedung, r.nama_acara, r.tgl_acara, 
                   r.jam_mulai, r.jam_selesai, r.posisi_approval, r.status_akhir 
            FROM reservasi r
            JOIN gedung_fasilitas g ON r.id_gedung = g.id_gedung
            WHERE r.id_user = :id_user
            ORDER BY r.tgl_acara DESC"; // Urutkan dari acara paling baru
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_user' => $id_user]);
    $riwayat = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "login_sebagai" => $_SESSION['nama'],
        "jumlah_riwayat" => count($riwayat),
        "data" => $riwayat
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data riwayat. Detail: " . $e->getMessage()]);
}
?>