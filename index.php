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

    // Allow any domain (use '*' for a wildcard)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    //Set the Content-Type header to application/json
    header('Content-Type: application/json');

    /******************************************************************************
     * Section: Parse the array from the URL query string.
     * Description: for each element in $categories, we will generate an sql string and placeholder variables.
     ******************************************************************************/

    $conditions = [];
    $placeholders = [];

    //Check for keyvalue pairs in the URL, - Prevent SQL attacks by using placeholders and mysqli prepared statements

    // Assuming catergories is an array of strings.
    if (isset($_GET['categories']) && !empty($_GET['categories'])) {

        $categories = $_GET['categories'];
        $whereClause = implode(', ', array_fill(0, count($categories), '?'));
        $conditions[] = "c.name IN ($whereClause)";
        $placeholders = array_merge($placeholders, $categories);

    }

    // Assuming language is a string.
    if (isset($_GET['language']) && !empty($_GET['language'])) {

        $language = $_GET['language'];
        $conditions[] = "l.name = ?";
        $placeholders[] = $language;

    }

    //Assuming actors is an array of strings and each string separated by a space AKA %20 in the URL.
    if (isset($_GET['actors']) && !empty($_GET['actors'])) {

        $actors = $_GET['actors'];
        print_r($actors);

        // Split the actor's first name and last name into separate variables.
        $actorConditions = [];
        foreach ($actors as $actor) {
            // Split the actor name into first name and last name
            list($firstName, $lastName) = explode(' ', $actor);

            // Generate the WHERE clause for this actor
            $actorConditions[] = "(a.first_name = ? AND a.last_name = ?)";
            $placeholders[] = $firstName;
            $placeholders[] = $lastName;
        }

        $whereClause = implode(' OR ', $actorConditions);
        $conditions[] = "($whereClause)";
    }

    if (isset($_GET['ratings']) && !empty($_GET['ratings'])) {

        $ratings = $_GET['ratings'];
        $whereClause = implode(', ', array_fill(0, count($ratings), '?'));
        $conditions[] = "f.rating IN ($whereClause)";
        $placeholders = array_merge($placeholders, $ratings);
    }


    // Build the final WHERE clause
    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }



    // Get the category ID from the category table
    $sql = "SELECT f.*, a.*
        FROM film AS f 
        JOIN film_category AS fc ON f.film_id = fc.film_id
        JOIN category AS c ON fc.category_id = c.category_id

        JOIN film_actor AS fa ON f.film_id = fa.film_id
        JOIN actor AS a ON fa.actor_id = a.actor_id

        JOIN language AS l ON f.language_id = l.language_id
        $whereClause";



    if (isset($_GET['sortby']) && !empty($_GET['sortby'])) {

        $sortby = $_GET['sortby'];

        // Predefing the allowed sort columns and directions to prevent SQL injection attacks and invalid queries from being executed.
        // Define the allowed column names for sorting
        $allowedSortColumns = ['rating', 'rental_rate', 'length', 'rental_duration'];

        // Define the allowed sort directions
        $allowedSortDirections = ['asc', 'desc'];

        // Split sortby into column and direction
        list($sortColumn, $sortDirection) = explode(',', $sortby);

        // Check if the provided sortby column is allowed
        // Check if the provided sortby column and sort direction are allowed
        if (in_array($sortColumn, $allowedSortColumns) && in_array($sortDirection, $allowedSortDirections)) {
            // Append the ORDER BY clause to the query
            $orderByClause = "ORDER BY " . $sortColumn . " " . $sortDirection;
            // Then append $orderByClause to the final SQL query
            $sql .= " " . $orderByClause;
        }
    }


    echo $sql;

    /******************************************************************************
     * Section: Here we will use Prepared SQL statements to prevent SQL attacks.
     * Description: 
     ******************************************************************************/

    // Prepare the SQL query using mysqli
    $stmt = $link->prepare($sql);

    if (!$stmt) {
        // If the prepared statement failed, output the error message
        die("Prepare failed: (" . $link->errno . ") " . $link->error);
    }

    if (!empty($placeholders)) {
        // Binds the parameters to the placeholders
        $params = str_repeat('s', count($placeholders));
        $stmt->bind_param($params, ...$placeholders);
    }

    // Executes the prepared statement
    $stmt->execute();

    // Fetches the result
    $result = $stmt->get_result();
    $data = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } else {
        echo "0 results";
    }

    /******************************************************************************
     * Section: Here we will use query the database for the tables we need.
     * Description: 
     ******************************************************************************/

    print_r($data);
    // Converts the array to JSON format and send it to the requestee
    // echo json_encode($data);
} else {
    echo "This page accepts only GET requests.";
}
