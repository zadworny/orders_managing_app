<?php
//error_reporting(E_ALL);
require_once '../database/connect.php';
require_once '../admin/pdfGenerator.php';
require_once '../vendor/autoload.php';
require_once '../functions/columns.php';

$pdfGenerator = new PDFGenerator();

/*$_POST['id'] = '163';
$_POST['dbTable'] = 'orders';
$_POST['method'] = 'label';
$_POST['company'] = 'Hydro-Dyna';
$_POST['single'] = 'single';
$_POST['pdf'] = '';*/

if (isset($_POST['id'])) {
    $method = $_POST['method'];
    $single = $_POST['single'];
    $pdf = $_POST['pdf'];
    
    $database = new Database();
    $db = $database->getConnection();
    $table = $_POST['dbTable'];
    $id = $_POST['id'];
    $company = $_POST['company']; // Fetch from DB not from current app status!

    if ($id == '0') {
        $query = "SELECT MAX(id) as latest_id FROM orders";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['latest_id'];
    }
    
    $columns = ['clientid', 'firstname', 'lastname', 'company', 'orderid', 'receiptid', 'acceptance_date', 'name', 'name_custom', 'part_name', 'status', 'verification_date', 'repair_date', 'note_receive', 'note_repair', 'interference_note', 'insert_date'];
    $cnames = ['Numer klienta', 'Imię', 'Nazwisko', 'Firma', 'Numer części', 'Numer zamówienia', 'Data przyjęcia', 'Nazwa firmy', 'Nazwa własna', 'Nazwa części', 'Status', 'Termin weryfikacji', 'Termin naprawy', 'Notatka przyjęcia', 'Notatka naprawy', 'Ingerencja', 'Data dodania'];
    $column_map = array_combine($columns, $cnames);

    //$columns_imploded = implode(',', $columns);
    //$query = "SELECT " . $columns_imploded . " FROM " . $table . " WHERE id = :id LIMIT 1";

    $query = "SELECT DISTINCT " . $columnsListSpecial . " FROM " . $table . " t1 JOIN clients t2 ON t1.clientid = t2.clientid WHERE t1.id = :id LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $record = [];
    foreach ($row as $column => $value) {
        //if (in_array($column, $columns)) {
            $descriptive_name = $column_map[$column] ?? $column;
            if ($value === null || $value === '') {
                $value = '<span>brak</span>';
            }
            $record[$descriptive_name] = $value;
        //}
    }

    // Could be done better - but needed to make this work fast...
    $query1 = "SELECT part_name, repair_quote, verification_quote, receiptid FROM " . $table . " WHERE orderid = :orderid AND deleted IS NULL";
    $stmt1 = $db->prepare($query1);
    $stmt1->bindParam(':orderid', $row['orderid']);
    $stmt1->execute();
    while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $id = $row1['receiptid'];
        $name = $row1['part_name'];
        $allParts[$id] = $name;
        $vCost[$id] = $row1['verification_quote'];
        $rCost[$id] = $row1['repair_quote'];
    }

    $path = 'https://hydro-dyna.pl/app/images/';
    //if ($company == 'Hydro-Dex') {
    if ($row['company'] == 'Hydro-Dex') {
        $id2 = '2';
        $img = $path . 'Hydro-Dex.jpg'; //png
    } else {
        $id2 = '1';
        $img = $path . 'Hydro-Dyna.jpg'; //png
    }
    $query2 = "SELECT * FROM companies WHERE id = :id LIMIT 1";
    $stmt2 = $db->prepare($query2);
    $stmt2->bindParam(':id', $id2);
    $stmt2->execute();
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    $query3 = "SELECT * FROM clients WHERE clientid = :clientid LIMIT 1";
    $stmt3 = $db->prepare($query3);
    $stmt3->bindParam(':clientid', $row['clientid']);
    $stmt3->execute();
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
   
    mb_internal_encoding("UTF-8");
    $record3 = [];
    foreach ($row3 as $column3 => $value3) {
        if ($value3 !== null && $value3 !== '') {
            $value3 = mb_strtolower($value3);
            $record3[$column3] = mb_convert_case($value3, MB_CASE_TITLE, "UTF-8");
        } else {
            $record3[$column3] = $value3;
        }
    }

    $exp1 = explode(' ', $row['insert_date']);
    $exp2 = explode(':', $exp1[1]);
    $time = $exp2[0] . ':' . $exp2[1];
    $acceptance = $record['Data przyjęcia']; // . ' ' . $time;

    $file = str_replace('/', '', $row['orderid']);

    if ($method == 'label') {
        
        $name = $row['name_custom'] ?: $row['name'];
        if ($name == '') { $name = $row['firstname'] . ' ' . $row['lastname']; }

        // LABEL
        $htmlContent = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Print Content</title>
            <style>
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: normal;
                src: url(../../app/fonts/DejaVuSans.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: normal;
                src: url(../../app/fonts/DejaVuSans-Oblique.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: bold;
                src: url(../../app/fonts/DejaVuSans-Bold.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: bold;
                src: url(../../app/fonts/DejaVuSans-BoldOblique.ttf) format("truetype");
            }
            @page {
                size: 100mm 62mm
            }
            html {
                margin: 0
            }
            body {
                font-family: DejaVu Sans, sans-serif;
                margin: 2mm
            }
            .oneLabel {
                margin: 0,
                padding: 0
            }
            .oneLabel:first-of-type {
                page-break-before: auto;
            }
            img {
                width: 100%;
            }
            h1 {
                font-weight: bold;
                font-size: 28px;
                margin: 5px 0;
            }
            h2 {
                font-size: 20px;
                margin-top: 10px;
                margin-bottom: 0;
                line-height: 110%;
            }
            h2, 
            span {
                width: 100%;
                text-align: center;
                margin: 0 auto;
            }
            table td {
                font-size: 13px;
            }
            table tr:nth-child(1) td {
                font-weight: normal;
                text-align: right;
                line-height: 115%;
            }
            table tr:nth-child(1) td:nth-child(1) {
                width: 125px;
            }
            table tr:nth-child(1) td:nth-child(2) {
                width: 225px;
                float: right
            }
            table tr:nth-child(2) td {
                text-align: center;
                line-height: 135%;
            }
            @media print {
                @page {
                    width: 62mm,
                    height: 100mm,
                    size: landscape,
                    margin: 0
                }
                body {
                    margin: 2mm
                }
                .oneLabel {
                    display: block;
                    page-break-before: auto;
                    page-break-after: always;
                    break-inside: avoid;
                    margin: 0;
                    width: 100%;
                    height: 100%;
                }
                /*.oneLabel:first-of-type {
                    page-break-before: auto;
                }*/
                * {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact; /* Standard syntax - fix for light grey elements */
                }
                img {
                    width: 125px;
                    float: left;
                }
                h1 {
                    font-weight: bold;
                    font-size: 32px;
                    margin: 10px 0;
                    float: right;
                }
                h2 {
                    font-size: 26px;
                    margin-top: 10px;
                    margin-bottom: 0;
                    line-height: 110%;
                }
                td {
                    font-size: 14px;
                }
                tr:nth-child(1) td {
                    line-height: 125%;
                }
            }
            </style>
        </head>
        <body>';

            if ($single == 'single') { // temp: single
                $htmlContent .= '<table class="oneLabel">
                    <tr>
                        <td>
                            <img src="' . $img . '">
                        </td>
                        <td>
                            <h1>ID:' . $record['Numer zamówienia'] . '</h1>
                            Przyjęto: <strong>' . str_replace(array('-', ' '), array('&#8729;', '&nbsp;'), $acceptance) . '</strong><br>
                            Termin weryfikacji: <strong>' . str_replace('-', '&#8729;', $record['Termin weryfikacji']) . '</strong><br>
                            Termin naprawy: <strong>' . str_replace('-', '&#8729;', $record['Termin naprawy']) . '</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 12px">
                            <span>' . $row['part_name'] . '</span>
                            <h2>' . $name . '</h2>
                        </td>
                    </tr>
                </table>';
            } else {
                foreach ($allParts as $k => $v) {
                    $htmlContent .= '<table class="oneLabel">
                        <tr>
                            <td>
                                <img src="' . $img . '">
                            </td>
                            <td>
                                <h1>ID:' . $k . '</h1>
                                Przyjęto: <strong>' . str_replace('-', '&#8729;', $acceptance) . '</strong><br>
                                Termin weryfikacji: <strong>' . str_replace('-', '&#8729;', $record['Termin weryfikacji']) . '</strong><br>
                                Termin naprawy: <strong>' . str_replace('-', '&#8729;', $record['Termin naprawy']) . '</strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 12px">
                                <span>' . $v . '</span>
                                <h2>' . $name . '</h2>
                            </td>
                        </tr>
                    </table>';
                }
            }

        $htmlContent .= '</body>
        </html>';

        $path = 'pdfs/' . $file . '_etykieta.pdf';
        $size = '100mm 62mm';
        $orientation = 'landscape';

    } elseif ($method == 'address') {

        $flatNo2 = is_null($row2['flat_no']) ? '' : '/' . $row2['flat_no'];
        $flatNo3 = is_null($record3['flat_no']) ? '' : '/' . $record3['flat_no'];

        $htmlContent = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Print Content</title>
            <style>
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: normal;
                src: url(../../app/fonts/DejaVuSans.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: normal;
                src: url(../../app/fonts/DejaVuSans-Oblique.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: bold;
                src: url(../../app/fonts/DejaVuSans-Bold.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: bold;
                src: url(../../app/fonts/DejaVuSans-BoldOblique.ttf) format("truetype");
            }
            body {
                font-family: DejaVu Sans, sans-serif;
                text-align: center;
                font-size: 44px;
                width: 90%;
                margin: 0 auto;
                line-height: 175%;
            }
            @media print {
                @page {
                    width: 210mm;
                    min-height: 297mm;
                    size: portrait;
                }
            }
            </style>
        </head>
        <body>
            <strong>' . $record3['name'] . '</strong><br>'
            . $record3['street'] . ' ' . $record3['house_no'] . $flatNo3 . '<br>'
            . $record3['postcode'] . ' ' . $record3['town'] . '<br><br><br>'
            . $record3['phone'] . '<br>'
            . $record3['email'] .
        '</body>
        </html>';

        $path = 'pdfs/' . $file . '_adres.pdf';
        $size = 'A4';
        $orientation = 'portrait';

    } else {

        $partNote = '';

        if ($method == 'doc') {
            $title1 = 'Dane firmy przyjmującej';
            $title2 = 'PRZYJĘCIE NA MAGAZYN';
            $title3 = 'Podpis osoby przyjmującej na magazyn';
            $title4 = 'Podpis osoby zlecającej';
            if ($row['note_receive'] != '') {
                $partNote = '<p>Uwagi:</p><p>' . $row['note_receive'] . '</p>';
            } else {
                $partNote = '';
            }
            $note = '<br>Potwierdzam iż zapoznałem się z <u>Ogólnymi Warunkami Umów</u>';
            $path = 'pdfs/' . $file . '_przyjecie.pdf';
        } elseif ($method == 'doc2') {
            $title1 = 'Dane firmy przyjmującej';
            $title2 = 'ZLECENIE WERYFIKACJI';
            $title3 = 'Podpis osoby przyjmującej zlecenie';
            $title4 = 'Podpis osoby zlecającej';
            $note = '<br>Potwierdzam iż zapoznałem się z <u>Ogólnymi Warunkami Umów</u>';
            $path = 'pdfs/' . $file . '_weryfikacja.pdf';
        } elseif ($method == 'doc3') {
            $title1 = 'Dane firmy przyjmującej';
            $title2 = 'ZLECENIE NAPRAWY';
            $title3 = 'Podpis osoby przyjmującej zlecenie';
            $title4 = 'Podpis osoby zlecającej';
            if ($row['note_repair'] != '') {
                $partNote = '<p>Uwagi:</p><p>' . $row['note_repair'] . '</p>';
            } else {
                $partNote = '';
            }
            $note = '<br>Potwierdzam iż zapoznałem się z <u>Ogólnymi Warunkami Umów</u>';
            $path = 'pdfs/' . $file . '_naprawa.pdf';
        } elseif ($method == 'doc4') {
            $title1 = 'Dane firmy wydającej';
            $title2 = 'WYDANIE Z MAGAZYNU';
            $title3 = 'Podpis osoby wydającej z magazynu';
            $title4 = 'Podpis osoby odbierającej';
            $note = '<br>Potwierdzam odbiór urządzeń pod względem ilościowym i jakościowym';
            $note .= '<br>Potwierdzam iż zapoznałem się z <u>Ogólnymi Warunkami Umów</u>';
            $path = 'pdfs/' . $file . '_wydanie.pdf';
        }
        $note .= '<br><u>Ogólne Warunki Umów</u> na kolejnej stronie &rarr;';

        $flatNo2 = is_null($row2['flat_no']) ? '' : '/' . $row2['flat_no'];
        $flatNo3 = is_null($record3['flat_no']) ? '' : '/' . $record3['flat_no'];

        $owuDoc = '<div id="owuDoc">
        
        <ol>
    
            <h2>Ogólne Warunki Umów obowiązujące w ' . $row2['name'] . ', ' . $row2['town'] . ', NIP ' . $row2['nip'] . ', REGON ' . $row2['regon'] . ' od dnia 24.06.2024r.</h2>

            <li>
                <strong>Postanowienia Ogólne</strong>
                <br>
                Niniejsze Ogólne Warunki Umów (OWU) mają zastosowanie do wszystkich umów zawieranych przez Zleceniobiorcę, w szczególności zarówno na podstawie ofert Zleceniobiorcy jak i zaakceptowanych zleceń. Postanowienia umowne sprzeczne z treścią OWU wymagają zachowania formy pisemnej pod rygorem nieważności. Zawarcie umowy oznacza akceptację OWU, stanowiących jej integralną część.
            </li>
            
            <li>
                <strong>Zlecenie i oferta</strong>
                <br>
                Przedmiotem oferty oraz zawartej umowy są jedynie podzespoły wyraźnie wymienione w treści Zlecenia/Oferty. Umowa stron nie dotyczy jakichkolwiek innych podzespołów czy elementów maszyny. W szczególności umowa nie obejmuje weryfikacji maszyny, przyczyn jej niesprawności, sprawdzania jakichkolwiek elementów maszyny czy wskazania koniecznych w niej napraw. Wynagrodzenie nie obejmuje również montażu naprawionego podzespołu w maszynie ani rozruchu maszyny po naprawie podzespołu, chyba żeby było to wprost pisemnie wymienione w ofercie. Zlecenie weryfikacji maszyny pod kątem jej niesprawności wymaga zawarcia innej, specjalnej umowy zawartej na piśmie pod rygorem nieważności.
            <li>
                <strong>Dostarczenie podzespołu do naprawy</strong>
                <br>
                O ile strony pisemnie nie postanowiły inaczej, dostarczenie przedmiotu naprawy do zakładu Zleceniobiorcy i odbiór go z tego zakładu następuje na koszt i ryzyko Zleceniodawcy.
            </li>
        
            <li>
                <strong>Cena i płatność</strong>
                <br>
                <ol type="a">
                    <li>
                        Podana cena jest cena netto i dolicza się do niej podatek VAT według obowiązującej stawki.
                    </li>
                    <li>
                        Zapłata powinna nastąpić w terminie wynikającym z wystawionej faktury VAT, na rachunek bankowy Zleceniobiorcy uwidoczniony na fakturze. Za opóźnienie płatności będą naliczana ustawowe odsetki za opóźnienie.
                        O ile strony pisemnie nie postanowiły inaczej, dostarczenie przedmiotu naprawy do zakładu Zleceniobiorcy i odbiór go z tego zakładu następuje na koszt i ryzyko Zleceniodawcy.
                    </li>
                    <li>
                        Jeżeli strony w treści oferty/zlecenia nie postanowiły inaczej, płatność ma nastąpić przed odbiorem naprawianego podzespołu. Do czasu uregulowania całej należnej płatności Zleceniobiorca pozostaje właścicielem wszystkich wymienionych lub naprawionych elementów podzespołu. Zleceniobiorcy przysługuje prawa zatrzymania naprawianego podzespołu do czasu uregulowania całej należnej płatności. W razie wydania naprawionego podzespołu i przekroczenia terminu płatności, Zleceniobiorcy przysługuje wynagrodzenie za korzystania przez Zleceniodawcę z naprawionych lub wymienionych elementów podzespołu, pozostających jego własnością, w wysokości 100 zł za każdy dzień.
                    </li>
                </ol>
            </li>
        
            <li>
                <strong>Termin realizacji</strong>
                <br>
                <ol type="a">
                    <li>
                        Termin realizacji liczy się w dniach roboczych, zgodnie z przepisami kodeksu cywilnego, począwszy od dnia następującego po dniu otrzymania pisemnego Zlecenia od Zleceniodawcy, jednak nie wcześniej niż od otrzymania podzespołu, do dnia, gdy naprawiony podzespół jest gotowy do jego odbioru. Strony mogą ustalić termin początkowy uzależniony od jeszcze innego zdarzenia, np. otrzymania jakiś materiałów od Zleceniodawcy, itd. Termin jest informacyjny, jeżeli strony pisemnie nie postanowiły inaczej.
                    </li>
                    <li>
                        Termin określony w ofercie lub zaakceptowanym Zleceniu ulega przedłużeniu o czas trwania przeszkody zaistniałej na skutek okoliczności niezależnych od Zleceniobiorcy, w tym m.in. oczekiwania na dostawę od poddostawców, okoliczności siły wyższej, ograniczenia w ruchu pojazdów ciężarowych, blokady dróg, itp. Termin może ulec przedłużeniu w przypadku zaistnienia nadzwyczajnych okoliczności, takich jak niestandardowe usterki, wystąpienie nieprzewidzianych utrudnień lub inne komplikacje niezawinione przez Zleceniobiorcę.
                    </li>
                </ol>
            </li>
        
            <li>
                <strong>Warunki gwarancji</strong>
                <br>
                <ol type="a">
                    <li>
                        Na naprawiony podzespół udzielona jest gwarancja na okres 12 miesięcy od dnia wystawienia faktury VAT. Rękojmia za wady naprawy jest wyłączona. Gwarancja dotyczy jedynie naprawianego podzespołu i dotyczy tylko wad powstałych na skutek jego niewłaściwej naprawy, zastosowania niewłaściwych materiałów lub wad zastosowanych materiałów.
                    </li>
                    <li>
                        Warunki gwarancji:<br>
                        - w przypadku podzespołów pracujących w układzie zamkniętym należy naprawiać pompę hydrauliczną i silnik hydrauliczny w tym samym czasie,<br>
                        - zalać odpowiednim olejem hydraulicznym obudowę oraz zasilanie naprawionego podzespołu (pompę/silnik),<br>
                        - wymienić filtry hydrauliczne na nowe, odpowiednie<br>
                        - wyregulować zawór przeciążeniowy główny na ciśnienie nominalne, wskazane przez producenta maszyny<br>
                        - uzupełnić stan oleju hydraulicznego w zbiorniku<br>
                        - starannie i należycie odpowietrzyć podzespół przed uruchomieniem<br>
                        Powyższe czynności powinny być dokonane przez profesjonalny podmiot
                    </li>
                    <li>
                        Wadę należy zgłosić pisemnie w terminie 3 dni od jej wykrycia.
                    </li>
                    <li>
                        Uprawnienia z gwarancji wykonuje się przez dostarczenie danego podzespołu do siedziby Zleceniobiorcy jako Gwaranta celem zweryfikowania zgłoszenia i ewentualnej naprawy. Brak dostarczenia podzespołu do Gwaranta jest uważany jako cofnięcie zgłoszenia i nie obliguje Gwaranta do żadnych czynności. Dostarczony podzespół powinien być umyty i odpowiednio zapakowany oraz zabezpieczony.<br>
                        Ryzyko związane z dostawą podzespołu oraz jego koszt obciążają Zleceniodawcę.
                    </li>
                    <li>
                        W przypadku zaistnienia w przedmiocie naprawy w okresie gwarancji wady, za którą odpowiedzialność ponosi Gwarant, wada ta zostanie usunięta w terminie 14 dni. Postanowienia z pkt 5 lit. a) i b) stosuje się odpowiednio.
                    </li>
                </ol>
            </li>
        
            <li>
                <strong>Właściwość Sądu</strong>
                <br>
                Wszelkie spory wynikające z umowy strony poddają pod odpowiedni Sąd właściwości ogólnej Zleceniobiorcy.
            </li>
        
            <li>
                <strong>Postanowienia końcowe</strong>
                <br>
                <ol type="a">
                    <li>
                        Odpowiedzialność Zleceniobiorcy wobec Zleceniodawcy jest ograniczona do wysokości umówionego wynagrodzenia z tytułu wykonania naprawy/usługi serwisowej, wskazanego w umowie lub na fakturze VAT. Zleceniobiorca nie ponosi żadnej odpowiedzialności za jakąkolwiek szkodę wynikłą za straty w produkcji, utraty zysku, straty w korzystaniu, utraty kontaktów, lub jakąkolwiek inną szkodę nie bezpośrednią jakiegokolwiek rodzaju, odniesioną przez Zleceniodawcę lub jakąkolwiek inną osobę fizyczną, osobę prawną lub jednostkę organizacyjną z nim powiązaną jakimkolwiek tytułem prawnym.
                    </li>
                    <li>
                        W przypadku braku odbioru podzespołu w terminie 12 miesięcy od wystawienia faktury VAT za jego naprawę, Strony ustalają, że własność tego podzespołu przechodzi na Zleceniobiorcę, za cenę równą wartości podzespołu jako złomu metalowego. Zleceniobiorca może potrącić tą wartość z należnością przysługujących mu wobec Zleceniodawcy.
                    </li>
                    <li>
                        Przeniesienie praw lub obowiązków wynikających z umowy przez Zleceniodawcę na inny podmiot wymaga uprzedniej pisemnej zgody Zleceniobiorcy pod rygorem nieważności.
                    </li>
                </ol>
            </li>';
            
            //<p id="lastSign">
            //...............................................................<br>
            //' . $title4 . '
            //</p>

        $owuDoc .= '</ol>
        </div>';

        $htmlContent = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Content</title>
            <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 12px;
                line-height: 110%;
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: normal;
                src: url(../vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: normal;
                src: url(../vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Oblique.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: normal;
                font-weight: bold;
                src: url(../vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf) format("truetype");
            }
            @font-face {
                font-family: "DejaVu Sans";
                font-style: italic;
                font-weight: bold;
                src: url(../vendor/dompdf/dompdf/lib/fonts/DejaVuSans-BoldOblique.ttf) format("truetype");
            }
            #img {
                background-image: url("' . $img . '");
                background-repeat: no-repeat;
                background-position: center;
                background-size: contain;
                height: 100px;
                width: 133px;
            }
            input {
                margin-left: 0;
            }

            #header {
                width: 100%;
            }
            #header tr:nth-child(1) td:nth-child(1),
            #header tr:nth-child(2) td:nth-child(1) {
                text-align: left;
                width: 67%;
            }
            #header tr:nth-child(1) td:nth-child(2),
            #header tr:nth-child(2) td:nth-child(2) {
                text-align: right;
                width: 33%;
            }
            #header tr:nth-child(1) td {
                vertical-align: top;
            }
            #header tr:nth-child(2) td {
                vertical-align: top;
            }
            #header tr:nth-child(3) td {
                text-align: center;
                padding: 100px 0 50px 0;
            }
            #header #title {
                font-size: 110%;
                font-weight: bold;
            }
            #header a {
                color: inherit;
                text-decoration: none;
            }

            #products {
                width: 100%;
            }
            #products {
                border-collapse: collapse;
            }
            #products tr:nth-child(1) td {
                background-color: #EEE;
                font-weight: bold;
            }
            #products tr td {
                border: 1px solid #CCC;
                padding: 5px 10px;
            }
            #products tr td:nth-child(1) {
                width: 5%;
            }
            #products tr td:nth-child(2) {
                width: 78%;
            }
            #products tr td:nth-child(3),
            #products tr td:nth-child(4) {
                width: 17%;
            }';

            if ($method == 'doc2' || $method == 'doc3') {
                $htmlContent .= '#products tr td:nth-child(3) {
                    width: 10%;
                }';
            }

            $htmlContent .= '#products tr td:nth-child(3),
            #products tr td:nth-child(4) {
                text-align: right;
            }

            #footer {
                width: 100%;
            }
            #footer tr:nth-child(1) td {
                text-align: left;
            }
            #footer tr:nth-child(1) td p {
                float: left;
                display: inline-block;
            }
            #footer tr:nth-child(1) td p:nth-child(1) {
                width: 9%;
            }
            #footer tr:nth-child(1) td p:nth-child(2) {
                width: 91%;
            }
            #footer tr:nth-child(2) td {
                padding-top: 140px;
                text-align: right;
            }
            #footer tr:nth-child(2) td:nth-child(1) {
                text-align: left;
            }
            #footer tr:nth-child(3) td {
                text-align: right;
            }
            #mainDoc,
            #owuDoc {
                display: block;
                /*page-break-before: always;*/
                /*page-break-after: always;*/
                break-inside: avoid;
                margin: 0;
                width: 100%;
                height: 100%;
            }

            #mainDoc #nextPage {
                position: absolute;
                bottom: 0;
                right: 0;
            }

            #owuDoc h2 {
                font-size: 14px;
                width: 90%;
                margin: 0 auto;
                text-align: center;
                margin-bottom: 50px;
                margin-top: 20px;
                text-transform: uppercase;
            }
            #owuDoc ol {
                font-size: 8px;
                line-height: 100%;
                margin-left: -25px;
            }
            #owuDoc h2,
            #owuDoc p {
                line-height: 115%;
            }
            #owuDoc li {
                margin-bottom: 3px;
            }
            #owuDoc li strong {
                line-height: 250%;
            }
            #owuDoc #lastSign {
                font-size: 14px;
                margin-top: 40px;
                float: right;
                margin-right: 0;
                text-align: right;
                width: 100%;
            }
            @media print {
                @page {
                    width: 210mm;
                    min-height: 297mm;
                    size: portrait;
                }
                #footer tr:nth-child(2) td {
                    padding-top: 80px;
                }
                * {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact; /* Standard syntax - fix for title td background */
                    line-height: 150%;
                }
                #mainDoc,
                #owuDoc {
                    display: block;
                    /*page-break-before: always;*/
                    /*page-break-after: always;*/
                    break-inside: avoid;
                    margin: 0;
                    width: 100%;
                    height: 100%;
                }
                #mainDoc {
                    page-break-after: always;
                }
                #owuDoc ol {
                    font-size: 7.5px;
                }
            }
            </style>
        </head>
        <body>
            <div id="mainDoc">
            <table id="header">
                <tr>
                    <td>
                        <div id="img">
                    </td>
                    <td>'
                        . $row2['street'] . ', dnia ' . date('d.m.Y') .
                   '</td >
                </tr>
                <tr>
                    <td>
                        <strong>' . $title1 . '</strong><br>"'
                        . $row2['name'] . '"<br>'
                        . $row2['street'] . ' ' . $row2['house_no'] . $flatNo2 . '<br>'
                        . $row2['postcode'] . ' ' . $row2['town'] . '<br>'
                        . 'NIP: ' . $row2['nip'] . '<br><br>'
                        . 'Email: <a href="mailto:' . $row2['email'] . '">' . $row2['email'] . '</a><br>'
                        . 'Tel.: <a href="tel:' . $row2['phone'] . '">' . $row2['phone'] . '</a><br>'
                        . 'Tel.: <a href="tel:' . $row2['phone_additional'] . '">' . $row2['phone_additional'] . '</a><br>
                    </td>
                    <td>
                        <strong>Właściciel sprzętu</strong><br>"'
                        . $record3['name'] . '"<br>'
                        . $record3['street'] . ' ' . $record3['house_no'] . $flatNo3 . '<br>'
                        . $record3['postcode'] . ' ' . $record3['town'] . '<br>'
                        . 'NIP: ' . $record3['nip'] . '<br><br>'
                        . 'Numer klienta: ' . $record3['clientid'] . '<br>'
                        . 'Tel.: ' . $record3['phone'] . '
                    </td>
                </tr>
                <tr>
                    <td colspan="2" id="title">' . $title2 . '<br>NUMER ' . $row['orderid'] . '</td>
                </tr>
            </table>
            <table id="products">
                <tr>
                    <td>Lp</td>
                    <td>Nazwa</td>';

                    if ($method == 'doc2' || $method == 'doc3') {
                        $htmlContent .= '<td>Koszt&nbsp;netto</td>';
                    }
                    
                    $htmlContent .= '<td>Numer&nbsp;części</td>
                </tr>';
                
                $i = 1;
                foreach ($allParts as $k => $v) {
                    $htmlContent .= '<tr>
                        <td>' . $i . '</td>
                        <td>' . $v . '</td>';

                        if ($method == 'doc2') {
                            $htmlContent .= '<td>' . $vCost[$k] . ' PLN</td>';
                        } else if ($method == 'doc3') {
                            $htmlContent .= '<td>' . $rCost[$k] . ' PLN</td>';
                        }

                        $htmlContent .= '<td>' . $k . '</td>
                    </tr>';
                    $i++;
                }

            $htmlContent .= '</table>
            <table id="footer">
                <tr>
                    <td colspan="2">
                        ' . $partNote . '
                    </td>
                </tr>
                <tr>
                    <td>
                        ...............................................................<br>
                        ' . $title3 . '
                    </td>
                    <td>
                        ...............................................................<br>
                        ' . $title4 . '
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        ' . $note . '
                    </td>
                </tr>
            </table>
            </div>
            ' . $owuDoc . '
        </body>
        </html>';

        // Previously right before </table>
        //<p id="nextPage">
            //<u>Ogólne Warunki Umów</u> na kolejnej stronie &rarr;
        //</p>

        $size = 'A4';
        $orientation = 'portrait';
    }

    // PRINT
    header('Content-Type: application/json');
    echo json_encode(['html' => $htmlContent, 'path' => $path]);

    // temp: comment out
    if ($single != 'single' && $pdf == 'pdf') {
        // PDF
        $pdfGenerator->generateFromHtml($htmlContent, '../' . $path, $size, $orientation); //generateFromFile
    }
}
?>