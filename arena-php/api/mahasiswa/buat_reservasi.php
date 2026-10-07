<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php'; 

// 1. GERBANG OTORISASI & ROLE
if (!isset($_SESSION['id_user'])) {
    echo json_encode(["status" => "error", "message" => "Anda belum login!"]);
    exit;
}

if ($_SESSION['role'] !== 'Mahasiswa') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Hanya Mahasiswa yang dapat membuat pengajuan."]);
    exit;
}

// 2. PROSES PENYIMPANAN DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Menangkap data dari Postman/Frontend sesuai dengan kolom di tabel
    $id_gedung = $input['id_gedung'] ?? '';
    $nama_acara = $input['nama_acara'] ?? '';
    $tgl_acara = $input['tgl_acara'] ?? '';
    $jam_mulai = $input['jam_mulai'] ?? '';
    $jam_selesai = $input['jam_selesai'] ?? '';

    if (empty($id_gedung) || empty($nama_acara) || empty($tgl_acara) || empty($jam_mulai) || empty($jam_selesai)) {
        echo json_encode(["status" => "error", "message" => "Data pengajuan tidak lengkap!"]);
        exit;
    }

    try {
        // Kita ubah nama variabel PHP-nya menjadi $id_user agar tidak bingung
        $id_user = $_SESSION['id_user'];

        // Query disesuaikan dengan kolom tabel aslimu: id_user, tgl_acara, jam_mulai, jam_selesai
        $sql = "INSERT INTO reservasi (id_gedung, id_user, nama_acara, tgl_acara, jam_mulai, jam_selesai, status_akhir) 
                VALUES (:id_gedung, :id_user, :nama_acara, :tgl_acara, :jam_mulai, :jam_selesai, 'Pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_gedung' => $id_gedung,
            ':id_user' => $id_user, // Binding data session ke kolom id_user
            ':nama_acara' => $nama_acara,
            ':tgl_acara' => $tgl_acara,
            ':jam_mulai' => $jam_mulai,
            ':jam_selesai' => $jam_selesai
        ]);

        echo json_encode([
            "status" => "success", 
            "message" => "Pengajuan berhasil! Menunggu persetujuan DosPem."
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error", 
            "message" => "Gagal menyimpan ke database. Detail: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method harus POST."]);
}
?>