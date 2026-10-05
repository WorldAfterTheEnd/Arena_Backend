<?php
session_start();
header('Content-Type: application/json');
require_once 'config/koneksi.php';

// 1. GERBANG KEAMANAN & ROLE
if (!isset($_SESSION['id_user'])) {
    echo json_encode(["status" => "error", "message" => "Anda belum login!"]);
    exit;
}

$approver_roles = ['DosPem', 'Kajur', 'Wadir', 'PresBEM'];
if (!in_array($_SESSION['role'], $approver_roles)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak!"]);
    exit;
}

// 2. PROSES UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id_reservasi = $input['id_reservasi'] ?? '';
    $status_baru = $input['status_baru'] ?? ''; 

    if (empty($id_reservasi) || empty($status_baru)) {
        echo json_encode(["status" => "error", "message" => "ID Reservasi dan Status Baru wajib diisi!"]);
        exit;
    }

    // Validasi agar status tidak diisi sembarangan oleh peretas
    $status_valid = ['Approved', 'Rejected', 'In-Progress'];
    if (!in_array($status_baru, $status_valid)) {
        echo json_encode(["status" => "error", "message" => "Format status tidak valid!"]);
        exit;
    }

    try {
        $sql = "UPDATE reservasi SET status_akhir = :status_baru WHERE id_reservasi = :id_reservasi";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status_baru' => $status_baru,
            ':id_reservasi' => $id_reservasi
        ]);

        // Cek apakah ada baris yang benar-benar ter-update
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Proposal berhasil di-$status_baru!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "ID Reservasi tidak ditemukan."]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Gagal update database. Detail: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method harus POST."]);
}
?>