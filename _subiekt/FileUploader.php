<?php
class FileUploader {
    private $targetDirectory = "uploads/";
    private $allowedFileType = "txt";
    
    public function upload($file) {
        $targetFile = $this->targetDirectory . basename($file["name"]);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        /*if ($fileType != $this->allowedFileType) {
            throw new Exception("Sorry, only TXT files are allowed.");
        }*/
        
        if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
            throw new Exception("Sorry, there was an error uploading your file.");
        }
        
        return $targetFile;
    }
}
?>
