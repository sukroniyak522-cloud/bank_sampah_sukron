<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_admin($is_admin, $base);
    wajib_post_aman();
    $aksi = $_POST["aksi"] ?? "";
    $t = q($koneksi, "SELECT * FROM tarik_saldo WHERE id_tarik=? AND status='pending'", "i", (int) ($_POST["id"] ?? 0))[0] ?? null;
    if (!$t) kembali("error", "Pengajuan tidak ditemukan atau sudah diproses.");
    if ($aksi === "tolak") {
        q($koneksi, "UPDATE tarik_saldo SET status='ditolak' WHERE id_tarik=?", "i", $t["id_tarik"]);
        kembali("success", "Pengajuan ditolak.");
    }
    if ($aksi === "setujui") {
        try {
            mysqli_begin_transaction($koneksi);
            $dipotong = q($koneksi, "UPDATE users SET saldo = saldo - ? WHERE id_user=? AND saldo >= ?", "iii", $t["nominal"], $t["id_user"], $t["nominal"]);
            if ($dipotong < 1) { mysqli_rollback($koneksi); kembali("error", "Saldo nasabah tidak cukup."); }
            q($koneksi, "UPDATE tarik_saldo SET status='sukses' WHERE id_tarik=?", "i", $t["id_tarik"]);
            mysqli_commit($koneksi);
        } catch (Throwable $ex) { mysqli_rollback($koneksi); kembali("error", "Gagal memproses penarikan."); }
        kembali("success", "Penarikan disetujui, saldo dipotong.");
    }
}

$sql = "SELECT w.*, u.nama FROM tarik_saldo w JOIN users u ON u.id_user=w.id_user";
$rows = $is_admin ? q($koneksi, $sql . " ORDER BY w.id_tarik DESC")
                  : q($koneksi, $sql . " WHERE w.id_user=? ORDER BY w.id_tarik DESC", "i", $id_user);

tampil_header("Tarik Saldo", "tarik_saldo", $base, $is_admin);
?>
<div class="page-head">
    <div><h1>Tarik Saldo</h1><p>Pengajuan pencairan saldo, disetujui oleh admin.</p></div>
    <a class="btn btn-primary" href="tambah.php">Ajukan penarikan</a>
</div>
<div class="table-card">
<?php if ($rows): ?>
    <table>
        <thead><tr><th>Tanggal</th><?php if ($is_admin): ?><th>Nasabah</th><?php endif; ?><th class="text-right">Nominal</th><th>Status</th><?php if ($is_admin): ?><th class="text-right">Aksi</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= date("d/m/Y H:i", strtotime($r["tanggal"])) ?></td>
                <?php if ($is_admin): ?><td><?= e($r["nama"]) ?></td><?php endif; ?>
                <td class="text-right num-in"><?= rp($r["nominal"]) ?></td>
                <td><span class="badge st-<?= e($r["status"]) ?>"><?= e(ucfirst($r["status"])) ?></span></td>
                <?php if ($is_admin): ?><td class="text-right">
                    <?php if ($r["status"] === "pending"): ?>
                    <div class="actions end">
                        <?php foreach (["setujui" => ["Setujui", "btn-primary"], "tolak" => ["Tolak", "btn-danger"]] as $a => $v): ?>
                        <form method="POST" onsubmit="return confirm('<?= $v[0] ?> pengajuan ini?')"><?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="<?= $a ?>"><input type="hidden" name="id" value="<?= (int) $r["id_tarik"] ?>">
                            <button class="btn <?= $v[1] ?> btn-sm" type="submit"><?= $v[0] ?></button></form>
                        <?php endforeach; ?>
                    </div><?php endif; ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?><p class="empty-row">Belum ada pengajuan penarikan.</p><?php endif; ?>
</div>
<?php tampil_footer(); ?>
