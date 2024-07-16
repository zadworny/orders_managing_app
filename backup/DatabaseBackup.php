<?php
class DatabaseBackup {
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private array $tables;

    public function __construct(string $host, string $username, string $password, string $database, array $tables) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->tables = $tables;
    }

    public function createBackup(): string {
        $backupFile = 'sql/' . date('Ymd_His') . '.sql';
        $command = sprintf(
            '/usr/bin/mysqldump --opt -h%s -u%s -p%s %s %s > %s 2>&1',
            //'/Applications/MAMP/Library/bin/mysqldump --opt -h%s -u%s -p%s %s %s', // > %s 2>&1
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            implode(' ', $this->tables),
            $backupFile
        );
        
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMessage = "Backup failed with error code $returnVar";
            if (!empty($output)) {
                $errorMessage .= ": " . implode("\n", $output);
            }
            throw new Exception($errorMessage);
        }

        if (!file_exists($backupFile) || !filesize($backupFile)) {
            throw new Exception("Backup file creation failed or file is empty.");
        }

        return $backupFile;
    }
}
