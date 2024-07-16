<?php
$str = '             PRZEDSIĘBIORSTWO ROBÓT  DROGOWO-ZIEMNYCH I  TRANSPORTOWYCH             "TOM-TRANS" TOMASZ MUCHA ';
echo '<textarea>'.$str.'</textarea><br><textarea>'.preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', str_replace('"', '', trim($str))).'</textarea>';
?>