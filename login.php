<?php
session_start();
require "koneksi.php";

// Redirect jika sudah login
if (isset($_SESSION["id_user"])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validasi CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Sesi tidak valid, silakan muat ulang halaman.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($username === "" || $password === "") {
            $error = "Username dan password wajib diisi!";
        } else {
            $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            $cocok = false;
            $needs_rehash = false;

            if ($data) {
                $tersimpan = $data["password"];
                if (password_get_info($tersimpan)["algo"]) {
                    $cocok = password_verify($password, $tersimpan);
                } else {
                    $cocok = hash_equals($tersimpan, $password);
                    if ($cocok) {
                        $needs_rehash = true;
                    }
                }
            }

            if ($cocok) {
                if ($needs_rehash || password_needs_rehash($data["password"], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id_user = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $data["id_user"]);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }

                session_regenerate_id(true);
                $_SESSION["id_user"]  = $data["id_user"];
                $_SESSION["nama"]     = $data["nama"];
                $_SESSION["username"] = $data["username"];
                $_SESSION["role"]     = $data["role"];

                header("Location: index.php");
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        }
    }
}

// Ambil daftar user untuk opsi quick select
$users = mysqli_query($koneksi, "SELECT id_user, nama, username, role FROM users ORDER BY id_user ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Bank Sampah Sukron</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= (int) @filemtime(__DIR__ . "/assets/style.css") ?>">
</head>
<body class="login-body">

    <div class="login-shell">
        <!-- Panel kiri: nama & cara kerja -->
        <section class="login-brand">
            <a class="brand" href="login.php">
                <span class="brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 12a8 8 0 1 1-2.6-5.9M20 4.5v4.6h-4.6"/></svg></span>
                <span class="brand-name"><small>Bank Sampah</small>Sukron</span>
            </a>

            <div>
                <h1>Setor sampah, saldo bertambah.</h1>
                <p>Tabungan sampah untuk siswa, guru, dan pengepul.</p>

                <ol class="steps">
                    <li>Setor sampah ke petugas</li>
                    <li>Saldo bertambah sesuai harga per kg</li>
                    <li>Ajukan penarikan, admin yang menyetujui</li>
                </ol>
            </div>

            <small>&copy; <?= date("Y") ?> Bank Sampah Sukron</small>
        </section>

        <!-- Panel kanan: formulir masuk -->
        <section class="login-form-area">
            <h2>Masuk</h2>
            <p class="lead">Masukkan username dan password Anda.</p>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: sukron" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password akun Anda" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>

            <?php if ($users && mysqli_num_rows($users) > 0): ?>
                <div class="quick-accounts">
                    <p class="quick-title">Pilih akun cepat</p>
                    <div class="account-chips">
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <button type="button" class="chip" onclick="fillUser(<?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES) ?>)">
                                <span><?= htmlspecialchars($u["nama"]) ?></span>
                                <span class="chip-role"><?= ucfirst(htmlspecialchars($u["role"])) ?></span>
                            </button>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function fillUser(username) {
            document.getElementById('username').value = username;
            document.getElementById('password').focus();
        }
    </script>
</body>
</html>
