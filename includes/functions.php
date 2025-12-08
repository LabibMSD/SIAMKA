<?php
function redirect($url) {
    header("Location: " . $url);
    exit;
}



function alert($message, $type = 'success') {
    echo "<div class='alert alert-{$type}'>{$message}</div>";
}

/**
 * Membersihkan input agar aman dari XSS & karakter berbahaya
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
