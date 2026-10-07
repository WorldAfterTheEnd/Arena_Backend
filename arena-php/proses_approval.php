<?php
session_start();
header('Content-Type: application/json');
require_once 'config/koneksi.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_reservasi = $input['id_reservasi'] ?? '';
    $keputusan = $input['keputusan'] ?? ''; 

    if (empty($id_reservasi) || empty($keputusan)) {
        echo json_encode(["status" => "error", "message" => "ID Reservasi dan Keputusan wajib diisi!"]);
        exit;
    }

    try {
        $status_akhir = 'In-Progress';
        $posisi_baru = $role; 
        $qr_url = null; // Default kosong

        if ($keputusan === 'Reject') {
            $status_akhir = 'Rejected';
            $posisi_baru = 'Selesai'; 
        } else if ($keputusan === 'Approve') {
            if ($role === 'DosPem') $posisi_baru = 'PresBEM';
            else if ($role === 'PresBEM') $posisi_baru = 'Kajur';
            else if ($role === 'Kajur') $posisi_baru = 'Wadir';
            else if ($role === 'Wadir') {
                $posisi_baru = 'Selesai';
                $status_akhir = 'Approved'; 
                
                // OTOMATISASI QR CODE SAAT WADIR APPROVE
                $url_verifikasi = "http://localhost:8080/verifikasi.php?id=" . $id_reservasi;
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url_verifikasi);
            }
        }

        // Siapkan query update (dinamis tergantung apakah QR dibuat atau tidak)
        if ($qr_url) {
            $sql = "UPDATE reservasi SET posisi_approval = :posisi_baru, status_akhir = :status_akhir, qr_code_surat = :qr_url 
                    WHERE id_reservasi = :id_reservasi AND posisi_approval = :role_sekarang";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':posisi_baru' => $posisi_baru, ':status_akhir' => $status_akhir, ':qr_url' => $qr_url, ':id_reservasi' => $id_reservasi, ':role_sekarang' => $role]);
        } else {
            $sql = "UPDATE reservasi SET posisi_approval = :posisi_baru, status_akhir = :status_akhir 
                    WHERE id_reservasi = :id_reservasi AND posisi_approval = :role_sekarang";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':posisi_baru' => $posisi_baru, ':status_akhir' => $status_akhir, ':id_reservasi' => $id_reservasi, ':role_sekarang' => $role]);
        }

        if ($stmt->rowCount() > 0) {
            $pesan = ($keputusan === 'Approve') ? "Berhasil di-Approve. Diteruskan ke: $posisi_baru" : "Proposal ditolak.";
            if ($role === 'Wadir' && $keputusan === 'Approve') {
                $pesan = "Final Approved! QR Code otomatis diterbitkan ke Admin dan Satpam terkait.";
            }
            echo json_encode(["status" => "success", "message" => $pesan]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal! Proposal tidak ditemukan atau bukan giliran Anda."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Gagal update database: " . $e->getMessage()]);
    }
}
?>