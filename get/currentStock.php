<?php

//create a new mysql connection
$db = 'dvd_rental';
$pass = 'password';
$user = 'root';
$link = new mysqli('localhost', $user, $pass, $db);
// Check if connected
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

// Get the request method
$request_method = $_SERVER['REQUEST_METHOD'];

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET") {


    if (isset($_GET['film_id']) && !empty($_GET['film_id'])) {
        // 
        $film_id = filter_var($_GET['film_id'], FILTER_SANITIZE_NUMBER_INT);
        $film_id = $_GET['film_id'];
    } else {
        $film_id = null;
    }

    $sql = "SELECT s.store_id, COUNT(s.store_id) AS stock, a.address, c.city, co.country
    FROM `inventory` 

    JOIN store s ON inventory.store_id = s.store_id
    JOIN address a ON s.address_id = a.address_id
    JOIN city c ON a.city_id = c.city_id
    JOIN country co ON c.country_id = co.country_id

    WHERE film_id = ?

    GROUP BY store_id;";

    if ($film_id) {
        // Prepare SQL statement
        $stmt = $link->prepare($sql);
        $stmt->bind_param("i", $film_id);

        if ($stmt->execute()) {

            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            // print an error if no results
            if (empty($data)) {
                echo "No inventory found for this film ID.";
            } else {
                echo json_encode($data);
            }
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Print out any errors
        foreach ($errors as $error) {
            echo $error . '<br>';
        }
    }
}
