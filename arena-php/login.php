<?php
// 1. Memulai sistem Session PHP
session_start();

// 2. Mengatur header agar response yang dikelurkan berupa JSON (Standar API)
header('Content-Type: application/json');

// 3. Memanggil jembatan koneksi database yang tadi sukses
require_once 'config/koneksi.php'; 

// Hapus/komen tulisan echo "SUKSES..." di file koneksi.php agar tidak ikut tercetak di JSON
// echo "SUKSES: PHP Native berhasil..."; -> // echo "SUKSES: PHP Native berhasil...";

// Cek apakah request yang masuk adalah tipe POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Menangkap data JSON yang dikirim oleh Frontend / Postman
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    // Validasi kosong
    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi!"]);
        exit;
    }

    try {
        // 4. Mencari user di pangkalan data (Menggunakan Parameterised Query untuk cegah SQL Injection)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch();

        // Jika user ditemukan
        if ($user) {
            // Cek kecocokan password
            if ($password === $user['password']) {
                
                // 5. Berhasil! Catat identitasnya di Session Server
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                
                echo json_encode([
                    "status" => "success", 
                    "message" => "Login berhasil!", 
                    "user" => [
                        "nama" => $user['nama_lengkap'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Password salah!"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Email tidak terdaftar!"]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Terjadi kesalahan server."]);
    }

} else {
    // Jika ada yang mencoba buka via URL browser biasa (GET)
    echo json_encode(["status" => "error", "message" => "Method tidak diizinkan. Gunakan POST."]);
}
?>