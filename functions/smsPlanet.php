<?php
require '../vendor/autoload.php';

use SMSPLANET\PHP\Client;

$client = new Client([
    'key' => '8b9f55e6-2902-42d1-a8e9-6ec561fd9a9e',
    'password' => 'Smsplanet112!'
]);


/*$smsFrom = 'Hydro-Dyna';
$smsTo = '792396769';
$smsMsg = 'Twoje zlecenie o numerze 10827 zostało przyjęte do realizacji. Sprawdź status na hydo-dyna.pl/status. Nie odpowiadaj na tę wiadomość.';

$message_id = $client->sendSimpleSMS([
    'from' => $smsFrom,
    'to' => $smsTo,
    'msg' => $smsMsg,
    //'test' => 1
]);
echo $message_id;*/
?>