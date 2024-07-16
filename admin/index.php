<?php
if (!isset($_SESSION['login']) || ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'editor')) {
    header("Location: login/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hydrodyna APP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css?v=1.0.0">
    <link rel="stylesheet" type="text/css" href="vendor/benhall14/php-calendar/html/css/calendar.css?v=1.0.0">
    <link rel="stylesheet" href="style/admin.css?v=1.0.0">
    <link rel="stylesheet" href="style/orders.css?v=1.0.0">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <!-- PRELOAD IMAGES FOR PRINT -->
    <link rel="preload" href="https://hydro-dyna.pl/app/images/Hydro-Dex.png" as="image">
    <link rel="preload" href="https://hydro-dyna.pl/app/images/Hydro-Dyna.png" as="image">
    <link rel="preload" href="https://hydro-dyna.pl/app/images/Hydro-Dex.jpg" as="image">
    <link rel="preload" href="https://hydro-dyna.pl/app/images/Hydro-Dyna.jpg" as="image">
</head>
<body id="bodyAdmin">

<div class="header">
    <img src="" class="logo" alt="Logo">
    <div class="menu-toggle-container">
        <div class="mainMenu">
            <a data-id="orders" href="#" id="ordersList">Lista zleceń</a>
            <a data-id="panel" href="#" id="ordersPanel">Panel zleceń</a>
            <a data-id="calendar" href="#" id="ordersCalendar"><i class="fa-regular fa-calendar-days"></i></a>
            <span id="ordersArchived">
                <input type="checkbox" id="archivedCheck" name="archivedCheck">
                <label id="archivedText" for="archivedCheck">Archiwum</label>
                <br>
                <span data-title="Sortowanie takie jak w Panelu Zleceń<br>Reklamacje, PILNE Naprawy...">
                    <input type="checkbox" id="sortByDate" name="column">
                    <label for="sortByDate">Sort wg daty</label>
                </span>
            </span>
            <br>
            <span id="rowCount">0 wyników</span>
            <div id="loading"><!--<img src="images/loader.gif">-->wczytywanie...</div>
        </div>
        <div id="underMenu">
            <div id="calendarMenu">
                <a href="#" id="calendarPrev"><i class="fa-solid fa-chevron-left"></i></a><span>dynamic</span><a href="#" id="calendarNext"><i class="fa-solid fa-chevron-right"></i></a><a href="#" id="calendarReset">Reset</a><a href="#" id="calendarAdd">Dodaj wydarzenie</a>
                <img class="resizePanel" data-title="Tryb pełnoekranowy" src="images/maximize.png">
            </div>
            <div id="searchBoxes">
                <input id="searchBox" class="searchInput" type="text" placeholder="Szukaj">
                <img class="resizePanel" data-title="Tryb pełnoekranowy" src="images/maximize.png">
                <a href="#" id="hideComplaints" data-title="Ukryj reklamacje"><img src="images/eye.png" style="height:10px"></a>
                <span id="hideComplaintsTimer"></span>
            </div>
            <div id="subMenu">
                <a href="#" id="changeCompany">Zmień Firmę</a><a href="#" id="addNew">Dodaj</a>
            </div>
            <div id="columnToggles">
                <div id="columnHover">
                    <a href="#">Wyświetl Kolumny</a>
                </div>
                <div id="dropdownContent">
                    <!-- Checkbox inputs go here -->
                </div>
            </div>
            <div class="findMe">
                <input id="searchBox2" class="searchInput" type="text" placeholder="Szukaj">
            </div>
            <div class="findMe">
                <img data-title="Szukaj po poszczególnych kolumnach" src="images/find.png">
            </div>
        </div>
    </div>
    <div id="login-details">
        <?php
        if (strtolower($_SESSION['login']) == 'prezes' || strtolower($_SESSION['login']) == 'prezes1') {
            echo '<img class="prezes" style="height:19px; margin-bottom:-3px" src="images/merc.png">';
            echo '<img class="prezes" src="images/crown.png">';
            echo '<span style="color:#ffb743">' . strtoupper($_SESSION['login']) . '</span>';
        } else if (strtolower($_SESSION['login']) == 'cezary' || strtolower($_SESSION['login']) == 'cezary1') {
            echo '<img class="badger" src="images/badger.png">';
            echo '<span style="color:#746e63">' . strtoupper($_SESSION['login']) . '</span>';
        } else if (strtolower($_SESSION['login']) == 'pawel' || strtolower($_SESSION['login']) == 'pawel1') {
            echo '<img class="pig" src="images/pig.png">';
            echo '<span style="color:#f6c">' . strtoupper($_SESSION['login']) . '</span>';
        } else if (strtolower($_SESSION['login']) == 'przemek' || strtolower($_SESSION['login']) == 'przemek1') {
            echo '<img class="space" src="images/space.png">';
            echo '<span style="color:#8e69c3">' . strtoupper($_SESSION['login']) . '</span>';
        } else {
            echo '<img class="admin" src="images/admin.png">';
            echo ucfirst($_SESSION['login']);
        }
        ?>
        (<?php echo $_SESSION['level']; ?>)

        <div id="statsToggles" style="margin:auto 7px">
            <div id="statsHover">
                <a href="#">Statystyki</a>
            </div>
            <div id="statsContent">
                <!-- Dynamic -->
            </div>
        </div>
        <a id="logoutBtn" href="login/logout.php">Wyloguj</a>
        <p id="backupInfo"><?php echo file_get_contents('backup/latestlog'); ?></p>

        <p class="mainMenu secMenu">
            <?php if ($_SESSION['level'] == 'admin') { ?>
                <a data-id="clients" href="#">Klienci</a>
                <a data-id="users" href="#">Użytkownicy</a>
                <a data-id="companies" href="#">Firmy</a>
            <?php } ?>
        </p>
        <a class="showPhotos cookie" href="instructions/1.png,2.png"><img data-title="Jak<br>zresetować<br>przeglądarkę" src="images/cookie.png"></a>

        <!--
        <table>
            <tr>
                <td>Ilość wpisów</td>
                <td id="rowCountTotal">0</td>
                <td id="rowCount">0</td>
            </tr>
            <tr>
                <td>Otwarte zlecenia</td>
                <td>327</td>
                <td>11</td>
            </tr>
            <tr>
                <td>Wyceny weryfikacji</td>
                <td>29,800zł</td>
                <td>5,500zł</td>
            </tr>
            <tr>
                <td>Wyceny napraw</td>
                <td>1,433,500zł</td>
                <td>166,000zł</td>
            </tr>
        </table>
        -->
    </div>
</div>
<div id="dateTime"></div>


<table id="recordsTable">
    <thead>
        <tr id="columnSearch">
        </tr>
        <tr id="columnNames">
        </tr>
    </thead>
    <tbody>
        <!-- Data rows inserted by jQuery -->
    </tbody>
</table>

<div id="temp">
    <a id="logoutBtn" href="login/logout.php">Wyloguj</a>
    <img id="resizePanel2" src="images/minimize.png">
</div>

<!-- Orders will be loaded here dynamically -->
<div id="flexContainer">
    <div class="orderContainers" id="ordersContainer1"></div>
    <div class="orderContainers" id="ordersContainer2"></div>
    <div class="orderContainers" id="ordersContainer3"></div>
    <div class="orderContainers" id="ordersContainer4"></div>
</div>

<div id="calendarContainer">
    <!-- dynamic content -->
</div>

<div id="infoBar">
    <!-- dynamic info -->
</div>

<div id="overlay"></div>

<div id="movePhotos">
    <img id="prevPhoto" src="images/next.png">
    <img id="deletePhoto" src="images/delete.png">
    <img id="closePhoto" src="images/close.png">
    <img id="nextPhoto" src="images/next.png">
</div>
<div id="showPhotos" class="photos">
</div>

<div id="formPopup" class="popup">
    <div class="notification_bg"></div>
    <div class="notification_bg_white"></div>
    <form id="recordForm" enctype="multipart/form-data">
    </form>
</div>

<div id="printPopup" class="popup">
    <div class="notification_bg"></div>
    <form id="printForm">
        <div id="emailConfirm">
            SMS wysłany pomyślnie na numer <span></span>
        </div>
        <div>
            <p>
                <strong>Etykiety</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="label" class="printBtn docsBtn printLabel" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <p>
                <strong>Adres</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="address" class="printBtn docsBtn printLabel" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <p>
                <strong>Dokument przyjęcia na magazyn</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="doc" class="printBtn docsBtn printDoc" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <p>
                <strong>Dokument weryfikacji</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="doc2" class="printBtn docsBtn printDoc" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <p>
                <strong>Dokument naprawy</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="doc3" class="printBtn docsBtn printDoc" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <p>
                <strong>Dokument wydania z magazynu</strong><br>
                Generuj PDF<input class="create_pdfs" name="create_pdfs" type="checkbox" checked>
            </p>
            <button data-type="doc4" class="printBtn docsBtn printDoc" type="button" data-id="0">Drukuj</button>
        </div>
        <div>
            <button type="button" class="cancelBtn">Anuluj Drukowanie</button>
        </div>
</form>
</div>

<div id="notification">
    <img class="blink" src="images/sound.png">
    <p>Kliknij aby dokończyć wypełnianie formularza</p>
</div>

<iframe id="printFrame" style="display:none;">
    <!-- Dynamic print data -->
</iframe>

<script src="scripts/jquery.min.js?v=1.0.0"></script>
<script src="scripts/cookies.js?v=1.0.1"></script>
<script src="scripts/orders.js?v=1.0.1"></script>
<script src="scripts/admin.js?v=1.0.7"></script>
<script src="scripts/reminder.js?v=1.0.1"></script>
</body>
</html>