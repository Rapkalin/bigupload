<?php

// echo "hello";
$fileName = "uploads/van-cleef-arpels.mp4";

$path = $fileName;
$size = filesize($path);
$fp = fopen($path, "rb");
$content = fread($fp, $size);
fclose($fp);

header("Content-length: ".$size);
header("Content-type: application/octet-stream");
header("Content-disposition: attachment; filename=".$fileName.";" );
echo $content;
