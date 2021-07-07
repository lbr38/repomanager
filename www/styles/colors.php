<?php
// Variables CSS //

header("Content-type: text/css; charset: UTF-8");

$WWW_DIR = dirname(__FILE__, 2);

$ini_array = parse_ini_file("${WWW_DIR}/configurations/display.ini");
$alternativeColor1 = $ini_array['alternativeColor1'];
$alternativeColor2 = $ini_array['alternativeColor2'];
?>

.list-repos tr.color1 td, table.list-repos-archived tr.color1 td { 
    color: <?php echo $alternativeColor1; ?>;
}

.list-repos tr.color2 td, table.list-repos-archived tr.color2 td { 
    color: <?php echo $alternativeColor2; ?>;
}