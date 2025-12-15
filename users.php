<?php


require 'config.php';


/* ===============================
   HANDLE CREATE USER
================================ */
if (isset($_POST['tambah'])) {
    $nama     = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if ($nama && $email && $password && $role) {
        $password_hash = md5($password); // sesuai kebiasaan kamu

        $stmt = $conn->prepare("
            INSERT INTO users (nama, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nama, $email, $password_hash, $role]);

        header("Location: users.php");
        exit;
    }
}

/* ===============================
   HANDLE EDIT USER
================================ */
if (isset($_POST['edit'])) {
    $user_id       = (int)$_POST['user_id'];
    $nama     = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role     = $_POST['role'];

    if ($nama && $email && $role) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET nama = ?, email = ?, role = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$nama, $email, $role, $user_id]);

        header("Location: users.php");
        exit;
    }
}

/* ===============================
   HANDLE DELETE USER
================================ */
if (isset($_GET['hapus'])) {
    $user_id = (int)$_GET['hapus'];

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);

    header("Location: users.php");
    exit;
}

/* ===============================
   HANDLE SEARCH + READ
================================ */
$search = $_GET['search'] ?? '';
$params = [];
$sql = "SELECT * FROM users";

if ($search !== '') {
    $sql .= " WHERE nama LIKE ? OR email LIKE ? OR role LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY user_id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$isPrint = isset($_GET['print']);

?>

<!DOCTYPE html>
<html lang="user_id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen User</title>
    <?php if ($isPrint): ?>
    <style>
        form, a, button {
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
    </style>
    <?php endif; ?>
</head>
<body>

<h1>
    <?= $isPrint 
        ? 'Data users' . ($search ? " (Pencarian: $search)" : '') 
        : 'Data Users' 
    ?>
</h1>

<a href="logout.php">Logout</a>
<br><br>

<!-- TAMBAH USER -->
<form method="POST">
    <input type="text" name="nama" placeholder="Nama" required>
    <input type="email" name="email" placeholder="email" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role" required>
        <option value="">-- Role --</option>
        <option value="admin">Admin</option>
        <option value="teknisi">Teknisi</option>
        <option value="siswa">Siswa</option>

    </select>
    <button name="tambah">Tambah</button>
</form>

<hr>

<!-- SEARCH -->
<form method="GET">
    <input type="text" name="search" placeholder="Cari user..." value="<?= htmlspecialchars($search) ?>">
    <button>Cari</button>
    <?php if ($search): ?>
        <a href="users.php">Reset</a>

        <a href="?search=<?= urlencode($search) ?>&print=1" target="_blank">
            Print
        </a>
    <?php else: ?>
        <a href="?print=1" target="_blank">Print</a>

    <?php endif; ?>
</form>

<hr>

<!-- LIST USER -->
<?php if (!$users): ?>
    <p>Data user kosong.</p>
<?php else: ?>
<table border="1" cellpadding="8">
<tr>
    <th>user_id</th>
    <th>Nama</th>
    <th>Email</th>
    <th>Role</th>
    <th>Aksi</th>
</tr>

<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['user_id'] ?></td>
    <td>
        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['user_id']): ?>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                <input type="text" name="nama" value="<?= htmlspecialchars($u['nama']) ?>" required>
        <?php else: ?>
            <?= htmlspecialchars($u['nama']) ?>
        <?php endif; ?>
    </td>

    <td>
        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['user_id']): ?>
            <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
        <?php else: ?>
            <?= htmlspecialchars($u['email']) ?>
        <?php endif; ?>
    </td>

    <td>
        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['user_id']): ?>
            <select name="role">
                <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                <option value="teknisi" <?= $u['role']=='teknisi'?'selected':'' ?>>teknisi</option>
                <option value="siswa" <?= $u['role']=='siswa'?'selected':'' ?>>Siswa</option>
            </select>
        <?php else: ?>
            <?= $u['role'] ?>
        <?php endif; ?>
    </td>

    <td>
        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['user_id']): ?>
            <button name="edit">Simpan</button>
            <a href="users.php">Batal</a>
        <?php else: ?>
            <a href="?edit_id=<?= $u['user_id'] ?>">Edit</a> |
            <a href="?hapus=<?= $u['user_id'] ?>" onclick="return confirm('Hapus user?')">Hapus</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($isPrint): ?>
<script>
    window.print();
</script>

<?php endif; ?>

</body>
</html>
