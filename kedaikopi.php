<?php
// Router untuk kedaikopi
$file = __DIR__ . '/LP/kedaikopi.html';
if (file_exists($file)) {
    readfile($file);
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
}
?>
