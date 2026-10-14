<?php
/**
 * cek_login.php
 * Dipanggil di awal setiap halaman yang butuh login.
 *
 * Cara pakai (contoh dari dalam folder sampah/ atau transaksi/):
 *   $base = "../";
 *   require $base . "cek_login.php";
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($base)) {
    $base = "";
}

require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi.php";

// --- Wajib login ---
if (!isset($_SESSION["id_user"])) {
    header("Location: " . $base . "login.php");
    exit();
}

$id_user = (int) $_SESSION["id_user"];

// --- Ambil ulang data user dari DB (supaya saldo selalu terbaru) ---
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id_user = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// User sudah dihapus dari DB tapi session masih hidup -> paksa logout
if (!$user) {
    session_unset();
    session_destroy();
    header("Location: " . $base . "login.php");
    exit();
}

$role_user = $user["role"];
$is_admin  = ($role_user === "admin");

// --- Token CSRF untuk form POST (dipakai modul topup & user) ---
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/** Hanya admin yang boleh lanjut. Nasabah dilempar ke dashboard. */
function wajib_admin($is_admin, $base)
{
    if (!$is_admin) {
        set_flash("error", "Halaman itu khusus admin.");
        header("Location: " . $base . "index.php");
        exit();
    }
}

/** Simpan pesan sekali tampil */
function set_flash($tipe, $pesan)
{
    $_SESSION["flash"] = ["tipe" => $tipe, "pesan" => $pesan];
}

/** Ambil + hapus pesan flash */
function ambil_flash()
{
    if (!isset($_SESSION["flash"])) {
        return null;
    }
    $f = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $f;
}

/** Shortcut escape HTML */
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

/** Format rupiah */
function rp($angka)
{
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

/** Input hidden berisi token CSRF, taruh di dalam <form method="POST"> */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION["csrf_token"]) . '">';
}

/** Cek token CSRF dari $_POST */
function cek_csrf()
{
    $t = $_POST["csrf_token"] ?? "";
    return is_string($t) && hash_equals($_SESSION["csrf_token"], $t);
}

/** Ambil daftar role langsung dari kolom ENUM users.role */
function daftar_role($koneksi)
{
    $res = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'role'");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($row && preg_match_all("/'([^']+)'/", $row["Type"], $m)) {
        return $m[1];
    }
    return ["admin", "nasabah"];
}

