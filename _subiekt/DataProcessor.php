<?php
class DataProcessor {
    private $pdo;
    
    public function __construct() {
        $host = 'localhost';
        $db = 'hydro_dyna';
        $user = 'root';
        $pass = 'root';
        //$host = 'localhost';
        //$db = 'jrqerflhfm_app';
        //$user = 'jrqerflhfm_app';
        //$pass = 'Hydrodyna13!';
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
            $columns = array_map('trim', explode(';', $line));
            //array_shift($columns); // Remove the first, empty element due to the leading '|'
            //array_shift($columns); // Remove 'Lp'
            //array_shift($columns); // Remove 'S'

            // Ensure there are enough columns to process (excluding 'Lp' and 'S')
            if (count($columns) >= 6) {

                // Remove dashes and spaces from NIP
                $columns[2] = str_replace(array('-', ' '), '', trim($columns[2]));
                // Remove empty spaces and quotes from name
                $columns[0] = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', str_replace('"', ' ', trim($columns[0])));
                $columns[1] = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', str_replace('"', ' ', trim($columns[1])));

                $addressParsed = $this->parseAddress($columns[3]); // Correct index after shift

                // Prepare data for database insertion
                $dataForDb = [
                    'name_custom' => trim($columns[0]),
                    'name' => trim($columns[1]),
                    'street' => $addressParsed['streetName'],
                    'house_no' => $addressParsed['buildingName'],
                    'flat_no' => $addressParsed['flatName'],
                    'postcode' => trim($columns[4]),
                    'town' => trim($columns[5]),
                    'nip' => trim($columns[2]),
                    'country' => trim($columns[6]),
                    'import_db' => 'hydro-dyna' //trim($columns[6])
                ];

                /*
                id
                date
                clientid
                firstname
                lastname
                phone
                phone_additional
                email
                name_custom
                * name
                * nip
                * street
                * house_no
                * flat_no
                * postcode
                * town
                * country
                * import_db
                note
                deleted
                */

                $this->insertIntoDatabase($dataForDb);
            }
        }
    }

    private function insertIntoDatabase($data) {
        $sql = "INSERT INTO clients (name_custom, name, street, house_no, flat_no, postcode, town, nip, country, import_db) VALUES (:name_custom, :name, :street, :house_no, :flat_no, :postcode, :town, :nip, :country, :import_db)";
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