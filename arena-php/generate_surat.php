<?php
session_start();
header('Content-Type: application/json');
require_once 'config/koneksi.php';

// 1. GERBANG KEAMANAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Mahasiswa') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Hanya Mahasiswa yang dapat mencetak surat izin."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_reservasi = $input['id_reservasi'] ?? '';

    if (empty($id_reservasi)) {
        echo json_encode(["status" => "error", "message" => "ID Reservasi wajib diisi!"]);
        exit;
    }

    try {
        // 2. CEK APAKAH STATUSNYA SUDAH APPROVED FINAL
        $sql_cek = "SELECT id_reservasi, status_akhir FROM reservasi 
                    WHERE id_reservasi = :id_reservasi AND id_user = :id_user";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->execute([
            ':id_reservasi' => $id_reservasi,
            ':id_user' => $_SESSION['id_user']
        ]);
        
        $reservasi = $stmt_cek->fetch();

        if (!$reservasi) {
            echo json_encode(["status" => "error", "message" => "Data tidak ditemukan atau ini bukan proposal Anda."]);
            exit;
        }

        if ($reservasi['status_akhir'] !== 'Approved') {
            echo json_encode(["status" => "error", "message" => "Gagal! Surat hanya bisa dicetak jika status sudah 'Approved'."]);
            exit;
        }

        // 3. GENERATE QR CODE SECARA AJAIB
        // Satpam akan men-scan ini dan diarahkan ke sistem ARENA untuk verifikasi
        $url_verifikasi = "http://localhost:8080/verifikasi.php?id=" . $id_reservasi;
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url_verifikasi);

        // 4. SIMPAN LINK QR KE DATABASE
        $sql_update = "UPDATE reservasi SET qr_code_surat = :qr_url WHERE id_reservasi = :id_reservasi";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':qr_url' => $qr_url,
            ':id_reservasi' => $id_reservasi
        ]);

        echo json_encode([
            "status" => "success", 
            "message" => "Surat Digital berhasil diterbitkan!",
            "link_qr_code" => $qr_url
        ]);

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Kesalahan sistem: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method harus POST."]);
}
?>