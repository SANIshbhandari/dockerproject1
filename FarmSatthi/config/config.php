<?php
function asset($path) {
    return '/'.$path;
}

function redirect($url) {
    header("Location: $url");
    exit;
}
?>
