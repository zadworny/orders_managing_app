<?php
require_once '../database/connect.php';

class Deadlines {
    private PDO $db; // Change the property type to PDO.

    public function __construct() {
        $database = new Database(); // Create an instance of Database class.
        $this->db = $database->getConnection(); // Get the PDO connection from Database class.
    }

    public function fetchDeadlines($date, $column) {
        //$sql = "SELECT COUNT(*) FROM orders WHERE " . $column . "_date = :date AND (archived IS NULL OR archived = '')";
        $sql = "SELECT COUNT(*) FROM orders WHERE (verification_date = :vdate OR repair_date = :rdate) AND 
                (archived = '' OR archived IS NULL) AND (deleted = '' OR deleted IS NULL) AND 
                (
                ((status = 'Zlecona weryfikacja' OR status = 'Wydać bez naprawy') AND verification_accepted = 'TAK' AND verification_date IS NOT NULL AND verification_date <> '') OR
                ((status = 'Zlecona naprawa' OR status = 'Zrobić test' OR status = 'Wydać po naprawie' OR status = 'Reklamacja') AND repair_accepted = 'TAK' AND repair_date IS NOT NULL AND repair_date <> '')
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':vdate', $date);
        $stmt->bindParam(':rdate', $date);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count;
    }
}

class PolishHolidays {
    private $year;

    public function __construct($year) {
        $this->year = $year;
    }

    public function getEasterDate() {
        $base = new DateTime("$this->year-03-21");
        $days = easter_days($this->year);
        return $base->add(new DateInterval("P{$days}D"));
    }

    public function getHolidays() {
        $easter = $this->getEasterDate();
        $easterMonday = (clone $easter)->add(new DateInterval('P1D'))->format('m-d');
        $corpusChristi = (clone $easter)->add(new DateInterval('P60D'))->format('m-d');
        return [
            '01-01', '01-06', '05-01', '05-03', '08-15', '11-01', '11-11', '12-25', '12-26', // Fixed holidays
            $easterMonday, $corpusChristi, // Variable holidays
        ];
    }

    public function getHolidaysInRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($start, $interval ,$end);

        $holidaysInRange = [];
        foreach($daterange as $date){
            $formattedDate = $date->format("m-d");
            if (in_array($formattedDate, $this->getHolidays())) {
                $holidaysInRange[] = $date->format("Y-m-d");
            }
        }
        return $holidaysInRange;
    }
}

class HolidayCalculator {
    private $polishHolidays;

    public function __construct($year) {
        $this->polishHolidays = new PolishHolidays($year);
    }

    public function countWeekendsInRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $weekends = 0;

        while ($start <= $end) {
            if ($start->format('N') >= 6) { // Saturday or Sunday
                $weekends++;
            }
            $start->add(new DateInterval('P1D'));
        }

        // Since weekends are counted in days, divide by 2 to get full weekends
        return floor($weekends / 2);
    }

    public function calculateWorkingDays($startDate, $daysToAdd) {
        $workingDays = 0;
        $date = new DateTime($startDate);
        $holidays = $this->polishHolidays->getHolidays();

        while ($workingDays < $daysToAdd) {
            $date->add(new DateInterval('P1D')); // Add a day
            $weekday = $date->format('N'); // 1 (for Monday) through 7 (for Sunday)
            $formattedDate = $date->format('m-d');

            if ($weekday < 6 && !in_array($formattedDate, $holidays)) {
                $workingDays++;
            }
        }

        return $date->format('Y-m-d');
    }

    public function calculateWorkingDaysBetweenDates($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $workingDays = 0;
        $holidays = $this->polishHolidays->getHolidays();

        while ($start <= $end) {
            $weekday = $start->format('N');
            $formattedDate = $start->format('m-d');

            if ($weekday < 6 && !in_array($formattedDate, $holidays)) {
                $workingDays++;
            }

            $start->add(new DateInterval('P1D'));
        }
        $workingDays = $workingDays - 1;
        if ($workingDays < 0) {
            $workingDays = 0;
        }

        return $workingDays;
    }

    public function getHolidaysInRange($startDate, $endDate) {
        return $this->polishHolidays->getHolidaysInRange($startDate, $endDate);
    }

    public function getWeekendsInRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $weekends = [];

        while ($start <= $end) {
            if ($start->format('N') >= 6) { // Saturday or Sunday
                $weekends[] = $start->format('Y-m-d');
            }
            $start->add(new DateInterval('P1D'));
        }

        return $weekends;
    }
}

//if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['daysToAdd']) && !empty($_POST['startDate'])) {
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['daysToAdd']) || isset($_POST['startDate']) || isset($_POST['endDate']))) {

    $daysToAdd = $_POST['daysToAdd'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $column = $_POST['column'];

    $year = date('Y', strtotime($startDate));
    $calculator = new HolidayCalculator($year);

    if ($_POST['method'] == "date") {
        $calculatedDate = $calculator->calculateWorkingDays($startDate, $daysToAdd);
        $endDate = $calculatedDate;
    } else {
        $calculatedDate = $calculator->calculateWorkingDaysBetweenDates($startDate, $endDate);
    }

    $deadlines = new Deadlines();
    $count = $deadlines->fetchDeadlines($endDate, $column);

    header('Content-Type: application/json');
    echo json_encode(['date' => $calculatedDate, 'deadlines' => $count]);

    // Additional
    /*
    $holidaysInRange = $calculator->getHolidaysInRange($startDate, $calculatedDate);
    $weekendsCount = $calculator->countWeekendsInRange($startDate, $calculatedDate);

    echo "Calculated Date: " . $calculatedDate . "<br>";
    echo "Holidays in Range:<br>";
    foreach ($holidaysInRange as $holiday) {
        echo $holiday . "<br>";
    }
    echo "Number of weekends in range: " . $weekendsCount . "<br>";
    */
}
?>