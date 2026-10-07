<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'AdminTU') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak!"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_gedung = $input['id_gedung'] ?? '';
    $status_baru = $input['status_operasional'] ?? ''; // 'Aktif' atau 'Nonaktif'

    if (empty($id_gedung) || empty($status_baru)) {
        echo json_encode(["status" => "error", "message" => "ID Gedung dan Status Baru wajib diisi!"]);
        exit;
    }

    if (!in_array($status_baru, ['Aktif', 'Nonaktif'])) {
        echo json_encode(["status" => "error", "message" => "Format salah! Gunakan 'Aktif' atau 'Nonaktif'."]);
        exit;
    }

    try {
        $sql = "UPDATE gedung_fasilitas SET status_operasional = :status_baru WHERE id_gedung = :id_gedung";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status_baru' => $status_baru, 
            ':id_gedung' => $id_gedung
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Sistem diperbarui: Status gedung sekarang $status_baru!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal diubah. ID tidak ditemukan atau status sudah $status_baru."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Kesalahan database: " . $e->getMessage()]);
    }
}
?>