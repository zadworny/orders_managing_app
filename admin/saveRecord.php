<?php
error_reporting(E_ALL);
require_once '../database/connect.php';
require_once '../functions/smsPlanet.php'; // For smsPlanet
require_once 'imageResizer.php';

$database = new Database();
$db = $database->getConnection();

// Determine if this is an update or insert
$isUpdate = isset($_POST['id']) && !empty($_POST['id']);
$isUpdateAdd = $isUpdate && isset($_POST['part_name']) && is_array($_POST['part_name']) && count($_POST['part_name']) > 1;

$table = $_POST['dbTable'];
$sp_sent = 0; // Holder for smsPlanet
$confirm = '';

$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename ORDER BY ORDINAL_POSITION";
$stmt = $db->prepare($query);
$stmt->execute([':dbname' => 'jrqerflhfm_app', ':tablename' => $table]); //jrqerflhfm_app
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
//array_shift($columns); // Remove id

$valuesToRemove = ['date', 'update_date', 'insert_date', 'deleted', 'password_reset_token', 'password_original']; //'password', 'checkbox_clients', 'checkbox_orders', 'checkbox_users', 'checkbox_companies'
$columns = array_filter($columns, function($value) use ($valuesToRemove) {
    return !in_array($value, $valuesToRemove);
});

$arr = $params = array();
foreach ($columns as $c) {
    $arr[$c] = $c . ' = :' . $c;
    $value = ($_POST[$c] === '') ? null : $_POST[$c]; // Insert NULL instead of empty

    // PASSWORD: if new OR if edit but changed
    if ((!$isUpdate && $c == 'password') || ($isUpdate && $_POST[$c] !== $_POST['password_original'] && $c == 'password')) {
        $value = password_hash($_POST[$c], PASSWORD_DEFAULT); 
    }

    $params[':' . $c] = $value; //$_POST[$c];
}
unset($arr['dbTable']);
unset($params['dbTable']);
unset($columns['dbTable']); // Remove dbTable as it's used only once

// GUS
$gusSuccess = 0;
if (isset($_POST['gusData']) && $_POST['gusData'] != '') {
    $gus = json_decode($_POST['gusData'], true);
    $gusSuccess = $gus['success'];
    if ($gusSuccess !== false) {
        $gusSuccess = 1;
        $gusClient = $gus['clientExistsMark'];
        $gvaluesToRemove = ['success', 'orderid', 'receiptid', 'client_orders', 'province', 'since', 'clientExists', 'clientExistsMark', 'branch'];
        foreach ($gus as $k => $c) {
            if (!in_array($k, $gvaluesToRemove)) {
                $newgus[$k] = $c;
                $garr[$k] = $k . ' = :' . $k;
                $gvalue = ($gus[$k] === '') ? null : $gus[$k]; // Insert NULL instead of empty
                $gparams[':' . $k] = $gvalue;
            }
        }
        
        //unset($garr['nip']);
        //unset($garr['firstname']);
        //unset($garr['lastname']);
        unset($garr['clientid']);

        $kgus = array_keys($newgus);
        $gset = implode(', ', $garr);
        $gkeys = implode(', ', $kgus);
        $gvals = ':' . implode(', :', $kgus);

        // CLIENT DB: Client exists but firstname, lastname, name_custom changed = add NEW record
        // Note: need to check ALL name_custom records for this NIP in clients db
        if ($gusClient == 1 && ($gparams[':firstname'] != $_POST['firstname'] || $gparams[':lastname'] != $_POST['lastname'])) {
            $gusClient = 0;
        }
    }
}

