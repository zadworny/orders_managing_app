<?php
require_once '../database/connect.php';
require_once '../vendor/autoload.php';

// Initialize the Database connection
$database = new Database();
$db = $database->getConnection();

use GusApi\BulkReportTypes;
use GusApi\Exception\InvalidUserKeyException;
use GusApi\Exception\NotFoundException;
use GusApi\GusApi;
use GusApi\ReportTypes;

//$_SERVER['REQUEST_METHOD'] = 'POST'; // TEST
//$_POST['nip'] = '6951002450'; // TEST 9131521804=single 6910207161=multi 7842148876 9241918365
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $gus = new GusApi('fb2155cf18dd431d957c');
    //$gus = new GusApi('abcde12345abcde12345', 'dev');
    
    try {
        $nipToCheck = str_replace('-', '', $_POST['nip']);
        
        $gus->login();
    
        $gusReports = $gus->getByNip($nipToCheck);
    
        foreach ($gusReports as $gusReport) {
            /*
            $fullReport_PERSON = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON);
            $fullReport_CEIDG = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_CEIDG);
            $fullReport_ORGANIZATION = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION);
            */
            //$fullReport_AGRO = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_AGRO);
            //$fullReport_OTHER = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_OTHER);
            //$fullReport_DELETED_BEFORE_20141108 = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_DELETED_BEFORE_20141108);
            //$fullReport_LOCALS = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_LOCALS);
            //$fullReport_LOCAL = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_LOCAL);
            //$fullReport_ACTIVITY = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_ACTIVITY);
            //$fullReport_LOCAL_ACTIVITY = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_LOCAL_ACTIVITY);
            //$fullReport_ORGANIZATION_ACTIVITY = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION_ACTIVITY);
            //$fullReport_ORGANIZATION_LOCALS = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION_LOCALS);
            //$fullReport_ORGANIZATION_LOCAL = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION_LOCAL);
            //$fullReport_ORGANIZATION_LOCAL_ACTIVITY = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION_LOCAL_ACTIVITY);
            //$fullReport_ORGANIZATION_PARTNERS = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION_PARTNERS);
            //$fullReport_UNIT_TYPE = $gus->getFullReport($gusReport, ReportTypes::REPORT_UNIT_TYPE);
            
            /*
            echo '<pre style="margin-bottom:100px">';
                echo 'DEFAULT: '; print_r($gusReport);
                echo '<br>PERSON: '; print_r($fullReport_PERSON);
                echo '<br>PERSON_CEIDG: '; print_r($fullReport_CEIDG);
                echo '<br>ORGANIZATION: '; print_r($fullReport_ORGANIZATION);
                //echo '<br>PERSON_AGRO: '; print_r($fullReport_AGRO);
                //echo '<br>PERSON_OTHER: '; print_r($fullReport_OTHER);
                //echo '<br>PERSON_DELETED_BEFORE_20141108: '; print_r($fullReport_DELETED_BEFORE_20141108);
                //echo '<br>PERSON_LOCALS: '; print_r($fullReport_LOCALS);
                //echo '<br>PERSON_LOCAL: '; print_r($fullReport_LOCAL);
                //echo '<br>ACTIVITY: '; print_r($fullReport_ACTIVITY);
                //echo '<br>LOCAL_ACTIVITY: '; print_r($fullReport_LOCAL_ACTIVITY);
                //echo '<br>ORGANIZATION_ACTIVITY: '; print_r($fullReport_ORGANIZATION_ACTIVITY);
                //echo '<br>ORGANIZATION_LOCALS: '; print_r($fullReport_ORGANIZATION_LOCALS);
                //echo '<br>ORGANIZATION_LOCAL: '; print_r($fullReport_ORGANIZATION_LOCAL);
                //echo '<br>ORGANIZATION_LOCAL_ACTIVITY: '; print_r($fullReport_ORGANIZATION_LOCAL_ACTIVITY);
                //echo '<br>ORGANIZATION_PARTNERS: '; print_r($fullReport_ORGANIZATION_PARTNERS);
                //echo '<br>PERSON_UNIT_TYPE: '; print_r($fullReport_UNIT_TYPE);
            echo '</pre>';
            */
            
            if ($gusReport->getType()=='p') {
                //REPORT_ORGANIZATION
                $fullReport = $gus->getFullReport($gusReport, ReportTypes::REPORT_ORGANIZATION);
                $type = 'praw';
                $firstname = '';
                $lastname = '';
            } else {
                //PERSON / PERSON_CEIDG
                $fullReport = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON_CEIDG);
                $type = 'fiz';
                $fullReport_PERSON = $gus->getFullReport($gusReport, ReportTypes::REPORT_PERSON);
                    $fn1 = mb_convert_case($fullReport_PERSON[0]["fiz_imie1"], MB_CASE_TITLE, "UTF-8");
                    $fn2 = mb_convert_case($fullReport_PERSON[0]["fiz_imie2"], MB_CASE_TITLE, "UTF-8");
                $firstname = trim($fn1.' '.$fn2);
                $lastname = mb_convert_case($fullReport_PERSON[0]["fiz_nazwisko"], MB_CASE_TITLE, "UTF-8");
            }

            //echo '<pre>'; print_r($fullReport); exit(); 

            $name = $gusReport->getName();
            $name_custom = $fullReport[0][$type."_nazwaSkrocona"];
            $phone = $fullReport[0][$type."_numerTelefonu"];
            $country = mb_convert_case($fullReport[0][$type."_adSiedzKraj_Nazwa"], MB_CASE_TITLE, "UTF-8");
            $since = $fullReport[0][$type."_dataPowstania"]; //ADD ???
            $street = $gusReport->getStreet();
            $house_no = $gusReport->getPropertyNumber();
            $flat_no = $gusReport->getApartmentNumber();
            $postcode = $gusReport->getZipCode();
            $town = $gusReport->getCity();
            $post_town = $gusReport->getPostCity();
            $province = mb_convert_case($gusReport->getProvince(), MB_CASE_TITLE, "UTF-8"); //ADD ???
            $import_db = "GUS";
            
            if ($street == '' && $town != '') {
                $street = $town;
                $town = $post_town;
            }
    
            /*
            $show_report = '
                <table>
                    <tr>
                        <td style="color:#BBB; width:100px">
                            firstname:<br>
                            lastname:<br>
                            name_custom:<br>
                            name:<br>
                            phone:<br>
                            street:<br>
                            house_no:<br>
                            flat_no:<br>
                            postcode:<br>
                            town:<br>
                            province:<br>
                            country:<br>
                            since:<br>
                            import_db:
                        </td>
                        <td style="color:#BBB; width:120px">
                            imię:<br>
                            nazwisko:<br>
                            skrócona nazwa:<br>
                            nazwa firmy:<br>
                            telefon:<br>
                            ulica:<br>
                            numer domu:<br>
                            numer lokalu:<br>
                            kod pocztowy:<br>
                            miejscowość:<br>
                            województwo:<br>
                            państwo:<br>
                            data powstania:<br>
                            źródło:
                        </td>
                        <td>'.
                            $firstname.'<br>'.
                            $lastname.'<br>'.
                            $name_custom.'<br>'.
                            $name.'<br>'.
                            $phone.'<br>'.
                            $street.'<br>'.
                            $house_no.'<br>'.
                            $flat_no.'<br>'.
                            $postcode.'<br>'.
                            $town.'<br>'.
                            $province.'<br>'.
                            $country.'<br>'.
                            $since.'<br>'.
                            $import_db.'
                        </td>
                    </tr>
                </table>';
            echo $show_report;
            */

            $query = "SELECT id, firstname, lastname, phone, phone_additional, email, clientid, name_custom FROM clients WHERE nip = :nip";
            $stmt = $db->prepare($query);
            $stmt->execute([':nip' => $nipToCheck]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // BTW Check if client exists in local database
            if ($result) {
                $clientExistsMark = 1;
                $clientExists = 'Klient <strong style="color:#6C0">ISTNIEJE</strong> w firmowej bazie';
                $clientid = $result[0]['clientid']; // Assuming the first result is the primary one if multiple are returned
                $phone_additional = $result[0]['phone_additional'];
                $branch = array_column($result, 'id'); // Collect all ids

                // Assuming default values are set somewhere above this snippet
                $firstname = $firstname ?: $result[0]['firstname'];
                $lastname = $lastname ?: $result[0]['lastname'];
                $phone = $phone ?: $result[0]['phone'];
                $email = $result[0]['email'];
                $name_custom = $result[0]['name_custom'];
            } else {
                $branch[] = null;
                $clientid = null;
                $phone_additional = null;
                $clientExistsMark = 0;
                $clientExists = 'Klient <strong style="color:red">NIE ISTNIEJE</strong> w firmowej bazie';
                
                $email = '';
            }
            
            // In case the client does not have the clientid yet
            if ($clientid === null) {
                // Create the next clientid
                $query = "SELECT MAX(clientid) AS last_clientid FROM clients";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $clientid = $result['last_clientid'] + 1;
                // In case there's no clientid yet for any of the clients
                if ($clientid === 1) {
                    $clientid = '12345'; // The very first clientid
                }
            }

            // also in nip.php
            //$query = "SELECT COUNT(*) AS count FROM orders WHERE nip = :nip AND (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)";
            $query = "SELECT COUNT(*) AS count FROM orders WHERE clientid = :clientid AND history LIKE '%Zlecona naprawa%' AND status = 'Zakończono' AND (deleted = '' OR deleted IS NULL)"; // AND repair_accepted = 'TAK'
            $stmt = $db->prepare($query);
            $stmt->execute([':clientid' => $clientid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $client_orders = $result['count'];

            // also in fetchSuggestions.php
            $query = "SELECT MAX(orderid) AS last_orderid FROM orders WHERE clientid = :clientid AND (deleted = '' OR deleted IS NULL)";
            $stmt = $db->prepare($query);
            $stmt->execute([':clientid' => $clientid]);
            $result_tmp = $stmt->fetch(PDO::FETCH_ASSOC);
            $order = $result_tmp['last_orderid'];
            if ($order !== null) {
                //$exp = explode('/', $receipt);
                $exp = substr($order, -3);
                $incrementedNumber = intval($exp) + 1; //$exp[1]
                $orderid = str_pad($incrementedNumber, 3, "0", STR_PAD_LEFT);
            } else {
                $orderid = '001';
            }
            
            $orderid = $clientid . '/' . $orderid;
            $receiptid = $orderid . 'A';

            // Combine variables into an associative array
            $data[] = [
                "success" => true,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "phone" => $phone,
                "phone_additional" => $phone_additional,
                "email" => $email,
                "nip" => $nipToCheck,
                "clientid" => $clientid,
                "orderid" => $orderid,
                "receiptid" => $receiptid,
                "client_orders" => $client_orders,
                "name_custom" => $name_custom,
                "name" => $name,
                "street" => $street,
                "house_no" => $house_no,
                "flat_no" => $flat_no,
                "postcode" => $postcode,
                "town" => $town,
                "province" => $province,
                "country" => $country,
                "since" => $since,
                "import_db" => $import_db,
                "clientExists" => $clientExists,
                "clientExistsMark" => $clientExistsMark,
                "branch" => $branch
            ];
        }
        // Convert the array to JSON
        $jsonData = json_encode($data);
        echo $jsonData;
    } catch (InvalidUserKeyException $e) {
        $data[] = ["success" => false, "clientExists" => '', "clientExistsMark" => 0];
        $jsonData = json_encode($data);
        echo $jsonData;
    } catch (NotFoundException $e) {
        $data[] = ["success" => false, "clientExists" => '', "clientExistsMark" => 0];
        $jsonData = json_encode($data);
        echo $jsonData;
    }
}