<?php
if (!isset($_SESSION['login'])) {
    header("Location: login/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <link rel="stylesheet" href="style/orders.css?v=1.0.0">
    <style>
    /*body {
        transform: rotate(270deg);
        transform-origin: right top;
        width: 100vh;
        height: 100vw;
        overflow-x: visible;
    }*/
    </style>
</head>
<body id="bodyOrders">

    <div id="temp2">
        <?php echo '<strong>Dzisiaj:</strong> ' . date('d-m-Y'); ?>
    </div>
    <div id="temp">
        <a id="logoutBtn" href="login/logout.php">Wyloguj</a>
    </div>

    <!-- Orders will be loaded here dynamically -->
    <div id="flexContainer">
        <div class="orderContainers" id="ordersContainer1"></div>
        <div class="orderContainers" id="ordersContainer2"></div>
        <div class="orderContainers" id="ordersContainer3"></div>
        <div class="orderContainers" id="ordersContainer4"></div>
    </div>

    <div id="infoBar">
        <!-- dynamic info -->
    </div>

    <script src="scripts/jquery.min.js?v=1.0.0"></script>
    <script src="scripts/cookies.js?v=1.0.1"></script>
    <script src="scripts/orders.js?v=1.0.1"></script>
    <script src="scripts/admin.js?v=1.0.7"></script>
    <script>
        fetchOrders();
        //setInterval(function() {fetchOrders('auto');}, 3 * 1000); // Refresh every 60 seconds
    </script>
</body>
</html>
