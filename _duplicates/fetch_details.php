<?php

// Database connection parameters
$host = 'localhost';
$dbName = 'hydro_dyna';
$username = 'root';
$password = 'root'; // Use the actual password for your database

// Create a PDO instance as db connection
try {
    $db = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Could not connect to the database $dbName :" . $e->getMessage());
}

// Retrieve IDs from the query string and filter them as integers
$ids = array_map('intval', $_GET['id'] ?? []);

// Check if there are any IDs to search for
if (!empty($ids)) {
    // Create placeholders for prepared statement
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Prepare SQL statement
    $sql = "SELECT * FROM clients WHERE id IN ($placeholders)";
    $stmt = $db->prepare($sql);

    // Execute with the array of IDs
    $stmt->execute($ids);

    // Fetch all the matching records
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    // No IDs provided
    echo json_encode(['error' => 'No IDs provided']);
}

