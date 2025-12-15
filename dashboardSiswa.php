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

$stmt = $conn->prepare("
    SELECT * FROM request
    WHERE user_id=?
");
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetchAll();
?>

<h2>Dashboard Siswa</h2>
<a href="tambah_request.php">Tambah Request</a> |
<a href="../logout.php">Logout</a>

<table border="1" cellpadding="5">
<tr>
    <th>Judul</th>
    <th>Deskripsi</th>
    <th>Lokasi</th>
    <th>Foto</th>
    <th>Tanggal Lapor</th>
    <th>Status</th>
</tr>
<?php foreach ($data as $d): ?>
<tr>
    <td><?= htmlspecialchars($d['judul']) ?></td>
    <td><?= htmlspecialchars($d['deskripsi']) ?></td>
    <td><?= htmlspecialchars($d['lokasi']) ?></td>

    <td align="center">
        <?php if (!empty($d['foto'])):  ?>
            <img src="../uploads/<?= htmlspecialchars($d['foto']) ?>"
                width="120"
                alt="foto request">
        <?php else: ?>
            <i>-</i>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($d['tanggal_lapor']) ?></td>
    <td><?= htmlspecialchars($d['status']) ?></td>
</tr>
<?php endforeach; ?>
</table>
