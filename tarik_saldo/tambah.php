<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajib_post_aman();
    $nominal = (int) ($_POST["nominal"] ?? 0);
    if ($nominal < 1000) kembali("error", "Minimal penarikan Rp 1.000.", "tambah.php");
    if ($nominal > (int) $user["saldo"]) kembali("error", "Nominal melebihi saldo kamu (" . rp($user["saldo"]) . ").", "tambah.php");
    q($koneksi, "INSERT INTO tarik_saldo (id_user, nominal, status, tanggal) VALUES (?,?,'pending',NOW())", "ii", $id_user, $nominal);
    kembali("success", "Pengajuan terkirim, menunggu persetujuan admin.");
}

tampil_header("Ajukan Penarikan", "tarik_saldo", $base, $is_admin);
?>
<div class="page-head"><div><h1>Ajukan penarikan</h1><p>Saldo tersedia: <strong><?= rp($user["saldo"]) ?></strong></p></div></div>
<form method="POST" class="form-card narrow">
    <?= csrf_field() ?>
    <div class="form-group"><label for="nominal">Nominal (Rp)</label>
        <input class="form-control" type="number" id="nominal" name="nominal" min="1000" max="<?= (int) $user["saldo"] ?>" step="500" required autofocus></div>
    <div class="actions"><button class="btn btn-primary" type="submit">Kirim pengajuan</button><a class="btn btn-secondary" href="index.php">Batal</a></div>
</form>
<?php tampil_footer(); ?>