if ($isUpdate) {
    $outputId = $_POST['id'];
    $params[':id'] = $_POST['id'];
    // Remove id and date types of columns from UPDATE
    unset($arr['id']); 
    unset($arr['date']); 
    unset($arr['update_date']); 
    unset($arr['insert_date']); 
    unset($params[':date']); 
    unset($params[':update_date']); 
    unset($params[':insert_date']);
    $set = implode(', ', $arr);
    $sp_sent = 1;
} 
if (!$isUpdate || $isUpdateAdd) { // add NEW || add MORE to receipts to existing order
    $outputId = '';
    // Remove id and date types of columns from INSERT
    $valuesToRemove = ['id', 'date', 'update_date', 'insert_date']; //'checkbox_clients', 'checkbox_orders', 'checkbox_users', 'checkbox_companies'
    foreach ($valuesToRemove as $vtr) {
        while (($key = array_search($vtr, $columns)) !== false) {
            unset($columns[$key]);
            unset($params[':' . $key]);
        }
    }
    unset($params[':id']); // Make sure id is removed
    $keys = implode(', ', $columns);
    $vals = ':' . implode(', :', $columns);

    if ($table == 'orders') {
        // smsPlanet
        $sp_ok = $params[':send_sms'];
        $sp_to = $_POST['phone']; //$params[':phone'];
        $sp_to_add = $_POST['phone_additional']; //$params[':phone_additional'];
        $sp_msg = $params[':send_sms_note'];

        if (($sp_ok == 'TAK' || $sp_ok == '1') && $sp_msg != '') { // && $sp_to != '' && strlen($sp_to) > 7
            $sp_from = $params[':company'] ?? $_COOKIE['company'] ?? 'Hydro-dyna';

            if ($sp_to_add != '') {
                /*** IMPORTANT: vendor/smsplanet/smsplanet-php-client/src/Client.php : comment out all 3 "throw new" lines! ***/
                $message_id = $client->sendSimpleSMS([
                    'from' => $sp_from,
                    'to' => $sp_to_add,
                    'msg' => $sp_msg,
                ]);
                $sp_sent = $message_id ? 1 : 0;
                $confirm = $sp_to_add;
            }
            // If not sent then try send to additional phone number
            if ($sp_sent == 0 && $sp_to != '') {
                $message_id = $client->sendSimpleSMS([
                    'from' => $sp_from,
                    'to' => $sp_to,
                    'msg' => $sp_msg,
                ]);
                $sp_sent = $message_id ? 1 : 0;
                $confirm = $sp_to;
            }
        } else {
            $sp_sent = 1;
        }

        // Don't save SMS content if set not to send
        if ($params[':send_sms'] != 'TAK') {
            $params[':send_sms_note'] = '';
        }
    }
}



/*
SCENARIOS:
- V add new with NIP
- add new INDIVIDUAL
- update existing NIP
- update existing INDIVIDUAL
* UPDATE if name_custom, firstname, lastname is unchanged

$gvaluesToRemove = ['success', 'receiptid', 'client_orders', 'province', 'since', 'clientExists', 'clientExistsMark', 'branch'];
[{
       "success":true,
    "firstname":"Micha\u0142",
    "lastname":"Wikowicz",
    "phone":"601385010",
    "email":"email...",
    "nip":"8952244185",
    "clientid":"12368",
       "receiptid":"12368\/001A",
       "client_orders":0,
    "name_custom":"",
    "name":"WIKOWICZ SP\u00d3\u0141KA KOMANDYTOWA",
    "street":"ul. Sulmierzycka",
    "house_no":"13","flat_no":"",
    "postcode":"51-127",
    "town":"Wroc\u0142aw",
       "province":"Dolno\u015bl\u0105skie",
    "country":"Polska",
       "since":"2022-07-12",
    "import_db":"GUS",
       "clientExists":"Klient <strong style=\"color:#6C0\">ISTNIEJE<\/strong> w firmowej bazie",
       "clientExistsMark":1,
       "branch":[4336]
}]
*/

if ($table == 'orders') {
    $gparams[':name'] = $_POST['name']; //$params[':name_custom'];
    $gparams[':name_custom'] = $_POST['name_custom']; //$params[':name_custom'];
    $gparams[':firstname'] = $_POST['firstname']; //$params[':firstname'];
    $gparams[':lastname'] = $_POST['lastname']; //$params[':lastname'];
    $gparams[':phone'] = $_POST['phone']; //$params[':phone'];
    $gparams[':phone_additional'] = $_POST['phone_additional']; //$params[':phone_additional'];
    $gparams[':email'] = $_POST['email']; //$params[':email'];
    
    // CLIENT: GUS not fetched (individual client) and client doesn't exist in clients db
    $individualClient = 0;
    if ($gusSuccess == 0) {
        $individualClient = 1;
        
        //$gparams[':receiptid'] = $params[':clientid'] . '/001A';
        //$gparams[':client_orders'] = '0';
        //$gparams[':province'] = ''; // ignore
        //$gparams[':since'] = ''; // ignore
        
        $gparams[':nip'] = '';
        $gparams[':individual'] = 'TAK';
        
        $gparams[':clientid'] = $params[':clientid'];
        //$gparams[':name'] = $params[':name'];
        $gparams[':import_db'] = 'Ręcznie';
        
        $gparams[':name'] =  ($_POST['name'] === '') ? null : $_POST['name']; //$params[':name'];
        $gparams[':street'] = ($_POST['street'] === '') ? null : $_POST['street']; //$params[':street'];
        $gparams[':house_no'] = ($_POST['house_no'] === '') ? null : $_POST['house_no']; //$params[':house_no'];
        $gparams[':flat_no'] = ($_POST['flat_no'] === '') ? null : $_POST['flat_no']; //$params[':flat_no'];
        $gparams[':postcode'] = ($_POST['postcode'] === '') ? null : $_POST['postcode']; //$params[':postcode'];
        $gparams[':town'] = ($_POST['town'] === '') ? null : $_POST['town']; //$params[':town'];
        $gparams[':country'] = ($_POST['country'] === '') ? null : $_POST['country']; //$params[':country'];
    
        foreach ($gparams as $k => $c) {
            $k = substr($k, 1); // Remove ":"
            $newgus[$k] = $c;
            $garr[$k] = $k . ' = :' . $k;
        }

        // NEW: BETA
        //unset($garr['nip']);
        //unset($garr['firstname']);
        //unset($garr['lastname']);
        unset($garr['clientid']);

        $kgus = array_keys($newgus);
        $gset = implode(', ', $garr);
        $gkeys = implode(', ', $kgus);
        $gvals = ':' . implode(', :', $kgus);
    
        $gusSuccess = 1;

        // Add or update individual client
        if ($_POST['client_exists_mark'] != '1') {
            $gusClient = 0;
        } else {
            $gusClient = 1;
        }
    }
}

