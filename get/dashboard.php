<?php

// There are many many insights we can gain from our data, here I will pick 5 insights that I think are interesting.

// Create connection to database

//create a new mysql connection
$db = 'dvd_rental';
$pass = 'password';
$user = 'root';
$link = new mysqli('localhost', $user, $pass, $db);
// Check if connected
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

// Run this to perform data analysis on our tables in the database, tables will be populated with the results.
require '../refreshTables.php';

/******************************************************************************
 * Section: What is the "total sales" and the "number of sales" (popularity) by film category?
 * Description: 
 ******************************************************************************/

$sql = "SELECT * FROM `sales_by_film_category` ORDER BY total_sales DESC;";
$result = $link->query($sql);

if ($result) {
    $data["sales_by_film_category"] = $result->fetch_all(MYSQLI_ASSOC);

    // print an error if no results
    if (empty($data)) {
        echo "e: 0001";
    }
} else {
    echo "Error: " . $link->error;
}

/******************************************************************************
 * Section: What is the "total sales" and "total amount of sales" for each store?
 * Description: 
 ******************************************************************************/

$sql = "SELECT * FROM `sales_by_store` ORDER BY total_sales DESC;";
$result = $link->query($sql);

if ($result) {
    $data["sales_by_store"] = $result->fetch_all(MYSQLI_ASSOC);

    // print an error if no results
    if (empty($data)) {
        echo "e: 0002";
    }
} else {
    echo "Error: " . $link->error;
}

/******************************************************************************
 * Section: What is the "total sales" and the "number of sales" per week?
 * Description: 
 ******************************************************************************/

$sql = "SELECT * FROM `sales_by_week` ORDER BY week_ending DESC;";
$result = $link->query($sql);

if ($result) {
    $data["sales_by_week"] = $result->fetch_all(MYSQLI_ASSOC);

    // print an error if no results
    if (empty($data)) {
        echo "e: 0003";
    }
} else {
    echo "Error: " . $link->error;
}

/******************************************************************************
 * Section: What is the "total sales" and the "number of sales" by day?
 * Description: 
 ******************************************************************************/

$sql = "SELECT * FROM `sales_by_day`;";
$result = $link->query($sql);

if ($result) {
    $data["sales_by_day"] = $result->fetch_all(MYSQLI_ASSOC);

    // print an error if no results
    if (empty($data)) {
        echo "e: 0003";
    }
} else {
    echo "Error: " . $link->error;
}

/******************************************************************************
 * Section: What is the days it takes people to return a film?
 * Description: 
 ******************************************************************************/

$sql = "SELECT * FROM `return_times`;";
$result = $link->query($sql);

if ($result) {
    $data["return_times"] = $result->fetch_all(MYSQLI_ASSOC);

    // print an error if no results
    if (empty($data)) {
        echo "e: 0004";
    }
} else {
    echo "Error: " . $link->error;
}

// Now $data contains the results of both queries under different keys
echo json_encode($data); // Send the results as a JSON response

$link->close();
