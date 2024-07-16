<?php
session_start();

// Load car data from JSON file
$jsonData = file_get_contents('cars.json');
$cars = json_decode($jsonData, true);

// Handle search query
$searchedCars = [];
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = strtolower($_GET['query']);
    // Filter cars matching the search query
    foreach ($cars as $car) {
        if (strpos(strtolower($car['Car Model']), $search_query) !== false) {
            $searchedCars[] = $car;
        }
    }
} else {
    $searchedCars = $cars; // No search query provided, show all cars
}

// Initialize or update recent searches array
if (!isset($_SESSION['recent_searches'])) {
    $_SESSION['recent_searches'] = [];
}

if (!empty($_GET['query'])) {
    array_unshift($_SESSION['recent_searches'], $_GET['query']); // Add to the front
    $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 5); // Keep only the last 5 searches
}

require('suggest.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
    <div class="top-bar">
        <a href="index.php">
            <img src="https://raw.githubusercontent.com/Stellacho0325/VoomWombat/fe44050f2442a6373ae0aac698cbcfb40721f3fd/Photo/logo2.webp" alt="Car Rental Logo" class="logo">
        </a>
        <nav class="navbar navbar-expand-sm navbar-light bg-light">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <form class="d-flex" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="query" placeholder="Search" aria-label="Search" id="searchBox" onfocus="showRecentSearches()" oninput="handleInput()">
                    <div id="recentSearches" style="display:none; position:absolute; background:white; border:1px solid #ccc; padding:10px;">
                        <!-- Recent searches will be populated here -->
                    </div>
                    <script>
                    function showRecentSearches() {
                        const searchBox = document.getElementById('searchBox');
                        const recentSearchesContainer = document.getElementById('recentSearches');
                        if (!searchBox.value) {
                            let recentSearchesHTML = '';
                            const recentSearches = <?php echo json_encode($_SESSION['recent_searches']); ?>;
                            recentSearches.forEach(search => {
                                recentSearchesHTML += `<div onclick="setSearch('${search}')">${search}</div>`;
                            });
                            recentSearchesContainer.innerHTML = recentSearchesHTML;
                            recentSearchesContainer.style.display = 'block';
                        }
                    }
                    function handleInput() {
                        const searchBox = document.getElementById('searchBox');
                        const recentSearchesContainer = document.getElementById('recentSearches');
                        if (!searchBox.value) {
                            showRecentSearches();
                        } else {
                            recentSearchesContainer.style.display = 'none';
                        }
                    }
                    function setSearch(value) {
                        const searchBox = document.getElementById('searchBox');
                        searchBox.value = value;
                        searchBox.form.submit(); // Optionally submit the form immediately after setting the value
                    }
                    </script>
                     <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
            </div>
        </nav>
        <div class="basket">
          <a href="cart.php">
            <span class="cart-icon">&#x1F6D2;</span> Cart
          </a>
        </div>
    </div>
</header>
<main class="main-content">
    <section class="search-results">
        <h2>Search Results</h2>
        <div class="items-grid">
            <?php if (!empty($searchedCars)): ?>
                <?php foreach ($searchedCars as $car): ?>
                    <div class="item">
                        <img src="<?= htmlspecialchars($car["Images"]) ?>" alt="<?= htmlspecialchars($car["Car Model"]) ?>">
                        <p class="item-name"><?= htmlspecialchars($car["Car Model"]) ?></p>
                        <p class="item-price">$<?= htmlspecialchars($car["Price/Day"]) ?> per day</p>
                        <p class="item-stock"><?= $car["Quantity"] > 0 ? "Available" : "Not available" ?></p>
                        <form method="post" action="reservation.php">
                            <input type="hidden" name="car_model" value="<?= htmlspecialchars($car["Car Model"]) ?>">
                            <button type="submit">Rent</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No results found.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
