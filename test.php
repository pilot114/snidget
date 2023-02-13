<?php

// self-modified script

$file = './test.php';
$source = file_get_contents($file);
$source .= "\necho 123;";
file_put_contents($file, $source);

echo 123;
echo 123;
echo 123;