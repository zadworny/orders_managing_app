<?php
require_once 'FileUploader.php';
require_once 'DataProcessor.php';

$uploader = new FileUploader();
$processor = new DataProcessor();

try {
    $filePath = $uploader->upload($_FILES['fileToUpload']);
    $processor->processFile($filePath);
    echo "Data transfer to MySQL database is complete.";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
