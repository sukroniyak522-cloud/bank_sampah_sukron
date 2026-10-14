<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";
wajib_admin($is_admin, $base);
$roles = daftar_role($koneksi);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_post_aman();
    $aksi = $_POST["aksi"] ?? "";
    if ($aksi === "simpan") {
        $id = (int) ($_POST["id"] ?? 0);
        $nama = trim($_POST["nama"] ?? "");
        $uname = trim($_POST["username"] ?? "");
        $pass = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "";
        if ($nama === "" || $uname === "" || !in_array($role, $roles, true)) kembali("error", "Nama, username, dan role wajib diisi.");
        if (q($koneksi, "SELECT id_user FROM users WHERE username=? AND id_user<>?", "si", $uname, $id)) kembali("error", "Username sudah dipakai.");
        if ($id > 0) {
            q($koneksi, "UPDATE users SET nama=?, username=?, role=? WHERE id_user=?", "sssi", $nama, $uname, $role, $id);
            if ($pass !== "") q($koneksi, "UPDATE users SET password=? WHERE id_user=?", "si", password_hash($pass, PASSWORD_DEFAULT), $id);
            kembali("success", "Akun diperbarui.");
        }
        if ($pass === "") kembali("error", "Password wajib diisi untuk akun baru.");
        q($koneksi, "INSERT INTO users (nama, username, password, role, saldo) VALUES (?,?,?,?,0)", "ssss", $nama, $uname, password_hash($pass, PASSWORD_DEFAULT), $role);
        kembali("success", "Akun ditambahkan.");
    }
    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);
        if ($id === $id_user) kembali("error", "Kamu tidak bisa menghapus akunmu sendiri.");
        $pakai = q($koneksi, "SELECT (SELECT COUNT(*) FROM transaksi WHERE id_user=?) + (SELECT COUNT(*) FROM tarik_saldo WHERE id_user=?) AS c", "ii", $id, $id)[0]["c"];
        if ($pakai > 0) kembali("error", "Akun ini punya riwayat transaksi/penarikan, jadi tidak bisa dihapus.");
        q($koneksi, "DELETE FROM users WHERE id_user=?", "i", $id);
        kembali("success", "Akun dihapus.");
    }
}

$edit = isset($_GET["edit"]) ? (q($koneksi, "SELECT * FROM users WHERE id_user=?", "i", (int) $_GET["edit"])[0] ?? null) : null;
$rows = q($koneksi, "SELECT * FROM users ORDER BY id_user");

tampil_header("Kelola User", "user", $base, $is_admin);
?>
<div class="page-head"><div><h1>Kelola User</h1><p>Tambah, ubah, dan hapus akun.</p></div></div>

<form method="POST" class="form-card" autocomplete="off">
    <h2 class="section-title"><?= $edit ? "Ubah akun" : "Tambah akun" ?></h2>
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int) ($edit["id_user"] ?? 0) ?>">
    <div class="form-row">
        <div class="form-group"><label for="nama">Nama</label><input class="form-control" id="nama" name="nama" required value="<?= e($edit["nama"] ?? "") ?>"></div>
        <div class="form-group"><label for="username">Username</label><input class="form-control" id="username" name="username" required value="<?= e($edit["username"] ?? "") ?>"></div>
        <div class="form-group"><label for="password">Password <?= $edit ? "(kosongkan jika tidak diganti)" : "" ?></label><input class="form-control" type="password" id="password" name="password" <?= $edit ? "" : "required" ?> autocomplete="new-password"></div>
        <div class="form-group"><label for="role">Role</label>
            <select class="form-control" id="role" name="role"><?php foreach ($roles as $ro): ?><option value="<?= e($ro) ?>" <?= ($edit["role"] ?? "siswa") === $ro ? "selected" : "" ?>><?= e(ucfirst($ro)) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="actions"><button class="btn btn-primary" type="submit"><?= $edit ? "Simpan perubahan" : "Tambah akun" ?></button>
        <?php if ($edit): ?><a class="btn btn-secondary" href="index.php">Batal</a><?php endif; ?></div>
</form>

<div class="table-card">
    <table>
        <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th class="text-right">Saldo</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><strong><?= e($r["nama"]) ?></strong></td><td>@<?= e($r["username"]) ?></td>
                <td><span class="badge"><?= e(ucfirst($r["role"])) ?></span></td>
                <td class="text-right num-in"><?= rp($r["saldo"]) ?></td>
                <td class="text-right"><div class="actions end">
                    <a class="btn btn-warning btn-sm" href="index.php?edit=<?= (int) $r["id_user"] ?>">Ubah</a>
                    <?php if ((int) $r["id_user"] !== $id_user): ?>
                    <form method="POST" onsubmit="return confirm('Hapus akun ini?')"><?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $r["id_user"] ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                    <?php endif; ?></div></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php tampil_footer(); ?>
