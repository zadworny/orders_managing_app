<?php
require_once '../database/connect.php';

// Assuming a function exists to get the database connection
function getDatabaseConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Function to fetch statistics
function fetchStatistics() {
    $db = getDatabaseConnection();
    $sql = "SELECT 
            (SELECT COUNT(id) FROM orders WHERE (archived = '' OR archived IS NULL) AND (deleted = '' OR deleted IS NULL)) AS active_orders_count,
            (SELECT COUNT(id) FROM orders WHERE deleted = '' OR deleted IS NULL) AS total_orders_count,
            (SELECT COUNT(id) FROM orders WHERE archived = 'TAK') AS archived_orders_count,
            (SELECT COUNT(id) FROM clients WHERE deleted = '' OR deleted IS NULL) AS total_clients_count,
            (SELECT COUNT(id) FROM users WHERE deleted = '' OR deleted IS NULL) AS total_users_count,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)) AS total_verification_cost,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)) AS total_repair_cost,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND (archived = '' OR archived IS NULL)) AS active_verification_cost,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND (archived = '' OR archived IS NULL)) AS active_repair_cost,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND DATE_FORMAT(verification_date_start, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS verification_cost_current_month,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND DATE_FORMAT(repair_date_start, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS repair_cost_current_month,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= CURDATE() - INTERVAL 30 DAY) AS verification_cost_last30,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= CURDATE() - INTERVAL 30 DAY) AS repair_cost_last30,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH AND verification_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)) AS verification_cost_previous_month,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH AND repair_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)) AS repair_cost_previous_month,

            (SELECT SUM(verification_quote) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 2 MONTH AND verification_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH) AS verification_count_2months_back,
            (SELECT SUM(repair_quote) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 2 MONTH AND repair_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH) AS repair_count_2months_back,

            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)) AS total_verification_count,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL)) AS total_repair_count,

            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND (archived = '' OR archived IS NULL)) AS active_verification_count,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND (archived = '' OR archived IS NULL)) AS active_repair_count,

            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND DATE_FORMAT(verification_date_start, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS verification_count_current_month,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND DATE_FORMAT(repair_date_start, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS repair_count_current_month,

            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= CURDATE() - INTERVAL 30 DAY) AS verification_count_last30,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= CURDATE() - INTERVAL 30 DAY) AS repair_count_last30,

            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH AND verification_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)) AS verification_count_previous_month,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH AND repair_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)) AS repair_count_previous_month,
            
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona weryfikacja%' OR status = 'Zlecona weryfikacja') AND verification_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND verification_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 2 MONTH AND verification_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH) AS verification_count_2months_back,
            (SELECT COUNT(*) FROM orders WHERE (history LIKE '%Zlecona naprawa%' OR status = 'Zlecona naprawa') AND repair_accepted = 'TAK' AND (deleted = '' OR deleted IS NULL) AND repair_date_start >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 2 MONTH AND repair_date_start < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY) - INTERVAL 1 MONTH) AS repair_count_2months_back";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $stmt->fetch();
}

