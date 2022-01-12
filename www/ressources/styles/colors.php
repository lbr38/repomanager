<?php
// Variables CSS //

header("Content-type: text/css; charset: UTF-8");

define('ROOT', dirname(__FILE__, 3));

$ini_array = parse_ini_file(ROOT."/configurations/display.ini");
define('ALTERNATIVE_COLOR1', $ini_array['alternativeColor1']);
define('ALTERNATIVE_COLOR2', $ini_array['alternativeColor2']);
?>

table.list-repos tr.color1 td, table.list-repos tr.color1 .td-desc input[type=text] { 
    color: <?php echo ALTERNATIVE_COLOR1; ?> !important;
}

table.list-repos tr.color2 td, table.list-repos tr.color2 .td-desc input[type=text] { 
    color: <?php echo ALTERNATIVE_COLOR2; ?> !important;
}