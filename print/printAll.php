<?php
require_once('../../../config.php');

$html = "<link rel = \"stylesheet\" type = \"text/css\" href = \"css/stylePrint.css\">";
$html .= "<button href = \"#\" onclick=\"window.print();return false\">Imprimer</button>";
session_start();
echo $html.$_SESSION['allpages'];