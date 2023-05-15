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

if ($request_method === 'GET') {

    // Allows any domain '*' for wildcard
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

    // SQL query to fetch data
    $sql = "SELECT * FROM actor";
    $result = $link->query($sql);

    $data = array();

    if ($result->num_rows > 0) {
        // Fetch the data in rows
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        //Send it to the requestee
        //Set the Content-Type header to application/json
        header('Content-Type: application/json');

        // Convert the array to JSON format and send it
        echo json_encode($data);
    } else {
        echo "0 results";
    }
} else {
    echo "This page accepts only GET requests.";
}
?>