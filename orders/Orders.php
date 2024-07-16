<?php
require_once '../database/connect.php';
require_once '../functions/columns.php';

class Orders {
    private PDO $db; // Change the property type to PDO.

    public function __construct() {
        $database = new Database(); // Create an instance of Database class.
        $this->db = $database->getConnection(); // Get the PDO connection from Database class.
    }

    public function fetchOrders() {

        // Include columnsListSpecial
        include '../functions/columns.php';

        $login = $_SESSION['login'];
        $level = $_SESSION['level'];

        // Existing code to fetch all records
        $sql = "SELECT DISTINCT " . $columnsListSpecial . " 
            FROM orders t1 JOIN clients t2 ON t1.clientid = t2.clientid
            WHERE (t1.archived != 'TAK' OR t1.archived IS NULL)
            AND (t1.deleted != 'TAK' OR t1.deleted IS NULL)
            ORDER BY 
                CASE 
                    WHEN t1.status = 'Zlecona naprawa' AND t1.repair_accepted = 'TAK' AND t1.repair_date IS NOT NULL AND t1.repair_date <> '' AND t1.repair_urgent = 'TAK' THEN 1
                    WHEN (t1.status = 'Zlecona naprawa' OR t1.status = 'Zrobić test' OR t1.status = 'Wydać po naprawie') 
                        AND t1.repair_accepted = 'TAK' AND t1.repair_date IS NOT NULL AND t1.repair_date <> '' AND (t1.repair_urgent IS NULL OR t1.repair_urgent = '') THEN 2
                    WHEN t1.status = 'Zlecona weryfikacja' 
                        AND t1.verification_accepted = 'TAK' AND t1.verification_date IS NOT NULL AND t1.verification_date <> '' AND t1.verification_urgent = 'TAK' THEN 3
                    WHEN (t1.status = 'Zlecona weryfikacja' OR t1.status = 'Wydać bez naprawy') 
                        AND t1.verification_accepted = 'TAK' AND t1.verification_date IS NOT NULL AND t1.verification_date <> '' AND (t1.verification_urgent IS NULL OR t1.verification_urgent = '') THEN 4
                    ELSE 5
                END ASC,
                CASE 
                    WHEN t1.status = 'Zlecona naprawa' AND t1.repair_accepted = 'TAK' AND t1.repair_date IS NOT NULL AND t1.repair_date <> '' AND t1.repair_urgent = 'TAK' THEN t1.repair_date
                    WHEN (t1.status = 'Zlecona naprawa' OR t1.status = 'Zrobić test' OR t1.status = 'Wydać po naprawie') 
                        AND t1.repair_accepted = 'TAK' AND t1.repair_date IS NOT NULL AND t1.repair_date <> '' AND (t1.repair_urgent IS NULL OR t1.repair_urgent = '') THEN t1.repair_date
                    WHEN t1.status = 'Zlecona weryfikacja' 
                        AND t1.verification_accepted = 'TAK' AND t1.verification_date IS NOT NULL AND t1.verification_date <> '' AND t1.verification_urgent = 'TAK' THEN t1.verification_date
                    WHEN (t1.status = 'Zlecona weryfikacja' OR t1.status = 'Wydać bez naprawy') 
                        AND t1.verification_accepted = 'TAK' AND t1.verification_date IS NOT NULL AND t1.verification_date <> '' AND (t1.verification_urgent IS NULL OR t1.verification_urgent = '') THEN t1.verification_date
                    ELSE t1.acceptance_date
                END ASC,
                t1.acceptance_date ASC;
            ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $all = $stmt->fetchAll();
        
        // Initialize variables for each status
        $undefined = $receive = $verify = $forappraisal = $deliver = $appraisal = $repair = $test = $after = $finish = $complaint = $forgottenCount = $forgottenCount2 = $forgottenCount3 = [];
        
        $orderOptions = [
            'Przyjęto na magazyn' => 7, 
            'Zlecona weryfikacja' => 3, 
            'Rozebrane do wyceny' => 10, 
            'Wydać bez naprawy' => 5, 
            'Wyceniona naprawa' => 8, 
            'Zlecona naprawa' => 2, 
            'Zrobić test' => 4, 
            'Wydać po naprawie' => 6, 
            'Zakończono' => 9,
            'Reklamacja' => 1,
            'Status' => 10
        ];

        $collectOptions = [
            'Odbiór' => 0,
            'Osobiście' => 1, 
            'Kurier' => 2
        ];

        $paymentOptions = [
            'Płatność' => 0,
            'Zapłacono' => 1,
            'Przelew odroczony' => 2,
            'Brak płatności' => 3
        ];

        // Collect for tile linking
        foreach ($all as $record) {
            $orderid = $record['orderid'];
            $receiptid = $record['receiptid'];
            $orderList[$orderid][] = $receiptid;
        }
        
        foreach ($all as $record) {
            $status = $record['status'];
            $collect = $record['collect_method'];
            $payment = $record['payment'];

            // Get last update
            $updates[] = $record['update_date'];

            // Calc days from update
            preg_match_all('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $record['history'], $matches);
            $updateDate = end($matches[0]); // or just $record['update_date'];
            $currentDate = date('Y-m-d H:i:s');
            $updateDateTime = new DateTime($updateDate);
            $currentDateTime = new DateTime($currentDate);
            $interval = $currentDateTime->diff($updateDateTime);
            $daysDifference = $interval->days;
            $forgotten = '';
            if ($daysDifference > 6) {
                $forgottenCount[] = 1;
                $forgotten = "<span style=\"position:relative; z-index:1\"><img data-title=\"UWAGA<br>{$daysDifference} dni bez zmian\" class='orderWarning' src='images/warning.png'></span>";
            }
            if ($daysDifference > 13) {
                $forgottenCount2[] = 1;
            }
            if ($daysDifference > 20) {
                $forgottenCount3[] = 1;
            }

            $optionsHTML = '';
            foreach ($orderOptions as $optionText => $optionValue) {
                $selected = $optionText == $status ? 'selected' : '';
                $optionsHTML .= "<option value=\"$optionText\" $selected>$optionText</option>";
            }

            $optionsHTML2 = '';
            foreach ($collectOptions as $optionText => $optionValue) {
                $selected = $optionText == $collect ? 'selected' : '';
                $optionsHTML2 .= "<option value=\"$optionText\" $selected>$optionText</option>";
            }

            $optionsHTML3 = '';
            foreach ($paymentOptions as $optionText => $optionValue) {
                $selected = $optionText == $payment ? 'selected' : '';
                $optionsHTML3 .= "<option value=\"$optionText\" $selected>$optionText</option>";
            }

            if (($status == 'Zlecona weryfikacja' && $record['verification_date'] != '') ||
               ($status == 'Wydać bez naprawy' && $record['verification_date'] != '')) {
                $theDate = $record['verification_date'];
            } else if ($status == 'Zlecona naprawa' && $record['repair_date'] != '') {
                $theDate = $record['repair_date'];
            } else if ($status == 'Zrobić test' && $record['repair_date'] != '') {
                $theDate = $record['repair_date'];
            } else {
                $theDate = $record['insert_date'];
            }
            $class = $checked = $warning = $warning1 = $warning2 = $warning3 = $assemble = '';
            $date = explode(' ', $theDate)[0];
            $statusKey = $orderOptions[$status];
            $interference = $record['interference'];
            $warning1 = $interference != '' ? $record['interference_note'] : '';
            if ($statusKey == 5) {
                if ($record['assemble'] == 'TAK') {
                    $warning2 = 'SKŁADAĆ<br>';
                    $assemble = 'TAK';
                    $assembleChecked = 'checked';
                    $assembleTxt = 'TAK';
                } else {
                    $warning2 = 'NIE SKŁADAĆ<br>';
                    $assemble = 'NIE';
                    $assembleChecked = '';
                    $assembleTxt = 'NIE';
                }
            }

            if ($record['assemble_ready'] == 'TAK') {
                $assembleReadyChecked = 'checked';
            } else {
                $assembleReadyChecked = '';
            }

            if ($status == 'Zlecona weryfikacja' || $status == 'Zlecona naprawa') {
                if ($status == 'Zlecona weryfikacja') {
                    $urgent = $record['verification_urgent'];
                }
                if ($status == 'Zlecona naprawa') {
                    $urgent = $record['repair_urgent'];
                }
                if ($urgent == 'TAK' || $urgent == '1') {
                    $checked = 'checked';
                    $warning3 = "PILNE<br>";
                }

                $dateToCompare = new DateTime($theDate);
                $today = new DateTime(); 

                $diff = $today->diff($dateToCompare)->days;
                $isPast = $today > $dateToCompare;
                
                if ($diff == 0) { // || $diff == 1
                    $class = 'warningSoon';
                } elseif ($isPast) {
                    $class = 'warningPast';
                }
            }
            
            if ($warning2 != '' || $warning3 != '') {
                $warningNote = $warning3 . $warning2;
                $warning = "<span style=\"position:relative; z-index:1\"><img data-title=\"{$warningNote}\" class='orderWarning' src='images/urgent.png'></span>";
            }

            $orderid = $record['orderid'];
            $receiptid = $record['receiptid'];
            $countReceipts = count($orderList[$orderid]);
            if (strpos($orderid, '-') !== false) {
                // OLD NUMBERS
                $exp1 = explode('/', $orderid);
                $exp2 = explode('-', $exp1[1]);
                $min = ltrim($exp2[0], '0');
                $max = ltrim($exp2[1], '0');
                $range = $max - $min;
                
                $exp3 = explode('/', $receiptid);
                $num = $exp3[1];
                $cur = $max - $num;

                $fin = $cur+1 . '/' . $range+1;
                $dataid = "data-id='$orderid'";
                //$class = $class . ' oneOfFewBorder'; // Show green border
            } else if ($countReceipts > 1) {
                // NEW NUMBERS
                /*$letter = substr($receiptid, -1);
                if (ctype_alpha($letter)) {
                    $cur = ord(strtoupper($letter)) - ord('A') + 1;
                    if ($cur > $countReceipts) {
                        $cur = $cur - $countReceipts;
                    }
                    $fin = $cur . '/' . $countReceipts;
                    $dataid = "data-id='$orderid'";
                }*/

                /*foreach ($orderList[$orderid] as $k => $v) {
                    if ($v == $receiptid) {
                        $cur = $k + 1;
                    }
                }*/
                $cur = array_search($receiptid, $orderList[$orderid], true) + 1;
                $fin = $cur . '/' . $countReceipts;
                $dataid = "data-id='$orderid'";
            } else {
                $fin = $tmp = $dataid = '';
            }

            $rid = $record['id'];
            $name = $record['name_custom'] ?: $record['name'];
            if ($name == '') { $name = $record['firstname'] . ' ' . $record['lastname']; }
            $date = date('d-m-Y', strtotime($date));
            
            $clientDetails = '';
            if ($level != 'view') {
                $clientDetails = $record['firstname'] . ' ' . $record['lastname'] . '<br>TEL: ' . $record['phone'] . '<br>' . $record['email'];
            }
            
            $htmlRecord = "<div id=\"boxId-{$rid}\" class=\"order-box status-$statusKey $class\" $dataid>
                $warning
                $forgotten
                <span>ID: {$record['receiptid']}</span>
                <span class=\"oneOfFew\">{$fin}</span>";

                if ($level != 'view') {
                    if ($warning1 == '') {
                        $warning1 = 'Dodaj notatkę';
                    }
                    $htmlRecord .= "<img data-title=\"{$warning1}\" class=\"addNote\" src=\"images/edit.png\" data-id=\"{$rid}\">";
                    if ($warning1 != 'Dodaj notatkę') {
                        //$htmlRecord .= "<img data-title=\"{$warning1}\" class=\"addNote\" src=\"images/tick.png\" data-id=\"{$rid}\">";
                        $htmlRecord .= "<img class=\"addNoteMark\" src=\"images/tick.png\">";
                    }
                }

                $htmlRecord .= "<strong>{$date}</strong>

                <input class=\"verificationDateStart\" type=\"hidden\" value=\"{$record['verification_date_start']}\">
                <input class=\"verificationDateCount\" type=\"hidden\" value=\"{$record['verification_date_count']}\">
                <input class=\"verificationDate\" type=\"hidden\" value=\"{$record['verification_date']}\">
                <input class=\"verificationQuote\" type=\"hidden\" value=\"{$record['verification_quote']}\">

                <input class=\"repairDateStart\" type=\"hidden\" value=\"{$record['repair_date_start']}\">
                <input class=\"repairDateCount\" type=\"hidden\" value=\"{$record['repair_date_count']}\">
                <input class=\"repairDate\" type=\"hidden\" value=\"{$record['repair_date']}\">
                <input class=\"repairQuote\" type=\"hidden\" value=\"{$record['repair_quote']}\">

                <input class=\"repairQuote\" type=\"hidden\" value=\"{$record['repair_quote']}\">
                <input class=\"interferenceNote\" type=\"hidden\" value=\"{$record['interference_note']}\">

                <table>
                    <tr>
                        <td>Podzespół</td>
                        <td class=\"backToList\" data-id=\"{$rid}\" style='font-size:20px'><strong>{$record['part_name']}</strong></td>
                    </tr>
                    <tr style='opacity:1'>
                        <td>Klient</td>
                        <td data-title=\"{$clientDetails}\">{$name}</td>
                    </tr>";

                    if ($status == 'Wydać po naprawie') {
                        $htmlRecord .= "<tr class=\"hideTr\">
                            <td>Gotowe</td>
                            <td>
                                <input id=\"assembleReady{$rid}\" type=\"checkbox\" name=\"assembleReady\" value=\"TAK\" $assembleReadyChecked>
                                <label for=\"assembleReady{$rid}\" class=\"assembleTxt\">GOTOWE</label>
                            </td>
                        </tr>";
                    } else if ($status == 'Wydać bez naprawy') {
                        $htmlRecord .= "<tr class=\"hideTr\">
                            <td>Składać</td>
                            <td>
                                <input id=\"assemble{$rid}\" type=\"checkbox\" name=\"assemble\" value=\"TAK\" $assembleChecked>
                                <label for=\"assemble{$rid}\" class=\"assembleTxt\">{$assembleTxt}</label>
                                
                                <input id=\"assembleReady{$rid}\" type=\"checkbox\" name=\"assembleReady\" value=\"TAK\" $assembleReadyChecked>
                                <label for=\"assembleReady{$rid}\" class=\"assembleTxt\">GOTOWE</label>
                            </td>
                        </tr>";
                    }

                if ($level != 'view') {
                    if ($status == 'Wydać bez naprawy' || $status == 'Wydać po naprawie') {
                        $htmlRecord .= "<tr class=\"hideTr\">
                            <td>Odbiór</td>
                            <td>
                                <select data-id=\"{$rid}\" class=\"status-$statusKey collect\">$optionsHTML2</select>
                            </td>
                        </tr>
                        <tr class=\"hideTr\">
                            <td>Płatność</td>
                            <td>
                                <select data-id=\"{$rid}\" class=\"status-$statusKey payment\">$optionsHTML3</select>
                            </td>
                        </tr>";
                    }
                }

                $htmlRecord .= "</table>"; 
            
                if ($level != 'view') {
                    $htmlRecord .= "<select data-id=\"{$rid}\" class=\"status-$statusKey $class\">$optionsHTML</select>";
                    if ($status == 'Zlecona weryfikacja' || $status == 'Zlecona naprawa') {
                        $htmlRecord .= "<div class=\"urgent\"><label for=\"urgent{$rid}\">Pilne</label> <input id=\"urgent{$rid}\" type=\"checkbox\" name=\"urgent\" value=\"TAK\" $checked></div>";
                    }
                }

            $htmlRecord .= "</div>";
            
            switch ($status) {
                case 'Przyjęto na magazyn':
                    $receive[] = $htmlRecord;
                    break;
                case 'Zlecona weryfikacja':
                    $verify[] = $htmlRecord;
                    break;
                case 'Rozebrane do wyceny':
                    $forappraisal[] = $htmlRecord;
                    break;
                case 'Wydać bez naprawy':
                    $deliver[] = $htmlRecord;
                    break;
                case 'Wyceniona naprawa':
                    $appraisal[] = $htmlRecord;
                    break;
                case 'Zlecona naprawa':
                    $repair[] = $htmlRecord;
                    break;
                case 'Zrobić test':
                    $test[] = $htmlRecord;
                    break;
                case 'Wydać po naprawie':
                    $after[] = $htmlRecord;
                    break;
                case 'Zakończono':
                    $finish[] = $htmlRecord;
                    break;
                case 'Reklamacja':
                    $complaint[] = $htmlRecord;
                    break;
                case 'Status':
                    $undefined[] = $htmlRecord;
                    break;
                default:
                    $undefined[] = $htmlRecord;
                    break;
            }
        }
        rsort($updates);
        $update = $updates[0];
        
        return ['forgotten' => count($forgottenCount), 'forgotten2' => count($forgottenCount2), 'forgotten3' => count($forgottenCount3), 'receive' => $receive, 'verify' => $verify, 'forappraisal' => $forappraisal, 'deliver' => $deliver, 'appraisal' => $appraisal, 'repair' => $repair, 'test' => $test, 'after' => $after, 'complaint' => $complaint, 'finish' => $finish, 'undefined' => $undefined, 'update' => $update, 'login' => $login, 'level' => $level];
    }

    public function updateOrder($id, $status, $user, $date_quote, $date_start, $date_count, $date_end, $urgent, $mark) {
        try {
            if ($mark == 'note') {

                if ($status === '') { $status = null; } else { $status = (string) $status; }

                $sql = "UPDATE orders SET interference = :interference, interference_note = :interference_note WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':interference' => 'TAK', ':interference_note' => $status, ':id' => $id]);

            } else {
                
                $history = date('Y-m-d H:i') . ' ' . $status . ', ' . $user . '<br>';
                $archived = $status == 'Zakończono' ? 'TAK' : '';
                $accepted = 'TAK';
               
                if ($mark == 'checkboxAssemble') { //$status == 'Wydać bez naprawy' && 
                    $type = ", assemble = :ass";
                } else if ($mark == 'checkboxAssembleReady') { //$status == 'Wydać bez naprawy' && 
                    $type = ", assemble_ready = :ass";
                } else if ($status == 'Zlecona weryfikacja' && $mark == 'checkboxUrgent') {
                    $type = ", verification_urgent = :vru";
                } else if ($status == 'Zlecona naprawa' && $mark == 'checkboxUrgent') {
                    $type = ", repair_urgent = :vru";
                } else if ($status == 'Zlecona weryfikacja') {
                    $type = ", verification_quote = :vrdq, verification_date_start = :vrds, verification_date_count = :vrdc, verification_date = :vrde, verification_accepted = :vra, verification_urgent = :vru";
                } else if ($status == 'Zlecona naprawa') {
                    $type = ", repair_quote = :vrdq, repair_date_start = :vrds, repair_date_count = :vrdc, repair_date = :vrde, repair_accepted = :vra, repair_urgent = :vru";
                } else if ($status == 'Wydać bez naprawy') {
                    $accepted = '';
                    $type = ", repair_quote = :vrdq, repair_date_start = :vrds, repair_date_count = :vrdc, repair_date = :vrde, repair_accepted = :vra, repair_urgent = :vru";
                } else {
                    $type = "";
                }
    
                // Prepare for the database
                if ($date_quote === '') { $date_quote = null; } else { $date_quote = (string) $date_quote; }
                if ($date_start === '') { $date_start = null; } else { $date_start = (string) $date_start; }
                if ($date_count === '') { $date_count = null; } else { $date_count = (string) $date_count; }
                if ($date_end === '') { $date_end = null; } else { $date_end = (string) $date_end; }
                if ($urgent === '') { $urgent = null; } else { $urgent = (string) $urgent; }
        
                $sql = "UPDATE orders SET status = :status, user = :user, archived = :archived, history = CONCAT_WS('', history, :history) " . $type . " WHERE id = :id";
                $stmt = $this->db->prepare($sql);
    
                if ($mark == 'checkboxAssemble' || $mark == 'checkboxAssembleReady') { //$status == 'Wydać bez naprawy' && 
                    $stmt->execute([':status' => $status, ':user' => $user, ':archived' => $archived, ':history' => $history, ':ass' => $urgent, ':id' => $id]);
                } else if (($status == 'Zlecona weryfikacja' || $status == 'Zlecona naprawa') && $mark == 'checkboxUrgent') {
                    $stmt->execute([':status' => $status, ':user' => $user, ':archived' => $archived, ':history' => $history, ':vru' => $urgent, ':id' => $id]);
                } else if ($status == 'Zlecona weryfikacja' || $status == 'Zlecona naprawa') {
                    $stmt->execute([':status' => $status, ':user' => $user, ':archived' => $archived, ':history' => $history, ':vrdq' => $date_quote, ':vrds' => $date_start, ':vrdc' => $date_count, ':vrde' => $date_end, ':vra' => $accepted, ':vru' => $urgent, ':id' => $id]);
                } else if ($status == 'Wydać bez naprawy') {
                    $stmt->execute([':status' => $status, ':user' => $user, ':archived' => $archived, ':history' => $history, ':vrdq' => $date_quote, ':vrds' => $date_start, ':vrdc' => $date_count, ':vrde' => $date_end, ':vra' => $accepted, ':vru' => $urgent, ':id' => $id]);
                } else {
                    $stmt->execute([':status' => $status, ':user' => $user, ':archived' => $archived, ':history' => $history, ':id' => $id]);
                }
            }
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Handle error, log it, and return an error message
            //error_log('Update Order Failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function updateCollect($id, $method, $type) {
        $column = ($type == 'collect') ? 'collect_method' : 'payment';
        try {
            $sql = "UPDATE orders SET " . $column . " = :collect_method WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            $stmt->execute([':collect_method' => $method, ':id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Handle error, log it, and return an error message
            //error_log('Update Order Failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function console_log($data) {
        echo '<script>';
        echo 'console.log('. json_encode($data) .')';
        echo '</script>';
    }
}
?>
