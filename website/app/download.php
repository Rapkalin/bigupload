<?php
ini_set('memory_limit', -1);

// echo "hello";
$fileName = $_GET['fileName'];

$path = "uploads/" . $fileName;
$size = filesize($path);
$fp = fopen($path, "rb");
$content = fread($fp, $size);
fclose($fp);

header("Content-length: ".$size);
header("Content-type: application/octet-stream");
header("Content-disposition: attachment; filename=".$fileName.";" );
echo $content;
