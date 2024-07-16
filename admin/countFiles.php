<?php
if (isset($_POST['filePaths']) && is_array($_POST['filePaths'])) {
    $filePaths = $_POST['filePaths'];
    $existingFileCount = 0;
    foreach ($filePaths as $file) {
        if (file_exists($file)) {
            $existingFileCount++;
        }
    }
    echo $existingFileCount;
}
