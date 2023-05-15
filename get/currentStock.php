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

    if ($film_id) {
        // Prepare SQL statement
        $stmt = $link->prepare("SELECT store_id, COUNT(store_id) FROM `inventory` WHERE film_id = ? GROUP BY store_id;");
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
