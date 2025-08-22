<?php
$publicPath = __DIR__ . '/public';


if (file_exists($publicPath . '/index.php')) {
    require $publicPath . '/index.php';
    exit;
} else {
    echo "404 PAGE NOT FOUND";
}
