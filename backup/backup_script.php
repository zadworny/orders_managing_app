<?php
//0 0 * * * /usr/bin/wget -qO - https://hydro-dyna.pl/app/backup/backup_script.php >/dev/null 2>&1

require_once 'DatabaseBackup.php';
require_once 'FTPManager.php';

$host = 'localhost';
$username = 'jrqerflhfm_app';
$password = 'Hydrodyna13!';
$database = 'jrqerflhfm_app';

/*$host = 'localhost';
$username = 'admin';
$password = 'admin';
$database = 'hydro_dyna';*/

$tables = ['clients', 'companies', 'orders', 'users'];

// FTP configuration
$ftpHost = '167.172.165.3';
$ftpUsername = 'jrqerflhfm';
$ftpPassword = '@1nf053rw!5';
$ftpPath = '/www/backup/';

$dbBackup = new DatabaseBackup($host, $username, $password, $database, $tables);
$backupFile = $dbBackup->createBackup();

if ($backupFile) {
    $ftpManager = new FTPManager($ftpHost, $ftpUsername, $ftpPassword, $ftpPath);
    $ftpManager->uploadFile($backupFile);
} else {
    echo "Database backup failed.\n";
}
?>