<?php
$db_url = getenv('DATABASE_URL');
$conn = pg_connect($db_url);

// FUNGSI PENERJEMAH (Wrapper)
function mysqli_query($conn, $query) {
    return pg_query($conn, $query);
}

function mysqli_fetch_assoc($result) {
    return pg_fetch_assoc($result);
}

function mysqli_num_rows($result) {
    return pg_num_rows($result);
}

function mysqli_real_escape_string($conn, $str) {
    return pg_escape_string($conn, $str);
}

function mysqli_error($conn) {
    return pg_last_error($conn);
}
?>
