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
   KODE LOGIKA / DATABASE
   Contoh: ambil data user
================================ */
$query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <!-- Contoh link CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <h1>Halo, <?php echo htmlspecialchars($user['nama']); ?></h1>
        <nav>
            <a href="dashboard.php">Dashboard</a> |
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <section>
            <h2>Informasi Akun</h2>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>
        </section>

        <section>
            <h2>Data Lain</h2>
            <?php
            // contoh menampilkan data dari database
            $sql = "SELECT * FROM reports ORDER BY id DESC";
            $res = $conn->query($sql);
            if ($res->num_rows > 0) {
                echo "<ul>";
                while ($row = $res->fetch_assoc()) {
                    echo "<li>Report ID: " . htmlspecialchars($row['id']) . " - Status: " . htmlspecialchars($row['status']) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Tidak ada data laporan.</p>";
            }
            ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y'); ?> Sistem Admin</p>
    </footer>
</body>
</html>
