<?php
session_start();

// Database connection
$mysqli = new mysqli("localhost", "root", "", "assignment2");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Function to log errors
function log_error($message) {
    error_log($message, 3, 'errors.log'); // Change 'errors.log' to a desired log file path
}

// Generate a unique token and insert the rental record into the database
if (isset($_SESSION['last_uncompleted_order'])) {
    $selectedCarModel = $_SESSION['last_uncompleted_order']['car_model'];
    $quantity = $_SESSION['last_uncompleted_order']['quantity'];
    $startDate = $_SESSION['last_uncompleted_order']['start_date'];
    $endDate = $_SESSION['last_uncompleted_order']['end_date'];
    $name = $_SESSION['last_uncompleted_order']['name'];
    $email = $_SESSION['last_uncompleted_order']['email'];
    $phone = $_SESSION['last_uncompleted_order']['phone'];
    $hasLicense = $_SESSION['last_uncompleted_order']['has_license'];

    $confirmationToken = bin2hex(random_bytes(16)); // Generate a unique token

    $stmt = $mysqli->prepare("INSERT INTO rentals (car_model, quantity, start_date, end_date, name, email, phone, has_license, status, confirmation_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unconfirmed', ?)");
    $stmt->bind_param("sisssssis", $selectedCarModel, $quantity, $startDate, $endDate, $name, $email, $phone, $hasLicense, $confirmationToken);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Clear the session data
        unset($_SESSION['last_uncompleted_order']);

        // Generate the confirmation link
        $confirmationLink = "http://localhost/assign2/generate_confirmation.php?token=$confirmationToken";
        echo "Reservation placed successfully. <a href='$confirmationLink'>Click here to confirm your order</a>";
    } else {
        log_error("Error placing reservation: " . $stmt->error);
        echo "Error placing reservation.";
    }

    $stmt->close();
}

// Handle order confirmation
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $mysqli->prepare("SELECT * FROM rentals WHERE confirmation_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $rental = $result->fetch_assoc();

        // Update the car quantity in the JSON file
        $jsonFilePath = 'cars.json'; // Ensure this path is correct
        $jsonData = file_get_contents($jsonFilePath);
        if ($jsonData === false) {
            log_error("Failed to read JSON file");
            die("Error reading data file.");
        }

        $cars = json_decode($jsonData, true);
        if ($cars === null) {
            log_error("Failed to decode JSON file");
            die("Error decoding data file.");
        }

        $carUpdated = false;
        foreach ($cars as &$car) {
            if ($car['Car Model'] === $rental['car_model']) {
                if ($car['Quantity'] >= $rental['quantity']) {
                    $car['Quantity'] -= $rental['quantity'];
                    $carUpdated = true;
                } else {
                    log_error("Not enough quantity available for " . $rental['car_model']);
                    die("Not enough quantity available.");
                }
                break;
            }
        }

        if ($carUpdated) {
            $updatedJsonData = json_encode($cars, JSON_PRETTY_PRINT);
            if (file_put_contents($jsonFilePath, $updatedJsonData) === false) {
                log_error("Failed to write updated JSON data");
                die("Error writing data file.");
            } else {
                // Output the updated JSON for debugging
                echo "<h3>Updated JSON Data:</h3>";
                echo "<pre>" . htmlspecialchars($updatedJsonData) . "</pre>";
            }
        } else {
            log_error("Car model not found in JSON data: " . $rental['car_model']);
            die("Car model not found.");
        }

        // Update the rental status in the database
        $stmt = $mysqli->prepare("UPDATE rentals SET status = 'confirmed' WHERE confirmation_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Order confirmed successfully.";
        } else {
            log_error("Error confirming order: " . $stmt->error);
            echo "Error confirming order.";
        }
    } else {
        log_error("Invalid confirmation token: " . $token);
        echo "Invalid confirmation token.";
    }

    $stmt->close();
}

$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<header class="header">
    <div class="top-bar">
        <a href="index.php">
            <img src="https://raw.githubusercontent.com/Stellacho0325/VoomWombat/fe44050f2442a6373ae0aac698cbcfb40721f3fd/Photo/logo2.webp" alt="Car Rental Logo" class="logo">
        </a>
        <div class="search-container">
            <form class="d-flex" action="index.php" method="GET">
                <input class="form-control me-2" type="search" name="query" placeholder="Search" aria-label="Search" id="searchBox" onfocus="showRecentSearches()" oninput="handleInput()">
                <button class="btn btn-outline-success" type="submit">Search</button>
                <div id="recentSearches" style="display:none; position:absolute; background:white; border:1px solid #ccc; padding:10px;"></div>
            </form>
        </div>
        <a href="confirm_reservation.php" class="btn rent-button"><i class="fas fa-car-side"></i> Rent</a>
        <button class="toggle-button" onclick="toggleSidebar()">Categories</button>
    </div>
</header>
<div id="sidebar" class="sidebar">
    <a href="#" onclick="toggleSidebar()">☰ Categories</a>
    <div class="categories">
        <h3>Type</h3>
        <a href="?type=sedan">Sedan</a>
        <a href="?type=suv">SUV</a>
        <a href="?type=electric">Electric</a>
        <a href="?type=wagon">Wagon</a>
        <h3>Brand</h3>
        <a href="?brand=bmw">BMW</a>
        <a href="?brand=audi">Audi</a>
        <a href="?brand=mercedes">Mercedes</a>
        <a href="?brand=tesla">Tesla</a>
        <a href="?brand=kia">KIA</a>
        <a href="?brand=hyundai">Hyundai</a>
        <a href="?brand=genesis">Genesis</a>
        <a href="?brand=mazda">Mazda</a>
    </div>
</div>
<div class="confirmation-container">
    <div class="order-info">
        <h1>We've received your order</h1>
        <p class="footer-text">A copy of your receipt and order confirmation has been sent to your email.</p>
        <p class="footer-text">Thank you for shopping at FreshStellamall.</p>
    </div>
</div>
</body>
</html>
