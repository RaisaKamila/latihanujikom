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
   FUNGSI TRIGGER TASK LOG (TEKNISI)
================================ */
function triggerTask($conn, $request_id, $status_awal, $status_akhir, $catatan, $user_id)
{
    $stmt = $conn->prepare("
        INSERT INTO task_log
        (request_id, user_id, status_admin, status_teknisi, keterangan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $request_id,
        $user_id,
        $status_awal,     // status sebelum
        $status_akhir,    // status sesudah
        $catatan
    ]);
}

/* ===============================
   HANDLE UPDATE TEKNISI
================================ */
$error = '';
if (isset($_POST['update'])) {

    // ambil status awal
    $stmt = $conn->prepare("SELECT status FROM request WHERE request_id=?");
    $stmt->execute([$_POST['request_id']]);
    $status_awal = $stmt->fetchColumn();

    // validasi state machine
    $allowed_transitions = [
        'approve' => ['process'],    // admin approve -> teknisi bisa mulai process
        'process' => ['done'],       // process -> done
        'done'    => []              // done -> tidak bisa update lagi
    ];

    if (!isset($allowed_transitions[$status_awal])) {
        $error = "Status saat ini ('$status_awal') tidak bisa diubah!";
    } elseif (!in_array($_POST['status'], $allowed_transitions[$status_awal])) {
        $error = "Perubahan status tidak valid dari '$status_awal' ke '{$_POST['status']}'!";
    } else {
        // update status request
        $conn->prepare("UPDATE request SET status=? WHERE request_id=?")
            ->execute([$_POST['status'], $_POST['request_id']]);

        // log aksi teknisi
        triggerTask(
            $conn,
            $_POST['request_id'],
            $status_awal,
            $_POST['status'],
            $_POST['catatan'],
            $user_id
        );

        header("Location: dashboard.php");
        exit;
    }
}

/* ===============================
   DATA REQUEST TEKNISI (approved admin)
================================ */
/* ===============================
   DATA REQUEST TEKNISI (approved admin)
================================ */
$data = $conn->prepare("
    SELECT r.*,
           t_log.keterangan AS catatan_terakhir
    FROM request r
    JOIN tasks t ON r.request_id = t.request_id
    LEFT JOIN (
        SELECT request_id, keterangan
        FROM task_log
        WHERE user_id = ?
        ORDER BY log_id DESC
    ) t_log ON r.request_id = t_log.request_id
    WHERE t.technician_id = ?
    AND r.status != 'open'
    GROUP BY r.request_id
");
$data->execute([$user_id, $user_id]);
$data = $data->fetchAll();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Teknisi</title>
</head>
<body>
<h2>Dashboard Teknisi</h2>
<a href="../logout.php">Logout</a>

<?php if ($error): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<table border="1" cellpadding="5">
<tr>
    <th>Judul</th>
    <th>Deskripsi</th>
    <th>Lokasi</th>
    <th>Fotoy</th>
    <th>Status</th>

    <th>Update</th>
      <th>Catatn</th>
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
<td><?= htmlspecialchars($d['status']) ?></td>


<td>
<?php if ($d['status'] != 'done'): ?>
<form method="post">
    <input type="hidden" name="request_id" value="<?= $d['request_id'] ?>">
    <select name="status" required>
        <?php if ($d['status'] == 'approve'): ?>
            <option value="process">Process</option>
        <?php elseif ($d['status'] == 'process'): ?>
            <option value="done">Done</option>
        <?php endif; ?>
    </select><br>
    <textarea name="catatan" placeholder="Catatan teknisi"></textarea><br>
    <button name="update">Update</button>
</form>
<?php else: ?>
    <em>Status sudah selesai</em>
<?php endif; ?>
</td>

<td><?= htmlspecialchars($d['catatan_terakhir'] ?? '-') ?></td>

</tr>
<?php endforeach; ?>
</table>
</body>
</html>
