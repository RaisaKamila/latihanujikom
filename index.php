<?php
session_start();
require 'config.php';

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
   TRIGGER PHP
================================ */
function triggerAfterAdminDecision($conn, $task_id, $status, $staff_id = null) {

    if ($status === 'approved') {
        $conn->prepare("
            UPDATE tasks SET status = 0 WHERE id = ?
        ")->execute([$task_id]);

        $log = "Task di-approve & diberikan ke staff ID $staff_id";
    } else {
        $log = "Task ditolak oleh admin";
    }

    $conn->prepare("
        INSERT INTO task_logs (task_id, aksi)
        VALUES (?, ?)
    ")->execute([$task_id, $log]);
}

/* ===============================
   USER TAMBAH TASK
================================ */
//if (isset($_POST['tambah']) && $role === 'user') {
if (isset($_POST['tambah']) && in_array($role, ['user', 'admin'])) {
    $task_name = trim($_POST['task_name']);

    if ($task_name !== '') {
        $conn->prepare("
            INSERT INTO tasks (task_name, user_id)
            VALUES (?, ?)
        ")->execute([$task_name, $user_id]);
    }
    header("Location: index.php");
    exit;
}

/* ===============================
   ADMIN APPROVE / REJECT
================================ */
if ($role === 'admin') {

    if (isset($_POST['approve'])) {
        $task_id  = (int)$_POST['task_id'];
        $staff_id = (int)$_POST['staff_id'];

        $conn->prepare("
            UPDATE tasks
            SET approval_status = 'approved',
                staff_id = ?
            WHERE id = ?
        ")->execute([$staff_id, $task_id]);

        triggerAfterAdminDecision($conn, $task_id, 'approved', $staff_id);
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['reject'])) {
        $task_id = (int)$_POST['task_id'];

        $conn->prepare("
            UPDATE tasks
            SET approval_status = 'rejected',
                staff_id = NULL
            WHERE id = ?
        ")->execute([$task_id]);

        triggerAfterAdminDecision($conn, $task_id, 'rejected');
        header("Location: index.php");
        exit;
    }
}

/* ===============================
   STAFF UPDATE STATUS
================================ */
if (isset($_GET['ubah_status']) && in_array($role, ['staff', 'admin'])) {
    $id = (int)$_GET['ubah_status'];

    $stmt = $conn->prepare("
        SELECT status FROM tasks
        WHERE id = ? AND staff_id = ? AND approval_status = 'approved'
    ");
    $stmt->execute([$id, $user_id]);
    $task = $stmt->fetch();

    if ($task) {
        $status_baru = $task['status'] ? 0 : 1;
        $conn->prepare("
            UPDATE tasks SET status = ? WHERE id = ?
        ")->execute([$status_baru, $id]);
    }

    header("Location: index.php");
    exit;
}

/* ===============================
   QUERY DATA SESUAI ROLE
================================ */
$sql = "SELECT * FROM tasks";

if ($role === 'user') {
    $sql .= " WHERE user_id = $user_id";
}
if ($role === 'staff') {
    $sql .= " WHERE staff_id = $user_id AND approval_status = 'approved'";
}

$sql .= " ORDER BY created_at DESC";
$tugas = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>To-Do List Workflow</title>
</head>

<body>

<h2>Aplikasi To-Do List</h2>
<p>Login sebagai <b><?= $role ?></b></p>
<a href="logout.php">Logout</a>
<hr>

<!-- USER INPUT -->
<?php if ($role === 'user' || $role === 'admin'): ?>
<form method="POST">
    <input type="text" name="task_name" placeholder="Nama tugas" required>
    <button name="tambah">Ajukan Tugas</button>
</form>
<hr>
<?php endif; ?>

<table border="1" cellpadding="6">
<tr>
    <th>ID</th>
    <th>Nama</th>
    <th>Status</th>
    <th>Approval</th>
    <th>Aksi</th>
</tr>

<?php foreach ($tugas as $t): ?>
<tr>
    <td><?= $t['id'] ?></td>
    <td>
<?php if ($t['status']): ?>
    <s><?= htmlspecialchars($t['task_name']) ?></s>
<?php else: ?>
    <?= htmlspecialchars($t['task_name']) ?>
<?php endif; ?>
</td>

    <td><?= $t['status'] ? 'Selesai' : 'Belum' ?></td>
    <td><?= $t['approval_status'] ?></td>
    <td>

    <!-- ADMIN -->
    <?php if ($role === 'admin' && $t['approval_status'] === 'pending'): ?>

    <!-- FORM APPROVE -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
        <select name="staff_id" required>
            <option value="">-- Staff --</option>
            <?php
            $staff = $conn->query("SELECT id, nama FROM users WHERE role='staff'");
            foreach ($staff as $s):
            ?>
                <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
            <?php endforeach; ?>
        </select>
        <button name="approve">Approve</button>
    </form>

    <!-- FORM REJECT -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
        <button name="reject">Reject</button>
    </form>

    <!-- STAFF -->
    <?php elseif ($role === 'staff' || $role === 'admin'): ?>
        <a href="?ubah_status=<?= $t['id'] ?>">
            <?= $t['status'] ? 'Batal Selesai' : 'Selesai' ?>
        </a>

    <?php else: ?>
        -
    <?php endif; ?>

    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
