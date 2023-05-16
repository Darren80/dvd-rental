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

// When the method is PUT then that means we will be updating an existing film in the database.
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
    // DUMP the data from the request body into a variable
    parse_str(file_get_contents('php://input'), $_PUT);
    var_dump($_PUT); //$_PUT contains put fields 
}

// When the method is POST then that means will will be adding a new film to the database.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo $_PUT;
    $errors = [];

    // Sanitize input when the incoming data is meant to be a string
    // Use a special php functin called filter_var when data is a integer or float, this is done to prevent SQL injection.
    // If statements are used to check if the data is empty or not AND to check if the data is valid or not.

    // Title
    $title = sanitize_input($_POST["title"]);
    if (empty($title)) {
        $errors[] = "Title is required";
    }

    // Description
    $description = sanitize_input($_POST["description"]);
    if (empty($description)) {
        $errors[] = "Description is required";
    } else if (strlen($description) > 1000) {
        $errors[] = "Description cannot exceed 1000 characters";
    }

    // Release Year
    $release_year = filter_var($_POST["release_year"], FILTER_SANITIZE_NUMBER_INT);
    if (empty($release_year) || $release_year < 1800 || $release_year > 2099) {
        $errors[] = "Invalid release year";
    }

    // Language ID
    $language_id = filter_var($_POST["language_id"], FILTER_SANITIZE_NUMBER_INT);
    if (empty($language_id)) {
        $errors[] = "Language ID is required";
    }

    // Rental Duration
    $rental_duration = filter_var($_POST["rental_duration"], FILTER_SANITIZE_NUMBER_INT);
    if (empty($rental_duration) || $rental_duration < 1 || $rental_duration > 365) {
        $errors[] = "Invalid rental duration";
    }

    // Rental Rate
    $rental_rate = filter_var($_POST["rental_rate"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if (empty($rental_rate) || $rental_rate < 0 || $rental_rate > 99.99) {
        $errors[] = "Invalid rental rate";
    }

    // Film Length
    $length = filter_var($_POST["length"], FILTER_SANITIZE_NUMBER_INT);
    if (empty($length) || $length < 1 || $length > 600) {
        $errors[] = "Invalid film length";
    }

    // Replacement Cost
    $replacement_cost = filter_var($_POST["replacement_cost"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if (empty($replacement_cost) || $replacement_cost < 0 || $replacement_cost > 99.99) {
        $errors[] = "Invalid replacement cost";
    }

    // Rating
    $rating = sanitize_input($_POST["rating"]);
    if (empty($rating) || !in_array($rating, ["G", "PG", "PG-13", "R", "NC-17"])) {
        $errors[] = "Invalid rating";
    }

    // Special Features
    $special_features = sanitize_input($_POST["special_features"]);
    if (empty($special_features)) {
        $errors[] = "Special features are required";
    }

    // Other table variables
    $actor_id = filter_var($_POST["actors_id"], FILTER_SANITIZE_NUMBER_INT);
    $category_id = filter_var($_POST["category_id"], FILTER_SANITIZE_NUMBER_INT);

    if (empty($actor_id) || empty($category_id)) {
        $errors[] = "Actor ID and Category ID are required";
    }

    // If there are no errors, insert the data into the database
    if (empty($errors)) {
        $sql = "INSERT INTO film 
        (title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        // Prepare SQL statement
        $stmt = $link->prepare($sql);
        // Bind parameters to the prepared statement
        $stmt->bind_param("ssiiiiiiss", $title, $description, $release_year, $language_id, $rental_duration, $rental_rate, $length, $replacement_cost, $rating, $special_features);

        // Execute the prepared statement
        if ($stmt->execute()) {
            echo "New record created successfully in the film table";
            //Get the last inserted id
            $film_id = $link->insert_id;
    
            //Insert into the film_actor table
            $stmt = $link->prepare("INSERT INTO film_actor (film_id, actor_id) VALUES (?, ?)"); // assuming I have actor_id.
            $stmt->bind_param("ii", $film_id, $actor_id);
    
            if ($stmt->execute()) {
                echo "New record created successfully in the film_actor table";
            } else {
                echo "Error: " . $stmt->error;
            }
    
            //Insert into the film_category table
            $stmt = $link->prepare("INSERT INTO film_category (film_id, category_id) VALUES (?, ?)"); // assuming I have category_id.
            $stmt->bind_param("ii", $film_id, $category_id);
    
            if ($stmt->execute()) {
                echo "New record created successfully in the film_category table";
            } else {
                echo "Error: " . $stmt->error;
            }
    
            //Insert into the film_description table
            $stmt = $link->prepare("INSERT INTO film_text (film_id, title, description) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $film_id, $title, $description);
    
            if ($stmt->execute()) {
                echo "New record created successfully in the film_text table";
            } else {
                echo "Error: " . $stmt->error;
            }
    
        } else {
            echo "Error: " . $stmt->error;
        }
    
        $stmt->close();
    } else {
        foreach ($errors as $error) {
            echo $error . '<br>';
        }
    }
}

$link->close();

function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
