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

// Update the total_sales column in the sales_by_film_category table

/******************************************************************************
 * Section: What is the "total sales" and the "number of sales" (popularity) by film category?
 * Description: 
 ******************************************************************************/

// DELETE the table
$sql = "TRUNCATE TABLE sales_by_film_category;";
if (!$link->query($sql)) {
    echo "Error: " . $link->error;
}

$sql = "INSERT INTO
    sales_by_film_category (category_id, total_count, total_sales)
SELECT 
    fc.category_id, COUNT(fc.category_id), SUM(fc.category_id)
FROM 
    rental r
JOIN 
    inventory i ON r.inventory_id = i.inventory_id
JOIN 
    film_category fc ON i.film_id = fc.film_id
GROUP BY 
    fc.category_id;";

// Execute the query
if (!$link->query($sql) === TRUE) {
    echo "Error inserting record: " . $link->error;
}

/******************************************************************************
 * Section: What is the total sales of each store?
 * Description: 
 ******************************************************************************/

// DELETE the table
$sql = "TRUNCATE TABLE sales_by_store;";
if (!$link->query($sql)) {
    echo "Error: " . $link->error;
}

$sql = "INSERT INTO
    sales_by_store (store, store_address, manager, manager_name, total_count, total_sales)
SELECT
    s.store_id, a.address,
    s.manager_staff_id, CONCAT(st.first_name, ' ', st.last_name),
    COUNT(s.store_id), SUM(f.rental_rate) 
FROM
    rental 
JOIN
    inventory i ON rental.inventory_id = i.inventory_id 
JOIN
    store s ON i.store_id = s.store_id
JOIN
    -- Get the store address.
    address a ON s.address_id = a.address_id
JOIN
    -- Get the manager's full name. 
    staff st ON s.address_id = a.address_id
JOIN
    -- Get the film rental rate.
    film f ON i.film_id = f.film_id
GROUP BY
    s.store_id";

// Execute the query
if (!$link->query($sql) === TRUE) {
    echo "Error finding record: " . $link->error;
}

/******************************************************************************
 * Section: What is the number of sales per week for the last 5 weeks?
 * Description: 
 ******************************************************************************/

// DELETE the table
$sql = "TRUNCATE TABLE sales_by_week;";
if (!$link->query($sql)) {
    echo "Error: " . $link->error;
}

$sql = "INSERT INTO 
    sales_by_week (week_beginning, week_ending, rental_count)
SELECT 
    DATE_SUB(rental_date, INTERVAL WEEKDAY(rental_date) DAY) AS week_beginning, 
    DATE_ADD(rental_date, INTERVAL (6 - WEEKDAY(rental_date)) DAY) AS week_ending, 
    COUNT(*) as rental_count
FROM 
    rental 
GROUP BY 
    WEEK(rental_date, 1);";

// Execute the query
if (!$link->query($sql) === TRUE) {
    echo "Error finding record: " . $link->error;
}

/******************************************************************************
 * Section: Give values for the number of days before rentals are returned?
 * Description: 
 ******************************************************************************/

// DELETE the table
$sql = "TRUNCATE TABLE return_times;";
if (!$link->query($sql)) {
    echo "Error: " . $link->error;
}

$sql = "INSERT INTO
    return_times (num_days, count)
SELECT
    DATEDIFF(return_date, rental_date) AS num_days, COUNT(*) AS count
FROM
    rental
GROUP BY
    num_days;";

// Execute the query
if (!$link->query($sql) === TRUE) {
    echo "Error finding record: " . $link->error;
}

// $link->close();

$sql = "SELECT DATE(rental_date) AS day, COUNT(*) as sales_count
FROM rental
GROUP BY DATE(rental_date)  
ORDER BY `day` DESC;";