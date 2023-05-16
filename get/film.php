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

// We use the data from the URL query to

// Get the request method 
$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method === 'GET') {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    header('Content-Type: application/json');

    /******************************************************************************
     * Section: Get a specific film from the database.
     * Description:
     ******************************************************************************/

    $film_id = $_GET['film_id'];

    if (!isset($film_id) || empty($film_id)) {
        die("No film id provided");
    }

    // Prepare the SQL statement
    $sql = "SELECT f.*, a.*, l.name AS language_name, c.name AS category_name,
        -- Stop actors from causing the query to return duplicate rows
        GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as actors

         FROM film AS f 
         JOIN film_category AS fc ON f.film_id = fc.film_id

         JOIN film_actor AS fa ON f.film_id = fa.film_id
         JOIN actor AS a ON fa.actor_id = a.actor_id

        -- Get the category name
         JOIN language AS l ON f.language_id = l.language_id
        --  Get the language name
        JOIN category AS c ON fc.category_id = c.category_id
         WHERE f.film_id = ?";

    /******************************************************************************
     * Section: Perform database operations
     * Description:
     ******************************************************************************/

    $stmt = $link->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: (" . $link->errno . ") " . $link->error);
    }

    // Bind the film_id to the placeholder
    $stmt->bind_param('i', $film_id);

    // Execute the prepared statement
    $stmt->execute();

    // Fetch the result
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
    // Converts the array to JSON format and send it to the requestee
    // echo json_encode($data);
} else {
    echo "This page accepts only GET requests.";
}
