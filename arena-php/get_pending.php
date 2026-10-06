<?php
session_start();
header('Content-Type: application/json');
require_once 'config/koneksi.php';

// 1. GERBANG KEAMANAN & ROLE
if (!isset($_SESSION['id_user'])) {
    echo json_encode(["status" => "error", "message" => "Anda belum login!"]);
    exit;
}

$role = $_SESSION['role'];
$approver_roles = ['DosPem', 'PresBEM', 'Kajur', 'Wadir'];

if (!in_array($role, $approver_roles)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Anda bukan Approver."]);
    exit;
}

try {
    // 2. QUERY SEKUENSIAL: Hanya tampilkan data yang posisinya ada di role yang sedang login
    // Dan pastikan status akhirnya belum Approved/Rejected (masih berjalan)
    $sql = "SELECT r.id_reservasi, u.nama_lengkap AS nama_pemohon, g.nama_gedung, 
                   r.nama_acara, r.tgl_acara, r.jam_mulai, r.jam_selesai, r.posisi_approval, r.status_akhir 
            FROM reservasi r
            JOIN users u ON r.id_user = u.id_user
            JOIN gedung_fasilitas g ON r.id_gedung = g.id_gedung
            WHERE r.posisi_approval = :role_sekarang 
            AND r.status_akhir IN ('Pending', 'In-Progress')
            ORDER BY r.tgl_acara ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':role_sekarang' => $role]); // Filter data sesuai jabatan/role
    
    $data_pending = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "login_sebagai" => $_SESSION['nama'] . " ($role)",
        "jumlah_antrean" => count($data_pending),
        "data" => $data_pending
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data. Detail: " . $e->getMessage()]);
}
?>