// file upload
function attachments($postname, $receiptId, $existing) {
    if (isset($_POST[$postname])) {
        $targetDir = "../temp/";
        $receiptDir = "../uploads/" . $receiptId . "/";
        $uploadOk = 1;
        $message = "";
        $fileNames = []; // Declare
    
        // Count existing files
        $existing_count = preg_match('/([^,]+)\.[A-Za-z]{3,4}$/', $existing, $matches) ? $matches[1] : 0;
        $fileCount = $existing_count + 1;
        
        // Process each file
        if (isset($_FILES[$postname]['name']) && is_array($_FILES[$postname]['name']) && count($_FILES[$postname]['name']) > 0) {
    
            if (!file_exists($receiptDir)) { mkdir($receiptDir, 0777, true); }
    
            foreach ($_FILES[$postname]['name'] as $i => $name) {
                if ($_FILES[$postname]['error'][$i] == UPLOAD_ERR_OK && $_FILES[$postname]['size'][$i] <= 5000000) {
                    $fileName = $fileCount . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION)); // Use file count for file name
                    $fileNames[] = $fileName; // Collect names for database table
                    $targetFile = $targetDir . basename($name);
                    $processedFile = $receiptDir . $fileName; // Save processed file in receipt directory
    
                    if (move_uploaded_file($_FILES[$postname]["tmp_name"][$i], $targetFile)) {
                        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                        switch ($fileType) {
                            case 'jpeg':
                            case 'jpg':
                                $imageType = IMAGETYPE_JPEG;
                                break;
                            case 'gif':
                                $imageType = IMAGETYPE_GIF;
                                break;
                            case 'png':
                                $imageType = IMAGETYPE_PNG;
                                break;
                            default:
                                //throw new Exception("Unsupported file type: $fileType");
                                $message .= "File {$fileName} is not a valid image format. ";
                        }
                        $compression = ($imageType === 'png') ? 9 : 75; // PNG uses a scale of 0-9
                        $resizer = new ImageResizer();
                        $resizer->load($targetFile);
                        $resizer->resizeToLongestSide(1280);
                        $resizer->save($processedFile, $imageType, $compression);
        
                        // Delete the original file
                        if (!unlink($targetFile)) {
                            $message .= "Failed to delete original file {$fileName}. ";
                        }
        
                        $message .= "Processed and saved {$fileName} in folder {$receiptId}. ";
                        $fileCount++; // Increment file count for the next file name
                    } else {
                        $message .= "Failed to upload {$fileName}. ";
                    }
                } else {
                    $message .= "Error or file too large for {$name}. ";
                }  
            }
            $final = trim($existing . ',' . implode(',', $fileNames), ',');
        } else {
            $final = $existing; // Preserve existing ones
        }
        //$params[':attachments_path'] = $receiptId;

        return $final . '|' . $message . '|' . count($fileNames);
    } else {
        $final = $existing;
        $message = "";
        $fileNames = []; // Declare

        return $final . '|' . $message . '|' . count($fileNames);
    }
}

if ($isUpdate) {
    $query = "UPDATE " . $table . " SET " . $set . " WHERE id = :id";
    $query = str_replace(":history", "CONCAT_WS('', history, :history)", $query); // History
    $stmt_update = $db->prepare($query);
}
if (!$isUpdate || $isUpdateAdd) { // add NEW || add MORE to receipts to existing order
    $query = "INSERT INTO " . $table . " (" . $keys . ") VALUES (" . $vals . ")";
    $stmt_add = $db->prepare($query);
}
$output = '';

