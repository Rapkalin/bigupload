<?php

// echo "hello";

// $file = "uploads/van-cleef-arpels.mp4";

// header('Content-Type: application/octet-stream');
// header('Content-Transfer-Encoding: Binary');
// header('Content-disposition: attachment; filename="' . basename($file) . '"');

    $path = $fileName;
    $size = filesize($path);
    $fp = fopen($path, "rb");
    $content = fread($fp, $size);
    fclose($fp);

    header("Content-length: ".$size);
    header("Content-type: application/octet-stream");
    header("Content-disposition: attachment; filename=".$fileName.";" );
    echo $content;
