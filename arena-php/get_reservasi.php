<?php
// 1. Wajib panggil session_start() di setiap file API yang butuh pengamanan
session_start();

// 2. Set header agar output berupa JSON
header('Content-Type: application/json');
require_once 'config/koneksi.php'; 

// 3. GERBANG KEAMANAN: Cek apakah user punya "Karcis Session"
if (!isset($_SESSION['id_user'])) {
    echo json_encode([
        "status" => "error", 
        "message" => "Akses ditolak! Anda belum login."
    ]);
    exit; // Hentikan eksekusi kode di bawahnya
}

// Ambil data role dari session (untuk info saja)
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

try {
    // 4. Query JOIN yang sudah kamu kuasai di DBeaver (kita buat versi simpelnya)
    $sql = "SELECT r.id_reservasi, r.nama_acara, g.nama_gedung, r.status_akhir 
            FROM reservasi r
            JOIN gedung_fasilitas g ON r.id_gedung = g.id_gedung
            ORDER BY r.status_akhir ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // fetchAll() untuk mengambil banyak baris data sekaligus
    $data_reservasi = $stmt->fetchAll();

    // 5. Kirim data ke Frontend
    echo json_encode([
        "status" => "success",
        "login_sebagai" => "$nama ($role)",
        "jumlah_data" => count($data_reservasi),
        "data" => $data_reservasi
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data database."]);
}
?>