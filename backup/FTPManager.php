<?php
require_once '../functions/fileLogger.php';

class FTPManager {
    private string $ftpHost;
    private string $ftpUsername;
    private string $ftpPassword;
    private string $ftpPath;
    private FileLogger $successLogger; // Logger for successful uploads
    private FileLogger $errorLogger;    // Logger for errors

    public function __construct(string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath) {
        $this->ftpHost = $ftpHost;
        $this->ftpUsername = $ftpUsername;
        $this->ftpPassword = $ftpPassword;
        $this->ftpPath = $ftpPath;
        $this->successLogger = new FileLogger("latestlog"); // Path to the success log file
        $this->errorLogger = new FileLogger("errorlog");    // Path to the error log file
    }

    public function uploadFile(string $filePath): void {
        $connId = ftp_connect($this->ftpHost);
        $loginResult = ftp_login($connId, $this->ftpUsername, $this->ftpPassword);
    
        // Enable passive mode
        ftp_pasv($connId, true);
    
        // Change FTP_BINARY to FTP_ASCII
        if ($connId && $loginResult && ftp_put($connId, $this->ftpPath . basename($filePath), $filePath, FTP_ASCII)) {
            $this->successLogger->overwriteLog("Ostatni autobackup: ".date('d/m/Y H:i'));
            $this->cleanupBackups($connId);
        } else {
            $this->errorLogger->appendToLog("Upload pliku $filePath nie powiódł się\n");
        }
    
        fclose($logFile); // Close the log file
        ftp_close($connId);
    }        

    private function cleanupBackups($connId): void {
        // Handle FTP files
        $ftpFiles = ftp_nlist($connId, $this->ftpPath);
        if ($ftpFiles === false) {
            $this->errorLogger->appendToLog("Błąd pobrania listy plików FTP {$this->ftpPath}\n");
        } else {
            // Filter out '.' and '..' from the list
            $ftpFiles = array_filter($ftpFiles, fn($file) => !in_array($file, [$this->ftpPath.'.', $this->ftpPath.'..']));
            $this->cleanupFiles($ftpFiles, function($file) use ($connId, $logFile) {
                $result = ftp_delete($connId, $file);
                if ($result) {
                    $this->errorLogger->appendToLog("Usunięcie pliku FTP {$this->ftpPath} nie powiodło się\n");
                }
                return $result;
            });
        }
    
        // Handle Local files
        $localPath = 'sql'; // Adjust to your local backup directory
        $localFiles = scandir($localPath);
        if ($localFiles === false) {
            $this->errorLogger->appendToLog("Błąd pobrania listy plików lokalnie {$this->ftpPath}\n");
        } else {
            // Filter out '.' and '..' from the list
            $localFiles = array_filter($localFiles, fn($file) => !in_array($file, ['.', '..']));
            $this->cleanupFiles($localFiles, function($file) use ($localPath, $logFile) {
                $result = unlink($localPath . '/' . $file);
                if (!$result) {
                    $this->errorLogger->appendToLog("Usunięcie pliku lokalnie {$this->ftpPath} nie powiodło się\n");
                }
                return $result;
            });
        }

        fclose($logFile); // Close the log file
    }
    
    private function cleanupFiles(array $files, callable $deleteFunction): void {
        sort($files);
        $max_files = 30;
        // If more than X backup files exist, proceed with cleanup
        if (count($files) > $max_files) {
            // Keep only the X most recent files, delete the rest
            $filesToDelete = array_slice($files, 0, -$max_files);
    
            foreach ($filesToDelete as $file) {
                if  (!$deleteFunction($file)) {
                    // Logging for failed deletion is handled in the deleteFunction callback
                } 
            }
        }
    }
    
}
