<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!function_exists('triggerTask')) {
    function triggerTask($conn, $request_id, $user_id, $status_admin, $status_teknisi, $keterangan, $technician_id = null)
    {
        $conn->prepare("
            INSERT INTO task_log
            (request_id, user_id, status_admin, status_teknisi, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $request_id,
            $user_id,
            $status_admin,
            $status_teknisi,
            $keterangan
        ]);

        if ($technician_id) {
            $conn->prepare("
                INSERT INTO tasks
                (request_id, technician_id, status_awal, status_akhir, catatan)
                VALUES (?, ?, 'open', 'process', 'Ditugaskan oleh admin')
            ")->execute([$request_id, $technician_id]);
        }
    }
}

/* HANDLE APPROVE */
if (isset($_POST['approve'])) {
    $request_id = $_POST['request_id'];
    $technician_id = $_POST['technician_id'];

    // Ubah status request jadi 'approve'
    $conn->prepare("
        UPDATE request SET status='approve'
        WHERE request_id=?
    ")->execute([$request_id]);

    // Assign teknisi di tabel tasks
    $conn->prepare("
        INSERT INTO tasks
        (request_id, technician_id, status_awal, status_akhir, catatan)
        VALUES (?, ?, 'open', 'process', 'Ditugaskan oleh admin')
    ")->execute([$request_id, $technician_id]);

    // Catat log admin approve
    $conn->prepare("
        INSERT INTO task_log
        (request_id, user_id, status_admin, status_teknisi, keterangan)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $request_id,
        $user_id,
        'approved', // hanya untuk log admin
        '-',        // teknisi belum update
        'Disetujui admin'
    ]);

    header("Location: dashboard.php");
    exit;
}



/* HANDLE REJECT */
if (isset($_POST['reject'])) {
    $conn->prepare("
        UPDATE request SET status='reject'
        WHERE request_id=?
    ")->execute([$_POST['request_id']]);

    triggerTask(
        $conn,
        $_POST['request_id'],
        $user_id,
        'reject',
        '-',
        $_POST['catatan']
    );

    header("Location: dashboard.php");
    exit;
}

/* DATA TEKNISI */
$teknisi = $conn->query("
    SELECT user_id, nama FROM users WHERE role='teknisi'
")->fetchAll();

/* SEARCH */
$search = $_GET['search'] ?? '';

$sql = "
SELECT 
    r.*,
    u.nama AS pelapor,
    ut.nama AS teknisi
FROM request r
JOIN users u ON r.user_id = u.user_id
LEFT JOIN tasks t ON r.request_id = t.request_id
LEFT JOIN users ut ON t.technician_id = ut.user_id
WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= "
        AND (
            r.judul LIKE ?
            OR r.status LIKE ?
            OR r.lokasi LIKE ?
            OR r.tanggal_lapor LIKE ?
            OR u.nama LIKE ?
            OR ut.nama LIKE ?
        )
    ";

    $like = "%$search%";
    $params = array_fill(0, 6, $like);
}

$sql .= " ORDER BY r.tanggal_lapor DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

/* STATISTIK */
$stat = $conn->query("
    SELECT status, COUNT(*) total
    FROM request
    GROUP BY status
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
</head>

<body>

<h2>Dashboard Admin</h2>
<a href="../logout.php">Logout</a>

<hr>

<h3>Statistik Laporan</h3>
<table border="1" cellpadding="5">
<tr>
    <th>Status</th>
    <th>Total</th>
</tr>
<?php foreach ($stat as $s): ?>
<tr>
    <td><?= $s['status'] ?></td>
    <td><?= $s['total'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<hr>

<h3>Search Laporan</h3>
<form method="get">
    <input 
        type="text" 
        name="search" 
        placeholder="Cari judul / pelapor / teknisi / status / lokasi / tanggal"
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
        autocomplete="off"
    >
    <button type="submit">Filter</button>
</form>

<hr>

<h3>Data Laporan</h3>
<table border="1" cellpadding="5">
<tr>
    <th>No</th>
    <th>Pelapor</th>
    <th>Judul</th>
    <th>Deskripsi</th>
    <th>Foto</th>
    <th>Lokasi</th>
    <th>Teknisi</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>

<?php $no=1; foreach ($data as $d): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($d['pelapor']) ?></td>
    <td><?= htmlspecialchars($d['judul']) ?></td>
    <td><?= htmlspecialchars($d['deskripsi']) ?></td>
    <td align="center">
        <?php if (!empty($d['foto'])):  ?>
            <img src="../uploads/<?= htmlspecialchars($d['foto']) ?>"
                width="120"
                alt="foto request">
        <?php else: ?>
            <i>-</i>
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($d['lokasi']) ?></td>
    <td><?= htmlspecialchars($d['teknisi'] ?? '-') ?></td>
    <td><?= htmlspecialchars($d['status']) ?></td>
    <td>
        <?php if ($d['status'] == 'open'): ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="request_id" value="<?= $d['request_id'] ?>">
            <select name="technician_id" required>
                <?php foreach ($teknisi as $t): ?>
                    <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <button name="approve">Approve</button>
        </form>

        <form method="post" style="display:inline;">
            <input type="hidden" name="request_id" value="<?= $d['request_id'] ?>">
            <input type="text" name="catatan" placeholder="Alasan reject" required>
            <button name="reject">Reject</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
