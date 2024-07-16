<?php
class DataProcessor {
    private $pdo;
    
    public function __construct() {
        $host = 'localhost';
        $db = 'hydro_dyna';
        $user = 'root';
        $pass = 'root';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }
    
    public function processFile($filePath) {
        $file = new SplFileObject($filePath);
        $file->fgets(); // Skip header line (column names)
        while (!$file->eof()) {
            $line = $file->fgets();
            // Skip the first empty element caused by the leading '|'
            $columns = array_map('trim', explode('|', $line));
            array_shift($columns); // Remove the first, empty element due to the leading '|'
            array_shift($columns); // Remove 'Lp'
            array_shift($columns); // Remove 'S'

            // Ensure there are enough columns to process (excluding 'Lp' and 'S')
            if (count($columns) >= 7) {

                // Remove dashes from NIP
                $columns[4] = str_replace('-', '', $columns[4]);

                $addressParsed = $this->parseAddress($columns[5]); // Correct index after shift

                // Prepare data for database insertion
                $dataForDb = [
                    'type' => $columns[0],
                    'kind' => $columns[1],
                    'name_custom' => $columns[2],
                    'name' => $columns[3],
                    'nip' => $columns[4],
                    'street' => $addressParsed['streetName'],
                    'house_no' => $addressParsed['buildingName'],
                    'flat_no' => $addressParsed['flatName'],
                    'town' => $columns[6],
                    'import_db' => $columns[7],
                ];

                $this->insertIntoDatabase($dataForDb);
            }
        }
    }

    private function insertIntoDatabase($data) {
        $sql = "INSERT INTO clients (type, kind, name_custom, name, nip, street, house_no, flat_no, town, import_db) VALUES (:type, :kind, :name_custom, :name, :nip, :street, :house_no, :flat_no, :town, :import_db)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
    }

    private function parseAddress($address) {
        $address = $address ?? ''; // If $address is null, use an empty string instead
        $pattern = '/^(.*?)\s(\d+[a-zA-Z]*)(\/(\d+[a-zA-Z]*))?$/';
        preg_match($pattern, $address, $matches);

        return [
            'streetName' => $matches[1] ?? null,
            'buildingName' => $matches[2] ?? null,
            'flatName' => $matches[4] ?? null,
        ];
    }
}