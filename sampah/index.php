<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_admin($is_admin, $base);
    wajib_post_aman();
    $aksi = $_POST["aksi"] ?? "";
    if ($aksi === "simpan") {
        $id = (int) ($_POST["id"] ?? 0);
        $jenis = trim($_POST["jenis"] ?? "");
        $harga = (int) ($_POST["harga"] ?? 0);
        $stok = (float) ($_POST["stok"] ?? 0);
        if ($jenis === "" || $harga <= 0 || $stok < 0) kembali("error", "Isi jenis, harga (lebih dari 0), dan stok dengan benar.");
        if ($id > 0) {
            q($koneksi, "UPDATE sampah SET jenis_sampah=?, harga_per_kg=?, stok_kg=? WHERE id_sampah=?", "sidi", $jenis, $harga, $stok, $id);
            kembali("success", "Data sampah diperbarui.");
        }
        q($koneksi, "INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES (?,?,?)", "sid", $jenis, $harga, $stok);
        kembali("success", "Jenis sampah ditambahkan.");
    }
    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);
        $pakai = q($koneksi, "SELECT COUNT(*) AS c FROM transaksi WHERE id_sampah=?", "i", $id)[0]["c"];
        if ($pakai > 0) kembali("error", "Jenis sampah ini sudah dipakai di transaksi, jadi tidak bisa dihapus.");
        q($koneksi, "DELETE FROM sampah WHERE id_sampah=?", "i", $id);
        kembali("success", "Jenis sampah dihapus.");
    }
}

$edit = null;
if ($is_admin && isset($_GET["edit"])) {
    $edit = q($koneksi, "SELECT * FROM sampah WHERE id_sampah=?", "i", (int) $_GET["edit"])[0] ?? null;
}
$rows = q($koneksi, "SELECT * FROM sampah ORDER BY jenis_sampah");

tampil_header("Data Sampah", "sampah", $base, $is_admin);
?>
<div class="page-head">
    <div><h1>Data Sampah</h1><p><?= $is_admin ? "Atur jenis, harga, dan stok sampah." : "Daftar harga sampah per Kg." ?></p></div>
</div>

<?php if ($is_admin): ?>
<form method="POST" class="form-card">
    <h2 class="section-title"><?= $edit ? "Ubah jenis sampah" : "Tambah jenis sampah" ?></h2>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit["id_sampah"] ?? 0) ?>">
    <div class="form-row">
        <div class="form-group"><label for="jenis">Jenis sampah</label>
            <input class="form-control" id="jenis" name="jenis" required value="<?= e($edit["jenis_sampah"] ?? "") ?>" placeholder="Contoh: kardus"></div>
        <div class="form-group"><label for="harga">Harga per Kg (Rp)</label>
            <input class="form-control" type="number" min="1" id="harga" name="harga" required value="<?= e($edit["harga_per_kg"] ?? "") ?>"></div>
        <div class="form-group"><label for="stok">Stok (Kg)</label>
            <input class="form-control" type="number" min="0" step="0.1" id="stok" name="stok" required value="<?= e($edit["stok_kg"] ?? 0) ?>"></div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit"><?= $edit ? "Simpan perubahan" : "Tambah sampah" ?></button>
        <?php if ($edit): ?><a class="btn btn-secondary" href="index.php">Batal</a><?php endif; ?>
    </div>
</form>
<?php endif; ?>

<div class="table-card">
<?php if ($rows): ?>
    <table>
        <thead><tr><th>Jenis</th><th class="text-right">Harga / Kg</th><th class="text-right">Stok</th><?php if ($is_admin): ?><th class="text-right">Aksi</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= e($r["jenis_sampah"]) ?></strong></td>
                <td class="text-right"><?= rp($r["harga_per_kg"]) ?></td>
                <td class="text-right"><?= number_format($r["stok_kg"], 1, ",", ".") ?> Kg</td>
                <?php if ($is_admin): ?>
                <td class="text-right"><div class="actions end">
                    <a class="btn btn-warning btn-sm" href="index.php?edit=<?= (int) $r["id_sampah"] ?>">Ubah</a>
                    <form method="POST" onsubmit="return confirm('Hapus jenis sampah ini?')"><?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $r["id_sampah"] ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                </div></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?><p class="empty-row">Belum ada jenis sampah.</p><?php endif; ?>
</div>
<?php tampil_footer(); ?>
