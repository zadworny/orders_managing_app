<?php

class Database {
    private $host = 'localhost';
    private $db_name = 'hydro_dyna';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}

class DuplicateFinder {
    private $conn;
    private $table = 'clients';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findDuplicates() {
        $sql = "SELECT nip, GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ', ') AS ids, COUNT(*) as count FROM " . $this->table . " WHERE nip != '' GROUP BY nip HAVING count > 1";
    
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
    
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($results) > 0) {
                echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
                // Add LP column header
                echo "<tr><th>LP</th><th>NIP</th><th>Duplikaty</th></tr>";
    
                $lp = 1; // Initialize LP counter
                foreach ($results as $row) {
                    echo "<tr onclick='fetchDetails(\"" . htmlspecialchars($row['ids']) . "\", this)'>";
                    // Output LP counter value and increment it
                    echo "<td>" . $lp++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['nip']) . "</td>";
    
                    // Split IDs and apply condition
                    $ids = explode(', ', $row['ids']);
                    $coloredIds = array_map(function($id) {
                        if ($id > 3291) {
                            return "<span style='color: red;'>$id</span>";
                        } else {
                            return $id;
                        }
                    }, $ids);
    
                    echo "<td>" . implode(', ', $coloredIds) . "</td>";
                    echo "</tr>";
                }
    
                echo "</table>";
            } else {
                echo "No duplicated 'nip' values found.";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Usage
$database = new Database();
$db = $database->connect();

$duplicateFinder = new DuplicateFinder($db);
//$duplicateFinder->findDuplicates(); // Same as HTML below but no CSS
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicate Records</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .title {
            opacity: 0.25;
        }
        table tr:hover {
            background-color: #f2f2f2; /* Light grey, adjust the color as needed */
        }
    </style>
<script>
let lastClickedRow = null; // Global variable to remember the last clicked row

function fetchDetails(ids, clickedRow) {
    lastClickedRow = clickedRow; // Update the lastClickedRow to the current row

    const idArray = ids.split(', ');
    const queryString = idArray.map(id => `id[]=${id}`).join('&');

    fetch('fetch_details.php?' + queryString)
    .then(response => response.json())
    .then(data => {
        const detailsBox = document.getElementById('detailsBox');
        detailsBox.innerHTML = '';
        const table = document.createElement('table');
        const tbody = document.createElement('tbody');

        data.forEach(detail => {
            const row = document.createElement('tr');
            row.innerHTML = `<td><span class="title">Id:</span> ${detail.id}</td>
                             <td><span class="title">Nip:</span> ${detail.nip || ''}</td>
                             <td><span class="title">Nazwa:</span> ${detail.name}</td>
                             <td><span class="title">Adres:</span> ${detail.street || ''} ${detail.house_no || ''} ${detail.flat_no || ''}, ${detail.postcode || ''} ${detail.town || ''}</td>
                             <td><button class="deleteBtn" data-id="${detail.id}">Delete</button></td>`;
            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        detailsBox.appendChild(table);

        // Move this inside the .then() after the buttons have been added to the DOM
        document.querySelectorAll('.deleteBtn').forEach(button => {
            button.addEventListener('click', function() {
                const idToDelete = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this item?')) {
                    fetch('delete_detail.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${idToDelete}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.closest('tr').remove();
                            if (lastClickedRow) {
                                //lastClickedRow.style.opacity = "0.2";
                                lastClickedRow.style.backgroundColor = "#FEE";
                            }
                        } else {
                            alert('An error occurred while trying to delete the item.');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting detail:', error);
                        alert('An error occurred while trying to delete the item.');
                    });
                }
            });
        });
    })
    .catch(error => console.error('Error fetching details:', error));
}
</script>
</head>
<body>
    <?php $duplicateFinder->findDuplicates(); ?>
    <!-- Details Box -->
    <div id="detailsBox" style="position: fixed; bottom: 0; left: 0; width: 100%; background-color: white; overflow-y: auto; max-height: 200px;"></div>
</body>
</html>