if ($table == 'orders' && $sp_sent == 0) {
    $success = false;
} else if ($table == 'orders') {
    $parts = $_POST['part_name'];
    $ver_quote = $_POST['verification_quote'];
    $rep_quote = $_POST['repair_quote'];
    $notes = $_POST['interference_note'];
    $inter = $_POST['interference'];
    $attedit = $_POST['attachments_edit'];
    $order = $_POST['orderid']; // uncommented
    $receipt = $receipt_original = $receipt_new = $_POST['receiptid'];
    $p_index = $_POST['part_name_index'];
    $clientid = $_POST['clientid'];
    $output = [];
    $addMe = 0;

    if ($isUpdateAdd) {
        // Get the latest orderid
        $xquery = "SELECT MAX(receiptid) AS last_receipt FROM orders WHERE clientid = :clientid AND orderid = :orderid AND (deleted = '' OR deleted IS NULL)";
        $xstmt = $db->prepare($xquery);
        $xstmt->execute([':clientid' => $clientid, ':orderid' => $order]);
        $xresult = $xstmt->fetch(PDO::FETCH_ASSOC);
        $receipt_new = $xresult['last_receipt'];
    }

    // History column
    if ($params[':history'] == 1) {
        $params[':history'] = date('Y-m-d H:i') . " " . $params[':status'] . ", " . $_SESSION['login'] . "<br>";
    }
    // Archive column
    if ($params[':archived'] == 'TAK') {
        $params[':status'] = 'Zakończono';
    }

    // Prepare letters
    $lastKey = array_key_last($parts);
    foreach ($parts as $key => $part) {

        // Block commented out in the loop below
        if ($isUpdateAdd) {
            $addMe = 1;
            if ($key == $lastKey) {
                $addMe = 0;
                $receipt = $receipt_original; 
            } else if ($key == 0) {
                $receipt = $receipt_new;
            }
            //highest_receiptid
        } 
        $letter = substr($receipt, -1);
        $nextLetter = chr(ord($letter) + $addMe);

        $receipt = str_replace($letter, $nextLetter, $receipt);
        $receipt_arr[] = $receipt;
        if (!$isUpdate || $isUpdateAdd) {
            $addMe = 1;
        } else {
            $addMe = 0;
        }
    }

    // Don't reverse if update
    if ($isUpdate) {
        $parts_new = $parts;
    } else {
        $parts_new = array_reverse($parts);

        $ver_quote = array_reverse($ver_quote);
        $rep_quote = array_reverse($rep_quote);
        $notes = array_reverse($notes);
        $inter = array_reverse($inter);
    }

    foreach ($parts_new as $key => $part) {
    //foreach ($parts as $key => $part) {
        // ---> START SEQUENTIAL FILE PROCESSING
        $lockFile = '../lockfile.lock';
        $fp = fopen($lockFile, "w+");  

        if (!$fp) {
            error_log("Unable to open lock file for writing.");
            continue; // Skip processing this part if the lock file can't be opened
        }

        try {
            if (flock($fp, LOCK_EX)) { // acquire an exclusive lock
                $params[':part_name'] = $part;
                $params[':verification_quote'] = $ver_quote[$key];
                $params[':repair_quote'] = $rep_quote[$key];
                $params[':interference_note'] = $notes[$key];
                $params[':interference'] = $inter[$key];
                $attachments_edit = $attedit[$key];
        
                // Receiptid (Order ID)
                /*
                if ($isUpdateAdd) {
                    $addMe = 1;
                    if ($key == $lastKey) {
                        $addMe = 0;
                        $receipt = $receipt_original; 
                    } else if ($key == 0) {
                        $receipt = $receipt_new;
                    }
                } 
                $letter = substr($receipt, -1);
                $nextLetter = chr(ord($letter) + $addMe);
                */

                $receipt = $receipt_arr[$key];
                $receiptid = substr($receipt, -4);
                $params[':receiptid'] = $receipt;
                if (!$isUpdate || $isUpdateAdd) {
                    $addMe = 1;
                } else {
                    $addMe = 0;
                }

                // Attachments
                if ($key == 0) { 
                    $postname = 'attachments';
                } else { 
                    $postname = 'attachments_' . $p_index[$key];
                }
        
                // To be FINISHED <------------------------------- only allow 3 types of image files: png, jpg/jpeg, gif
                if (!$isUpdate || $isUpdateAdd) {
                    $params[':attachments_path'] = $clientid . $receiptid;
                }
        
                $expl = explode('|', attachments($postname, $params[':attachments_path'], $attachments_edit));
                $params[':attachments'] = $expl[0];

                // Fix: Convert into more database-friendly version and overwrite the existing $params
                foreach ($params as $key2 => $value) {
                    if ($value === '' || $value === null) {
                        $params_tmp[$key2] = null; // Convert empty strings to null
                    } else {
                        $params_tmp[$key2] = (string) $value; // Ensure all values are strings
                    }
                }
                $params = $params_tmp;

                // TEST
                //echo ':::' . PHP_EOL;
                //echo 'UPDATE: ' . PHP_EOL;
                //echo 'SET: ' . $set . PHP_EOL;
                //echo 'INSERT: ' . PHP_EOL;
                //echo 'KEYS: ' . $keys . PHP_EOL;
                //echo 'VALS: ' . $vals . PHP_EOL;
                //print_r($params);
                
                if (($isUpdate && !$isUpdateAdd) || ($isUpdateAdd && $key == $lastKey)) {
                    //echo $key . ' update' . PHP_EOL;
                    if (!array_key_exists(':id', $params)) {
                        $params[':id'] = $_POST['id'];
                    }
                    $success = $stmt_update->execute($params);
                } else if (!$isUpdate || ($isUpdateAdd && $key < $lastKey)) { // add NEW || add MORE to receipts to existing order
                    //echo $key . ' insert' . PHP_EOL;
                    unset($params[':id']);
                    $success = $stmt_add->execute($params);
                }
                $output[] = $expl[1];

                //$_SESSION['file_index'] = $_SESSION['file_index'] + $expl[2];

                flock($fp, LOCK_UN); // make sure to release the lock
            } else {
                error_log("Unable to acquire lock for part $key.");
            }
        } catch (Exception $e) {
            error_log("An error occurred while processing part $key: " . $e->getMessage());
        } finally {
            fclose($fp);
        }
    }

    // GUS
    // Next: make $gusSuccess == 1 / $gusClient == 0 by default. If inserted and then removed NIP or Name from form then get back to default
    if ($gusSuccess == 1) {
        $gtable = 'clients';
        if ($gusClient == 1 || ($isUpdate && $gusClient == 0)) {
            // Update
            //$gquery = "UPDATE " . $gtable . " SET " . $gset . " WHERE nip = :nip AND firstname = :firstname AND lastname = :lastname";
            $gquery = "UPDATE " . $gtable . " SET " . $gset . " WHERE clientid = :clientid";
            $gstmt = $db->prepare($gquery);
            $gsuccess = $gstmt->execute($gparams);
        /*if ($gusClient == 1 && $individualClient == 1) {
            // Update
            $gquery = "UPDATE " . $gtable . " SET " . $gset . " WHERE name = :name";
            $gstmt = $db->prepare($gquery);
            $gsuccess = $gstmt->execute($gparams);
        } else if ($gusClient == 1 && $individualClient == 0) {
            // Update
            $gquery = "UPDATE " . $gtable . " SET " . $gset . " WHERE nip = :nip";
            $gstmt = $db->prepare($gquery);
            $gsuccess = $gstmt->execute($gparams);*/
        } else if (!$isUpdate && $gusClient == 0) { // Prevent inserting a new client when only editing an order
            // Insert
            $gquery = "INSERT INTO " . $gtable . " (" . $gkeys . ") VALUES (" . $gvals . ")";
            $gstmt = $db->prepare($gquery);
            $gsuccess = $gstmt->execute($gparams);
        }
    }

    //unset($_SESSION['file_index']);
} else {
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            $params_tmp[$key] = null; // Convert empty strings to null
        } else {
            $params_tmp[$key] = (string) $value; // Ensure all values are strings
        }
    }
    $params = $params_tmp;
    if ($isUpdate) {
        $success = $stmt_update->execute($params);
    }
    if (!$isUpdate || $isUpdateAdd) { // add NEW || add MORE to receipts to existing order
        $success = $stmt_add->execute($params);
    }
}

// Setting header to JSON for AJAX response
header('Content-Type: application/json');
// query and gquery are for test only
echo json_encode(['success' => $success, 'sent' => $sp_sent, 'confirm' => $confirm, 'id' => $outputId, 'query' => $query, 'gquery' => $gquery, 'gparams' => $gparams, 'gusSuccess' => $gusSuccess, 'gusClient' => $gusClient, 'isUpdate' => $isUpdate, 'gusData' => $_POST['gusData'], 'gset' => $gset]);