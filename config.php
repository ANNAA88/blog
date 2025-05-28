<!-- <?php 
        session_start();
        // connect to database
        $conn = mysqli_connect("localhost", "root", "", "complete-blog-php",3307);

        if (!$conn) {
                die("Error connecting to database: " . mysqli_connect_error());
        }
    // define global constants
        define ('ROOT_PATH', realpath(dirname(__FILE__)));
        define('BASE_URL', 'http://localhost/complete-blog-php/');
?> -->
<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only define constants if they haven't been defined yet
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__)));
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/complete-blog-php/');
}

// Create database connection if not already created
if (!isset($conn)) {
    $conn = mysqli_connect('localhost', 'root', '', "complete-blog-php",3307);

    if (!$conn) {
        die("Database connection error: " . mysqli_connect_error());
    }
}
?>

