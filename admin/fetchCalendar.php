<?php
// https://github.com/benhall14/php-calendar
require_once '../database/connect.php';
require_once '../vendor/autoload.php';
require_once '../functions/columns.php';
include 'workingDays.php';

// Initialize the Database connection
$database = new Database();
$db = $database->getConnection();

use benhall14\phpCalendar\Calendar as Calendar;

$m = $mp = $mn = $_POST['month'];
$y = $yp = $yn = $_POST['year'];
$events = array();

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
    'Reklamacja' => 1
];
            
/*** CALENDAR SETTINGS ***/
$calendar = new Calendar;
$calendar->useMondayStartingDate();
$calendar->useFullDayNames();
//$calendar->stylesheet();
//$calendar->hideSaturdays();
//$calendar->hideSundays();
$calendar->setDays([
    'sunday' => ['initials' => 'N', 'full' => 'Niedziela'],
    'monday' => ['initials' => 'P', 'full' => 'Poniedziałek'],
    'tuesday' => ['initials' => 'W', 'full' => 'Wtorek'],
    'wednesday' => ['initials' => 'Ś', 'full' => 'Środa'],
    'thursday' => ['initials' => 'C', 'full' => 'Czwartek'],
    'friday' => ['initials' => 'P', 'full' => 'Piątek'],
    'saturday' => ['initials' => 'S', 'full' => 'Sobota'],
]);
$calendar->setMonths([
    'january' => 'Styczeń',  
    'february' => 'Luty',  
    'march' => 'Marzec',  
    'april' => 'Kwiecień',  
    'may' => 'Maj',  
    'june' => 'Czerwiec',  
    'july' => 'Lipiec',  
    'august' => 'Sierpień',  
    'september' => 'Wrzesień',  
    'october' => 'Październik',  
    'november' => 'Listopad',  
    'december' => 'Grudzień'
]);

// Events
try {
    $query = "SELECT * FROM events WHERE (deleted = '' OR deleted IS NULL)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add events to the calendar
    foreach ($results as $r) {
        $id = $r['id'];
        $note = $r['note'];
        $from = $r['date_from'];
        $to = $r['date_to'];
        $user = $r['user'];
        $event = $r['event'];
        $exp = $r['insert_date'];
            $insert = explode(' ',$exp)[0];

        $title = '<tr><td>Dodane</td><td>' . $insert . '</td></tr><tr><td>Przez</td><td>' . $user . '</td></tr>';
        if ($note != '') {
            $title .= '<tr><td>Notatka</td><td>' . $note . '</td></tr>';
        }
        $img = '<img class="eventDelete" data-id="' . $id . '" src="images/close.png">';
        $img .= '<img class="eventEdit" data-id="' . $id . '" src="images/edit.png">';
        $specialEvent = $img . '<span class="status-11 specialEvent" data-title="' . $title . '">' . $event . '</span>';

        $events[] = array(
            'start' => $from,
            'end' => $to,
            'summary' => $specialEvent,
            'mask' => true
        );
    }

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}

// Today
$events[] = array(
    'start' => date('Y-m-d'),
    'end' => date('Y-m-d'),
    'summary' => '<span class="calendarToday">Dzisiaj</span>',
    'mask' => true,
);

// Holidays
$holidays = (new PolishHolidays($y))->getHolidays(); // from workingDays.php
foreach ($holidays as $h) {
    $events[] = array(
        'start' => $y . '-' . $h,
        'end' => $y . '-' . $h,
        'summary' => '<span class="calendarDayOff">Dzień wolny od pracy</span>',
        'mask' => true,
    );
}

// Test
/*$display = $calendar->draw(date($y . '-' . $m . '-d'), 'white');
header('Content-Type: application/json');
echo json_encode(['calendar' => $display, 'm' => $m, 'y' => $y]);
exit();*/

// Weekends
if ($m == 12) {
    $mp = $m - 1;
    $mn = 1;
    $yn = $y + 1;
} else if ($m == 1) {
    $mp = 12;
    $mn = $m + 1;
    $yp = $y - 1;
} else {
    $mp = $m - 1;
    $mn = $m + 1;
}
$weekends = (new HolidayCalculator($y))->getWeekendsInRange($yp . '-' . $mp . '-15', $yn . '-' . $mn . '-15'); // from workingDays.php
foreach ($weekends as $w) {
    $events[] = array(
        'start' => $w,
        'end' => $w,
        'summary' => '<span class="calendarWeekend">Weekend</span>',
        'mask' => true,
    );
}

// Orders
try {
    //$query = "SELECT * FROM orders WHERE (archived = '' OR archived IS NULL) AND (deleted = '' OR deleted IS NULL)";
    $query = "SELECT DISTINCT " . $columnsListSpecial . " FROM orders t1 JOIN clients t2 ON t1.clientid = t2.clientid WHERE (t1.archived = '' OR t1.archived IS NULL) AND (t1.deleted = '' OR t1.deleted IS NULL)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add events to the calendar
    foreach ($results as $r) {
        $statusKey = $orderOptions[$r['status']];
        $id = $r['id'];
        //$title = '<span style=font-size:12px;display:block;max-width:175px>' . $r['part_name'] . '<br><br>Klient: ' . $r['name_custom'] . '</span>';
        $part_name = str_replace('"', '', $r['part_name']);
        $name_custom = str_replace('"', '', $r['name_custom']);
        $title = '<tr><td>Podz.</td><td>' . $part_name . '</td><tr><td>Klient</td><td>' . $name_custom . '</td></tr>';

        $date = '';
        if ($r['status'] == 'Zlecona weryfikacja' || $r['status'] == 'Wydać bez naprawy') {
            $date = $r['verification_date'];
        } else if ($r['status'] == 'Zlecona naprawa' || $r['status'] == 'Zrobić test' || $r['status'] == 'Wydać po naprawie') {
            $date = $r['repair_date'];
        } else {
            $date = explode(' ', $r['acceptance_date'])[0];
        }

        // switchBtn vs backToList
        if ($date != '') {
            $events[] = array(
                'start' => $date,
                'end' => $date,
                'summary' => '<span class="status-' . $statusKey . '" data-title="' . $title . '"><button class="backToList switchBtn" data-id="' . $id . '">&nbsp;</button>' . $r['status'] . '</span>', //$r['part_name'],
                'mask' => true,
            );
        }
    }

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}

$calendar->addEvents($events);
$display = $calendar->draw(date($y . '-' . $m . '-d'), 'white');

header('Content-Type: application/json');
echo json_encode(['calendar' => $display, 'count' => count($events), 'date' => $date]);
//echo $display;
?>
