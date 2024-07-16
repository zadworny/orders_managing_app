<?php
require_once '../database/connect.php';
require_once '../login/user.php';
require_once '../functions/columns.php';

// Initialize the Database connection
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$table = $_POST['dbTable'];
$deleted = $_POST['deleted'];
$archived = $_POST['archived'];

$login = $_SESSION['login'];
$level = $_SESSION['level'];
$id = $user->userId($login); // individual checkboxes set for each user

$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename ORDER BY ORDINAL_POSITION";
$stmt = $db->prepare($query);
$stmt->execute([':dbname' => 'jrqerflhfm_app', ':tablename' => $table]); //jrqerflhfm_app hydro_dyna
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$query = "SELECT checkbox_" . $table . " FROM users WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id]);
$checkboxes = $stmt->fetch(PDO::FETCH_ASSOC);
$checkboxes = implode($checkboxes);
if ($deleted == '1') {
    //$checkboxes = substr($checkboxes, 0, -1) . '1';
    $checkboxes = $checkboxes . '1';
}

// Ignore selected columns
if ($_SESSION['level'] !== 'admin' && $table == 'orders') { // editor / moderator
    $valuesToRemove = ['verification_quote', 'repair_quote'];
    $columns = array_filter($columns, function($value) use ($valuesToRemove) {
        return !in_array($value, $valuesToRemove, true);
    }, ARRAY_FILTER_USE_BOTH);
} else if ($table == 'users') {
    $valuesToRemove = ['password', 'password_reset_token', 'checkbox_clients', 'checkbox_orders', 'checkbox_users', 'checkbox_companies'];
    $columns = array_filter($columns, function($value) use ($valuesToRemove) {
        return !in_array($value, $valuesToRemove, true);
    }, ARRAY_FILTER_USE_BOTH);
}
if ($deleted == '0') {
    array_pop($columns); // Don't list 'deleted' column
}

/*try {
    if ($archived == '1' && $deleted == '1') {
        $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE (archived = :archived) ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':archived' => 'TAK']);
    } else if ($deleted == '1') {
        $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE (archived != :archived OR archived IS NULL OR archived = '') ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':archived' => 'TAK']);
    } else if ($archived == '1') {
        $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE (archived = :archived) AND (deleted != :deleted OR deleted IS NULL OR deleted = '') ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':archived' => 'TAK', ':deleted' => 'TAK']);
    } else {
        $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE (archived != :archived OR archived IS NULL OR archived = '') AND (deleted != :deleted OR deleted IS NULL OR deleted = '') ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':archived' => 'TAK', ':deleted' => 'TAK']);
        //$rowCount = $stmt->rowCount();
    }
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage(); exit();
}*/

if ($table == 'orders') {
    $columnsList = $columnsListSpecial;

    if ($deleted != '1') {
        //$showDeleted = "AND (deleted != 'TAK' OR deleted IS NULL)";
        $showDeleted = " WHERE (t1.deleted != 'TAK' OR t1.deleted IS NULL)";
    } else {
        //$showDeleted = "AND deleted = 'TAK'";
        $showDeleted = " WHERE t1.deleted = 'TAK'";
    }

    if (substr($checkboxes, 0, 1)) {
        $sortByDate = "ORDER BY 
          CASE
            WHEN t1.status = 'Reklamacja' THEN 1
            WHEN (t1.status = 'Zlecona naprawa' AND (t1.repair_urgent IS NULL OR t1.repair_urgent = '')) OR (t1.status = 'Zlecona weryfikacja' AND (t1.verification_urgent IS NULL OR t1.verification_urgent = '')) THEN 2
            WHEN t1.status IN ('Zlecona naprawa', 'Zlecona weryfikacja', 'Zrobić test') THEN 3
            WHEN t1.status IN ('Wydać po naprawie', 'Wydać bez naprawy', 'Przyjęto na magazyn', 'Wyceniona naprawa') THEN 4
            ELSE 5
          END,
          CASE
            WHEN t1.status = 'Reklamacja' THEN t1.repair_date
            WHEN t1.status = 'Zlecona naprawa' AND t1.repair_accepted = 'TAK' AND t1.repair_date IS NOT NULL AND t1.repair_date <> '' THEN t1.repair_date
            WHEN t1.status = 'Zlecona weryfikacja' AND t1.verification_accepted = 'TAK' AND t1.verification_date IS NOT NULL AND t1.verification_date <> '' THEN t1.verification_date
            ELSE t1.acceptance_date
          END ASC,
        t1.acceptance_date ASC";
    } else {
        $sortByDate = ' ORDER BY t1.id DESC';
    }
    
    $query = "SELECT DISTINCT " . $columnsList . " FROM " . $table . " t1 JOIN clients t2 ON t1.clientid = t2.clientid " . $showDeleted . $sortByDate;
} else if ($deleted == '1') {
    $columnsList = implode(', ', $columns);
    $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE deleted = 'TAK' ORDER BY id DESC";
} else {
    $columnsList = implode(', ', $columns);
    $query = "SELECT " . $columnsList . " FROM " . $table . " WHERE deleted IS NULL ORDER BY id DESC";
}

