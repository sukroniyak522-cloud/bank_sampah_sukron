<?php
$base = "";
require "cek_login.php";
require "layout.php";

$totalJenisSampah = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM sampah"))["jml"];
$totalStok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(stok_kg),0) AS jml FROM sampah"))["jml"];

if ($is_admin) {
    $totalTransaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM transaksi"))["jml"];
    $tarikPending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM tarik_saldo WHERE status = 'pending'"))["jml"];
} else {
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM transaksi WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $totalTransaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM tarik_saldo WHERE status = 'pending' AND id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $tarikPending = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
    mysqli_stmt_close($stmt);
}

// Ambil 5 transaksi terakhir untuk widget aktivitas terbaru
$sqlTerbaru = "SELECT t.*, u.nama, s.Jenis_sampah 
               FROM transaksi t 
               JOIN users u ON t.id_user = u.id_user 
               JOIN sampah s ON t.id_sampah = s.Id_sampah ";
if (!$is_admin) {
    $sqlTerbaru .= " WHERE t.id_user = " . (int)$id_user;
}
$sqlTerbaru .= " ORDER BY t.id_transaksi DESC LIMIT 5";
$recentTx = mysqli_query($koneksi, $sqlTerbaru);

tampil_header("Dashboard", "dashboard", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>Halo, <?= e($user["nama"]) ?></h1>
        <p>Ringkasan tabungan dan aktivitas bank sampah.</p>
    </div>
</div>

<!-- Buku tabungan: saldo -->
<section class="passbook" aria-label="Saldo tabungan">
    <div class="pb-main">
        <p class="pb-label">Saldo <?= $is_admin ? "utama" : "saya" ?></p>
        <p class="pb-amount"><?= rp($user["saldo"]) ?></p>
        <a class="pb-action" href="tarik_saldo/tambah.php">Ajukan penarikan</a>
    </div>
    <div class="pb-stub">
        <span class="pb-role"><?= e(ucfirst($user["role"])) ?></span>
        <span class="pb-user">@<?= e($user["username"]) ?></span>
    </div>
</section>

<!-- Ringkasan angka -->
<dl class="stats">
    <div class="stat">
        <dt>Jenis sampah</dt>
        <dd><?= (int) $totalJenisSampah ?></dd>
    </div>
    <div class="stat">
        <dt>Total stok</dt>
        <dd><?= number_format($totalStok, 1, ",", ".") ?> <span>Kg</span></dd>
    </div>
    <div class="stat">
        <dt><?= $is_admin ? "Total transaksi" : "Transaksi saya" ?></dt>
        <dd><?= (int) $totalTransaksi ?></dd>
    </div>
</dl>

<div class="dash-grid">
    <!-- Kiri: akses cepat -->
    <div>
        <div class="section-head">
            <h2 class="section-title">Akses cepat</h2>
        </div>
        <div class="menu-list">
            <a href="sampah/index.php" class="menu-item">
                <span class="menu-ico"><?= ikon('sampah') ?></span>
                <span>
                    <span class="menu-title">Data Sampah</span>
                    <span class="menu-desc"><?= $is_admin ? "Atur jenis, harga & stok" : "Daftar harga per Kg" ?></span>
                </span>
                <span class="menu-arrow"><?= ikon('panah') ?></span>
            </a>

            <a href="transaksi/index.php" class="menu-item">
                <span class="menu-ico"><?= ikon('transaksi') ?></span>
                <span>
                    <span class="menu-title">Transaksi</span>
                    <span class="menu-desc"><?= $is_admin ? "Catat setoran nasabah" : "Riwayat setoran" ?></span>
                </span>
                <span class="menu-arrow"><?= ikon('panah') ?></span>
            </a>

            <a href="tarik_saldo/index.php" class="menu-item">
                <span class="menu-ico"><?= ikon('tarik') ?></span>
                <span>
                    <span class="menu-title">Tarik Saldo</span>
                    <span class="menu-desc">
                        Pengajuan pencairan
                        <?php if ($tarikPending > 0): ?>
                            <span class="pending-note">(<?= (int) $tarikPending ?> pending)</span>
                        <?php endif; ?>
                    </span>
                </span>
                <span class="menu-arrow"><?= ikon('panah') ?></span>
            </a>

            <a href="topup/index.php" class="menu-item">
                <span class="menu-ico"><?= ikon('topup') ?></span>
                <span>
                    <span class="menu-title">Top Up</span>
                    <span class="menu-desc"><?= $is_admin ? "Isi saldo nasabah" : "Riwayat top up saya" ?></span>
                </span>
                <span class="menu-arrow"><?= ikon('panah') ?></span>
            </a>

            <?php if ($is_admin): ?>
            <a href="user/index.php" class="menu-item">
                <span class="menu-ico"><?= ikon('user') ?></span>
                <span>
                    <span class="menu-title">Kelola User</span>
                    <span class="menu-desc">Tambah, ubah & hapus akun</span>
                </span>
                <span class="menu-arrow"><?= ikon('panah') ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kanan: transaksi terakhir -->
    <div>
        <div class="section-head">
            <h2 class="section-title">Transaksi terakhir</h2>
            <a href="transaksi/index.php">Lihat semua</a>
        </div>
        <div class="table-card">
            <?php if ($recentTx && mysqli_num_rows($recentTx) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Tipe</th>
                            <th class="text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($recentTx)): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r["Jenis_sampah"]) ?></strong>
                                    <span class="sub"><?= date("d/m H:i", strtotime($r["tanggal"])) ?></span>
                                </td>
                                <td>
                                    <span class="badge-tipe tipe-<?= e(strtolower($r["tipe"])) ?>">
                                        <?= ucfirst(str_replace("_", " ", e($r["tipe"]))) ?>
                                    </span>
                                </td>
                                <td class="text-right num-in"><?= rp($r["total_rp"]) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-row">Belum ada riwayat transaksi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php tampil_footer(); ?>
