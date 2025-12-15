<?php
session_start();
require '../config.php';

/* ===============================
   CEK LOGIN
================================ */
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

/* ===============================
   FUNGSI TRIGGER TASK LOG
================================ */
function triggerTask($conn, $request_id, $user_id, $status_admin, $status_teknisi, $keterangan)
{
    $stmt = $conn->prepare("
        INSERT INTO task_log
        (request_id, user_id, status_admin, status_teknisi, keterangan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $request_id,
        $user_id,
        $status_admin,
        $status_teknisi,
        $keterangan
    ]);
}

/* ===============================
   HANDLE INSERT REQUEST
================================ */
if (isset($_POST['kirim'])) {

    $foto = null;

    if (!empty($_FILES['foto']['name'])) {

        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Tipe file harus jpg/jpeg/png";
        }

        if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran maksimal 2MB";
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);

    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        $error = "File bukan gambar valid";
    }

        $foto = uniqid() . '.' . $ext;
        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            "../uploads/" . $foto
        );
    }

    $stmt = $conn->prepare("
        INSERT INTO request (user_id, judul, deskripsi, lokasi, foto, tanggal_lapor)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $_POST['judul'],
        $_POST['deskripsi'],
        $_POST['lokasi'],
        $foto,
        $_POST['tanggal_lapor']
    ]);

    $request_id = $conn->lastInsertId();

    // 🔥 TRIGGER LOG (SISWA MEMBUAT REQUEST)
    triggerTask(
        $conn,
        $request_id,
        $user_id,
        'open',
        'open',
        'Request dibuat'
    );

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Request</title>
</head>
<body>

    <h2>Tambah Request</h2>

    <form method="post" enctype="multipart/form-data">
        Judul <br>
        <input type="text" name="judul" required><br><br>

        Deskripsi <br>
        <textarea name="deskripsi" required></textarea><br><br>

        Lokasi <br>
        <input type="text" name="lokasi" required><br><br>

        Foto <br>
        <input type="file" name="foto" accept=".jpg, .jpeg, .png"><br><br>

        Tanggal Lapor <br>
        <input type="date" name="tanggal_lapor"><br><br>

        <button type="submit" name="kirim">Kirim</button>
        <a href="dashboard.php">Kembali</a>
    </form>

</body>
</html>
