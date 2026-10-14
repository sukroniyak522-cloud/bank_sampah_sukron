<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_admin($is_admin, $base);
    wajib_post_aman();
    $uid = (int) ($_POST["id_user"] ?? 0);
    $nominal = (int) ($_POST["nominal"] ?? 0);
    $ket = trim($_POST["keterangan"] ?? "") ?: "Top up saldo";
    $ada = q($koneksi, "SELECT id_user FROM users WHERE id_user=?", "i", $uid);
    if (!$ada || $nominal <= 0) kembali("error", "Pilih user dan isi nominal lebih dari 0.");
    try {
        mysqli_begin_transaction($koneksi);
        q($koneksi, "INSERT INTO topup (id_user, nominal, keterangan, dicatat_oleh, tanggal) VALUES (?,?,?,?,NOW())", "iisi", $uid, $nominal, mb_substr($ket, 0, 100), $id_user);
        q($koneksi, "UPDATE users SET saldo = saldo + ? WHERE id_user=?", "ii", $nominal, $uid);
        mysqli_commit($koneksi);
    } catch (Throwable $ex) { mysqli_rollback($koneksi); kembali("error", "Gagal menyimpan top up. Pastikan tabel topup sudah dibuat (topup.sql)."); }
    kembali("success", "Top up " . rp($nominal) . " berhasil.");
}

try {
    $sql = "SELECT p.*, u.nama FROM topup p JOIN users u ON u.id_user=p.id_user";
    $rows = $is_admin ? q($koneksi, $sql . " ORDER BY p.id_topup DESC")
                      : q($koneksi, $sql . " WHERE p.id_user=? ORDER BY p.id_topup DESC", "i", $id_user);
} catch (Throwable $ex) { $rows = []; set_flash("error", "Tabel topup belum ada. Jalankan topup.sql di phpMyAdmin."); }
$daftarUser = $is_admin ? q($koneksi, "SELECT id_user, nama, role FROM users ORDER BY nama") : [];

tampil_header("Top Up", "topup", $base, $is_admin);
?>
<div class="page-head"><div><h1>Top Up</h1><p><?= $is_admin ? "Isi saldo nasabah secara manual." : "Riwayat top up saya." ?></p></div></div>

<?php if ($is_admin): ?>
<form method="POST" class="form-card">
    <h2 class="section-title">Isi saldo</h2>
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group"><label for="id_user">User</label>
            <select class="form-control" id="id_user" name="id_user" required>
                <?php foreach ($daftarUser as $u): ?><option value="<?= (int) $u["id_user"] ?>"><?= e($u["nama"]) ?> (<?= e($u["role"]) ?>)</option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="nominal">Nominal (Rp)</label><input class="form-control" type="number" min="1" id="nominal" name="nominal" required></div>
        <div class="form-group"><label for="keterangan">Keterangan</label><input class="form-control" id="keterangan" name="keterangan" maxlength="100" placeholder="Top up saldo"></div>
    </div>
    <div class="actions"><button class="btn btn-primary" type="submit">Top up sekarang</button></div>
</form>
<?php endif; ?>

<div class="table-card">
<?php if ($rows): ?>
    <table>
        <thead><tr><th>Tanggal</th><?php if ($is_admin): ?><th>User</th><?php endif; ?><th>Keterangan</th><th class="text-right">Nominal</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= date("d/m/Y H:i", strtotime($r["tanggal"])) ?></td>
                <?php if ($is_admin): ?><td><?= e($r["nama"]) ?></td><?php endif; ?>
                <td><?= e($r["keterangan"]) ?></td><td class="text-right num-in">+ <?= rp($r["nominal"]) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?><p class="empty-row">Belum ada riwayat top up.</p><?php endif; ?>
</div>
<?php tampil_footer(); ?>
