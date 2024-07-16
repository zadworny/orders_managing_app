<?php
class FileLogger {
    protected $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    public function overwriteLog(string $content): void {
        file_put_contents($this->filePath, $content, LOCK_EX);
    }

    public function appendToLog(string $content): void {
        file_put_contents($this->filePath, $content, FILE_APPEND | LOCK_EX);
    }
}

// Initialize the logger with the file name
//$logger = new FileLogger('latestlog.txt');
//$logger->overwriteLog("This will overwrite the existing content.\n");
//$logger->appendToLog("This will add to the existing content.\n");
?>