$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create DEFAULT clientid, receiptid, clientorders
$query = "SELECT MAX(clientid) AS last_clientid FROM clients";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$clientid = $result['last_clientid'] + 1;
// In case there's no clientid yet for any of the clients
if ($clientid === 1) {
    $clientid = '12345'; // The very first clientid
}
$orderid = $clientid . '/001';
$receiptid = $clientid . '/001A';

// Fix
//if (count($records) == 0) {
    //[{"id":"1","insert_date":"2024-04-06 20:30:26","update_date":"2024-04-22 19:43:56","acceptance_date":"2024-04-06","nip":"6443508640","individual":null,"name":"POMPY CIEP\u0141A DRAGON Justyna Kuriata","firstname":"Justyna","lastname":"Kuriata","phone":"123456789","email":"sssss","clientid":"12345","client_orders":"0","send_sms":"TAK","send_sms_note":"test :)","part_name":"Pompeczka hydrauliczna do Bagger 293","attachments":"1.jpg,2.jpg","attachments_path":"08640","interference":"","interference_note":"Kto\u015b si\u0119 nie ba\u0142 i tu grzeba\u0142 a przy okazji wa\u0142 nap\u0119dowy zjeba\u0142. Trzeba naprawi\u0107.","receiptid":"12345\/002","orderid":"12345\/002-004","delivery_method":"Kurier","status":"Zlecona weryfikacja","order_method":"SMS","assemble":null,"verification_date_start":"2024-04-11","verification_date_count":null,"verification_date":null,"verification_quote":null,"verification_accepted":null,"verification_urgent":"TAK","repair_date_start":"2024-04-11","repair_date_count":null,"repair_date":null,"repair_quote":null,"repair_accepted":null,"repair_urgent":"TAK","note":"Tu idzie notatka og\u00f3lna i mo\u017cna pisa\u0107 cokolwiek - kr\u00f3cej lub d\u0142u\u017cej bla bla bla","vatid":"2024\/05\/12","history":"2024-04-22 02:32 Zlecona weryfikacja, <br>2024-04-22 02:32 Przyj\u0119to na magazyn, <br>2024-04-22 02:32 Zlecona naprawa, <br>2024-04-22 02:32 Zrobi\u0107 test, <br>2024-04-22 02:56 Przyj\u0119to na magazyn, prezes<br>2024-04-22 03:06 Zlecona weryfikacja, prezes<br>","company":"Hydro-Dyna","user":"prezes","archived":"","deleted":null}]
//}

// Setting header to JSON for AJAX response
header('Content-Type: application/json');
echo json_encode(['checkboxes' => $checkboxes, 'records' => $records, 'clientid' => $clientid, 'orderid' => $orderid, 'receiptid' => $receiptid, 'clientorders' => 0, 'login' => $login, 'level' => $level]);
