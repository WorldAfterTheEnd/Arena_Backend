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
    echo json_encode(["status" => "error", "message" => "Akses Ditolak!"]);
    exit;
}

// 2. PROSES UPDATE STATUS BERANTAI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id_reservasi = $input['id_reservasi'] ?? '';
    $keputusan = $input['keputusan'] ?? ''; // Kita ganti key-nya jadi 'keputusan' (Approve / Reject)

    if (empty($id_reservasi) || empty($keputusan)) {
        echo json_encode(["status" => "error", "message" => "ID Reservasi dan Keputusan wajib diisi!"]);
        exit;
    }

    if (!in_array($keputusan, ['Approve', 'Reject'])) {
        echo json_encode(["status" => "error", "message" => "Keputusan tidak valid! Gunakan 'Approve' atau 'Reject'."]);
        exit;
    }

    try {
        $status_akhir = 'In-Progress';
        $posisi_baru = $role; // Nilai bawaan

        if ($keputusan === 'Reject') {
            $status_akhir = 'Rejected';
            $posisi_baru = 'Selesai'; 
        } else if ($keputusan === 'Approve') {
            // Logika Estafet berdasarkan Role saat ini
            if ($role === 'DosPem') {
                $posisi_baru = 'PresBEM';
            } else if ($role === 'PresBEM') {
                $posisi_baru = 'Kajur';
            } else if ($role === 'Kajur') {
                $posisi_baru = 'Wadir';
            } else if ($role === 'Wadir') {
                $posisi_baru = 'Selesai';
                $status_akhir = 'Approved'; // ACC Final!
            }
        }

        // DOUBLE PROTECTION: Pastikan proposal memang sedang berada di giliran orang ini
        $sql = "UPDATE reservasi 
                SET posisi_approval = :posisi_baru, status_akhir = :status_akhir 
                WHERE id_reservasi = :id_reservasi AND posisi_approval = :role_sekarang";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':posisi_baru' => $posisi_baru,
            ':status_akhir' => $status_akhir,
            ':id_reservasi' => $id_reservasi,
            ':role_sekarang' => $role
        ]);

        if ($stmt->rowCount() > 0) {
            $pesan = ($keputusan === 'Approve') ? "Berhasil di-Approve. Diteruskan ke: $posisi_baru" : "Proposal ditolak (Rejected).";
            
            // Jika Wadir yang Approve
            if ($role === 'Wadir' && $keputusan === 'Approve') {
                $pesan = "Berhasil! Proposal telah disetujui sepenuhnya (Final Approved).";
            }
            echo json_encode(["status" => "success", "message" => $pesan]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal! Proposal tidak ditemukan atau bukan giliran Anda."]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Gagal update database. Detail: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method harus POST."]);
}
?>