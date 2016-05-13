<?php
require_once('../../../config.php');
require_once('../html2pdf/vendor/autoload.php');

//Get back the contents of the page by the seesion
session_start();
$test = $_SESSION['versionid'];

//Creation of the pdf
$pdf = new HTML2PDF('P', 'LETTER', 'fr');
$pdf->setTestIsImage(false); //If true then impossible to create a pdf with images, problem of link of image impossible to load
$pdf->writeHTML($test);
$pdf->Output('courspdf.pdf');
