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



    $conditions = [];
    $placeholders = [];

    //Check for keyvalue pairs in the URL. If there are none, then the query will return all films.
    //SQL attacks are prevented by using placeholders and mysqli prepared statements.

    /******************************************************************************
     * Section: Parse the array(s) from the URL query string.
     * Description: Parsing arrays from query strings 
     * 1. The implode function creates a comma separated string from an array.
     * 2. rather than a for loop we can use array_fill to create an array of placeholders.
     * 3. $conditions builds a part of the final WHERE clause
     * 4. $placeholders is an array of values that will be used to replace the placeholders(?) in the SQL query.
     * 5. Add all the placeholders (categories, ratings, etc) to the $placeholders array. We will use them when preparing! our sql statement.
     ******************************************************************************/

    // Assuming catergories is an array of strings.
    if (isset($_GET['categories']) && !empty($_GET['categories'])) {

        $categories = $_GET['categories'];
        $whereClause = implode(', ', array_fill(0, count($categories), '?'));
        $conditions[] = "c.name IN ($whereClause)";
        $placeholders = array_merge($placeholders, $categories);
    }

    // Assuming language is a single string.
    if (isset($_GET['language']) && !empty($_GET['language'])) {

        $language = $_GET['language'];
        $conditions[] = "l.name = ?";
        $placeholders[] = $language;
    }

    //Assuming actors is an array of strings and each string is separated by a space AKA %20 in the URL.
    if (isset($_GET['actors']) && !empty($_GET['actors'])) {

        $actors = $_GET['actors'];

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

    // Assuming ratings is an array of strings.
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

    /******************************************************************************
     * Section: Build the SQL query to get everything about a movie.
     * Description: 
     ******************************************************************************/

    // Get the category ID from the category table
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
        $whereClause";

    /******************************************************************************
     * Section: Allows us to sort our data by a column and direction.
     * Description: 
     ******************************************************************************/

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

    echo json_encode($data);
    // Converts the array to JSON format and send it to the requestee
    // echo json_encode($data);
} else {
    echo "This page accepts only GET requests.";
}
