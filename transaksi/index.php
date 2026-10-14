<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

/** Terapkan efek transaksi sukses ke saldo & stok */
function terapkan($k, $tipe, $id_nasabah, $id_sampah, $berat, $total, $id_admin)
{
    if ($tipe === "setor") {
        q($k, "UPDATE users SET saldo = saldo + ? WHERE id_user=?", "ii", $total, $id_nasabah);
        q($k, "UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah=?", "ii", $berat, $id_sampah);
    } else { // jual ke pengepul: stok berkurang, kas admin bertambah
        q($k, "UPDATE sampah SET stok_kg = stok_kg - ? WHERE id_sampah=?", "ii", $berat, $id_sampah);
        q($k, "UPDATE users SET saldo = saldo + ? WHERE id_user=?", "ii", $total, $id_admin);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_admin($is_admin, $base);
    wajib_post_aman();
    $aksi = $_POST["aksi"] ?? "";
    try {
        if ($aksi === "catat") {
            $uid = (int) ($_POST["id_user"] ?? 0);
            $sid = (int) ($_POST["id_sampah"] ?? 0);
            $tipe = ($_POST["tipe"] ?? "") === "jual_pengepul" ? "jual_pengepul" : "setor";
            $berat = (int) ($_POST["berat"] ?? 0);
            $s = q($koneksi, "SELECT * FROM sampah WHERE id_sampah=?", "i", $sid)[0] ?? null;
            $u = q($koneksi, "SELECT id_user FROM users WHERE id_user=?", "i", $uid)[0] ?? null;
            if (!$s || !$u || $berat <= 0) kembali("error", "Pilih nasabah, jenis sampah, dan isi berat (bilangan bulat, minimal 1 Kg).");
            if ($tipe === "jual_pengepul" && $berat > $s["stok_kg"]) kembali("error", "Stok tidak cukup untuk dijual.");
            $total = $berat * (int) $s["harga_per_kg"];
            mysqli_begin_transaction($koneksi);
            q($koneksi, "INSERT INTO transaksi (id_user,id_sampah,tipe,berat_kg,total_rp,tanggal,status) VALUES (?,?,?,?,?,NOW(),'sukses')", "iisii", $uid, $sid, $tipe, $berat, $total);
            terapkan($koneksi, $tipe, $uid, $sid, $berat, $total, $id_user);
            mysqli_commit($koneksi);
            kembali("success", "Transaksi dicatat: " . rp($total));
        }
        if ($aksi === "setujui") {
            $t = q($koneksi, "SELECT * FROM transaksi WHERE id_transaksi=? AND status='pending'", "i", (int) ($_POST["id"] ?? 0))[0] ?? null;
            if (!$t) kembali("error", "Transaksi tidak ditemukan atau sudah diproses.");
            mysqli_begin_transaction($koneksi);
            q($koneksi, "UPDATE transaksi SET status='sukses' WHERE id_transaksi=?", "i", $t["id_transaksi"]);
            terapkan($koneksi, $t["tipe"], $t["id_user"], $t["id_sampah"], $t["berat_kg"], $t["total_rp"], $id_user);
            mysqli_commit($koneksi);
            kembali("success", "Transaksi disetujui, saldo dan stok diperbarui.");
        }
    } catch (Throwable $ex) {
        mysqli_rollback($koneksi);
        kembali("error", "Gagal memproses transaksi.");
    }
}

$sql = "SELECT t.*, u.nama, s.jenis_sampah FROM transaksi t JOIN users u ON u.id_user=t.id_user JOIN sampah s ON s.id_sampah=t.id_sampah";
$rows = $is_admin ? q($koneksi, $sql . " ORDER BY t.id_transaksi DESC")
                  : q($koneksi, $sql . " WHERE t.id_user=? ORDER BY t.id_transaksi DESC", "i", $id_user);
if ($is_admin) {
    $daftarUser = q($koneksi, "SELECT id_user, nama, role FROM users ORDER BY nama");
    $daftarSampah = q($koneksi, "SELECT id_sampah, jenis_sampah, harga_per_kg, stok_kg FROM sampah ORDER BY jenis_sampah");
}

tampil_header("Transaksi", "transaksi", $base, $is_admin);
?>
<div class="page-head">
    <div><h1>Transaksi</h1><p><?= $is_admin ? "Catat setoran nasabah dan penjualan ke pengepul." : "Riwayat setoran sampah saya." ?></p></div>
</div>

<?php if ($is_admin): ?>
<form method="POST" class="form-card">
    <h2 class="section-title">Catat transaksi baru</h2>
    <?= csrf_field() ?><input type="hidden" name="aksi" value="catat">
    <div class="form-row">
        <div class="form-group"><label for="tipe">Tipe</label>
            <select class="form-control" id="tipe" name="tipe">
                <option value="setor">Setor (saldo nasabah bertambah)</option>
                <option value="jual_pengepul">Jual ke pengepul (stok berkurang)</option>
            </select></div>
        <div class="form-group"><label for="id_user">Nasabah / pengepul</label>
            <select class="form-control" id="id_user" name="id_user" required>
                <?php foreach ($daftarUser as $u): ?><option value="<?= (int) $u["id_user"] ?>"><?= e($u["nama"]) ?> (<?= e($u["role"]) ?>)</option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="id_sampah">Jenis sampah</label>
            <select class="form-control" id="id_sampah" name="id_sampah" required>
                <?php foreach ($daftarSampah as $s): ?><option value="<?= (int) $s["id_sampah"] ?>"><?= e($s["jenis_sampah"]) ?> - <?= rp($s["harga_per_kg"]) ?>/Kg (stok <?= e($s["stok_kg"]) ?>)</option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="berat">Berat (Kg)</label>
            <input class="form-control" type="number" min="1" step="1" id="berat" name="berat" required></div>
    </div>
    <div class="actions"><button class="btn btn-primary" type="submit">Catat transaksi</button></div>
</form>
<?php endif; ?>

<div class="table-card">
<?php if ($rows): ?>
    <table>
        <thead><tr><th>Tanggal</th><?php if ($is_admin): ?><th>Nasabah</th><?php endif; ?><th>Jenis</th><th>Tipe</th><th class="text-right">Berat</th><th class="text-right">Total</th><th>Status</th><?php if ($is_admin): ?><th class="text-right">Aksi</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= date("d/m/Y H:i", strtotime($r["tanggal"])) ?></td>
                <?php if ($is_admin): ?><td><?= e($r["nama"]) ?></td><?php endif; ?>
                <td><strong><?= e($r["jenis_sampah"]) ?></strong></td>
                <td><span class="badge-tipe tipe-<?= e($r["tipe"]) ?>"><?= e(ucfirst(str_replace("_", " ", $r["tipe"]))) ?></span></td>
                <td class="text-right"><?= (int) $r["berat_kg"] ?> Kg</td>
                <td class="text-right num-in"><?= rp($r["total_rp"]) ?></td>
                <td><span class="badge st-<?= e($r["status"]) ?>"><?= e(ucfirst($r["status"])) ?></span></td>
                <?php if ($is_admin): ?><td class="text-right">
                    <?php if ($r["status"] === "pending"): ?>
                    <form method="POST" onsubmit="return confirm('Setujui transaksi ini?')"><?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id" value="<?= (int) $r["id_transaksi"] ?>">
                        <button class="btn btn-primary btn-sm" type="submit">Setujui</button></form>
                    <?php endif; ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?><p class="empty-row">Belum ada transaksi.</p><?php endif; ?>
</div>
<?php tampil_footer(); ?>