// Function to display statistics
function displayStatistics() {
    $row = fetchStatistics();

    setlocale(LC_TIME, 'pl_PL.UTF-8');
    $months = array(
        'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
        'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
    );
    $currentMonth = date('n');
    $previousMonth = $currentMonth - 1;
    if ($previousMonth < 1) {
        $previousMonth = 12;
    }
    $twoMonthsBack = $currentMonth - 2;
    if ($twoMonthsBack < 1) {
        $twoMonthsBack += 12;
    }
    $currentMonthName = $months[$currentMonth - 1];
    $previousMonthName = $months[$previousMonth - 1];
    $twoMonthsBackName = $months[$twoMonthsBack - 1];

    // admin vs editor
    $level = $_SESSION['level'];
    $stat = $stat0 = $stat1 = $stat2 = $stat3 = $stat4 = $stat5 = $stat6 = $stat7 = $stat8 = $stat9 = $stat10 = $stat11 = $stat12 = '';
    if ($level == 'admin') {
        $stat = '/ wyceny';
        $stat0 = '<td></td>';
        $stat1 = '<td>' . number_format($row["verification_cost_current_month"]) . '</td>';
        $stat2 = '<td>' . number_format($row["verification_cost_previous_month"]) . '</td>';
        $stat3 = '<td>' . number_format($row["verification_cost_2months_back"]) . '</td>';
        $stat4 = '<td>' . number_format($row["active_verification_cost"]) . '</td>';
        $stat5 = '<td>' . number_format($row["verification_cost_last30"]) . '</td>';
        $stat6 = '<td>' . number_format($row["total_verification_cost"]) . '</td>';
        $stat7 = '<td>' . number_format($row["repair_cost_current_month"]) . '</td>';
        $stat8 = '<td>' . number_format($row["repair_cost_previous_month"]) . '</td>';
        $stat9 = '<td>' . number_format($row["repair_cost_2months_back"]) . '</td>';
        $stat10 = '<td>' . number_format($row["active_repair_cost"]) . '</td>';
        $stat11 = '<td>' . number_format($row["repair_cost_last30"]) . '</td>';
        $stat12 = '<td>' . number_format($row["total_repair_cost"]) . '</td>';
    }

    $return = "<table class='statisticsTable'>";
        $return .= "<tr><th>Zlecenia wszystkie </th>" . $stat0 . "<td>" . number_format($row["total_orders_count"]) . "</td></tr>";
        $return .= "<tr><th>Zlecenia aktywne </th>" . $stat0 . "<td>" . number_format($row["active_orders_count"]) . "</td></tr>"; // htmlspecialchars
        $return .= "<tr><th>Zlecenia zakończone </th>" . $stat0 . "<td>" . number_format($row["archived_orders_count"]) . "</td></tr>";

        $return .= "<tr><td>&nbsp;</td>" . $stat0 . "</tr>";
        $return .= "<tr><th>Liczba klientów </th>" . $stat0 . "<td>" . number_format($row["total_clients_count"]) . "</td></tr>";
        $return .= "<tr><th>Liczba użytkowników </th>" . $stat0 . "<td>" . number_format($row["total_users_count"]) . "</td></tr>";
            
        $return .= "<tr><td>&nbsp;</td>" . $stat0 . "</tr>";
        $return .= "<tr><th>Weryfikacje ilość " . $stat . "</th><td></td>" . $stat0 . "</tr>";
        $return .= "<tr><td>&nbsp;</td>" . $stat0 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 1. " . $currentMonthName . " </th><td>" . number_format($row["verification_count_current_month"]) . "</td>" . $stat1 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 2. " . $previousMonthName . " </th><td>" . number_format($row["verification_count_previous_month"]) . "</td>" . $stat2 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 3. " . $twoMonthsBackName . " </th><td>" . number_format($row["verification_count_2months_back"]) . "</td>" . $stat3 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; W trakcie </th><td>" . number_format($row["active_verification_count"]) . "</td>" . $stat4 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; Ostatnie 30 dni </th><td>" . number_format($row["verification_count_last30"]) . "</td>" . $stat5 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; Od początku </th><td>" . number_format($row["total_verification_count"]) . "</td>" . $stat6 . "</tr>";
        
        $return .= "<tr><td>&nbsp;</td>" . $stat0 . "</tr>";
        $return .= "<tr><th>Naprawy ilość " . $stat . "</th><td></td>" . $stat0 . "</tr>";
        $return .= "<tr><td>&nbsp;</td>" . $stat0 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 1. " . $currentMonthName . " </th><td>" . number_format($row["repair_count_current_month"]) . "</td>" . $stat7 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 2. " . $previousMonthName . " </th><td>" . number_format($row["repair_count_previous_month"]) . "</td>" . $stat8 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; 3. " . $twoMonthsBackName . " </th><td>" . number_format($row["repair_count_2months_back"]) . "</td>" . $stat9 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; W trakcie </th><td>" . number_format($row["active_repair_count"]) . "</td>" . $stat10 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; Ostatnie 30 dni </th><td>" . number_format($row["repair_count_last30"]) . "</td>" . $stat11 . "</tr>";
        $return .= "<tr><th>&nbsp;&nbsp;&nbsp;&nbsp; Od początku </th><td>" . number_format($row["total_repair_count"]) . "</td>" . $stat12 . "</tr>";
    $return .= "</table>";

    echo $return;
}

displayStatistics();
?>
