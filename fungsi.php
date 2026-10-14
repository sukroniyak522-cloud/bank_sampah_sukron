<?php
/** Query prepared singkat. SELECT -> array baris, selain itu -> jumlah baris terpengaruh */
function q($k, $sql, $t = "", ...$p)
{
    $s = mysqli_prepare($k, $sql);
    if ($t !== "") mysqli_stmt_bind_param($s, $t, ...$p);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $out = $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);
    return $out;
}
/** Simpan pesan lalu pindah halaman */
function kembali($tipe, $pesan, $url = "index.php")
{
    set_flash($tipe, $pesan);
    header("Location: " . $url);
    exit();
}
/** Wajib POST + CSRF valid */
function wajib_post_aman()
{
    if (!cek_csrf()) kembali("error", "Sesi tidak valid, muat ulang halaman.");
}
