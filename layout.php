<?php
// layout.php
// Kerangka halaman bersama (sidebar + konten). Gaya ada di assets/style.css

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rp')) {
    function rp($angka) {
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

/** Ikon SVG sederhana (garis), warnanya ikut warna teks */
function ikon($nama) {
    $bentuk = [
        'dashboard' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.6"/>',
        'sampah'    => '<path d="M4 7h16M9.5 7V4.5h5V7M6 7l.9 12.2a1.5 1.5 0 0 0 1.5 1.3h7.2a1.5 1.5 0 0 0 1.5-1.3L18 7M10 11v6M14 11v6"/>',
        'transaksi' => '<path d="M4 8h13.5M14 4.5 17.5 8 14 11.5M20 16H6.5M10 12.5 6.5 16 10 19.5"/>',
        'tarik'     => '<path d="M12 4v11M7.5 10.5 12 15l4.5-4.5M4.5 19.5h15"/>',
        'topup'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v8M8 12h8"/>',
        'user'      => '<circle cx="9" cy="8" r="3.2"/><path d="M3 19.5c.6-3.2 3-5 6-5s5.4 1.8 6 5M16 5.3a3.2 3.2 0 0 1 0 5.9M18 14.8c1.6.7 2.7 2.1 3 4.7"/>',
        'keluar'    => '<path d="M9.5 4.5h-4v15h4M15.5 8 19.5 12l-4 4M19.5 12H9.5"/>',
        'putar'     => '<path d="M20 12a8 8 0 1 1-2.6-5.9M20 4.5v4.6h-4.6"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'panah'     => '<path d="m9.5 6 6 6-6 6"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($bentuk[$nama] ?? '') . '</svg>';
}

function tampil_header($title = "Bank Sampah", $active = "", $base = "", $is_admin = false) {
    global $user;

    $nama    = $user["nama"] ?? ($_SESSION["nama"] ?? "");
    $role    = $user["role"] ?? ($_SESSION["role"] ?? "");
    $inisial = strtoupper(mb_substr(trim($nama) !== "" ? trim($nama) : "?", 0, 1));

    // halaman tarik saldo lama memakai kode 'tarik'
    if ($active === "tarik") {
        $active = "tarik_saldo";
    }

    // [kode aktif, label, tujuan, ikon]
    $menu = [
        ["dashboard",   "Dashboard",   "index.php",             "dashboard"],
        ["sampah",      "Sampah",      "sampah/index.php",      "sampah"],
        ["transaksi",   "Transaksi",   "transaksi/index.php",   "transaksi"],
        ["tarik_saldo", "Tarik Saldo", "tarik_saldo/index.php", "tarik"],
        ["topup",       "Top Up",      "topup/index.php",       "topup"],
    ];
    if ($is_admin) {
        $menu[] = ["user", "User", "user/index.php", "user"];
    }

    // Pesan sekali tampil (berhasil / gagal) dari halaman sebelumnya
    $flash = function_exists('ambil_flash') ? ambil_flash() : null;

    $css = __DIR__ . "/assets/style.css";
    $ver = is_file($css) ? filemtime($css) : 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Bank Sampah Sukron</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=<?= (int) $ver ?>">
</head>
<body>
    <a class="skip-link" href="#konten">Lewati ke konten</a>

    <div class="app">
        <div class="scrim"></div>

        <aside class="sidebar" id="sidebar">
            <a class="brand" href="<?= e($base) ?>index.php">
                <span class="brand-mark"><?= ikon('putar') ?></span>
                <span class="brand-name"><small>Bank Sampah</small>Sukron</span>
            </a>

            <nav class="nav" aria-label="Menu utama">
                <?php foreach ($menu as $m): ?>
                    <a class="nav-link<?= $active === $m[0] ? ' active' : '' ?>"
                       href="<?= e($base . $m[2]) ?>"<?= $active === $m[0] ? ' aria-current="page"' : '' ?>>
                        <?= ikon($m[3]) ?><span><?= e($m[1]) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-foot">
                <div class="me">
                    <span class="avatar"><?= e($inisial) ?></span>
                    <div>
                        <div class="me-name"><?= e($nama) ?></div>
                        <div class="me-role"><?= e(ucfirst($role)) ?></div>
                    </div>
                </div>
                <a class="nav-link nav-out" href="<?= e($base) ?>logout.php" onclick="return confirm('Yakin ingin keluar?')">
                    <?= ikon('keluar') ?><span>Keluar</span>
                </a>
            </div>
        </aside>

        <div class="content">
            <header class="topbar">
                <a class="brand" href="<?= e($base) ?>index.php">
                    <span class="brand-mark"><?= ikon('putar') ?></span>
                    <span class="brand-name"><small>Bank Sampah</small>Sukron</span>
                </a>
                <button type="button" class="menu-btn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
                    <?= ikon('menu') ?>
                </button>
            </header>

            <main class="main" id="konten">
<?php if ($flash): ?>
                <div class="alert alert-<?= $flash["tipe"] === "success" ? "success" : "error" ?>" role="status"><?= e($flash["pesan"]) ?></div>
<?php endif; ?>
<?php
}

function tampil_footer() {
?>
            </main>

            <footer class="footer">
                &copy; <?= date('Y') ?> Bank Sampah Sukron
            </footer>
        </div>
    </div>

    <script>
        (function () {
            var body = document.body;
            var btn = document.querySelector('.menu-btn');
            var scrim = document.querySelector('.scrim');
            if (!btn) return;
            function setOpen(open) {
                body.classList.toggle('nav-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            btn.addEventListener('click', function () { setOpen(!body.classList.contains('nav-open')); });
            if (scrim) scrim.addEventListener('click', function () { setOpen(false); });
            document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') setOpen(false); });
        })();
    </script>
</body>
</html>
<?php
}
?>
