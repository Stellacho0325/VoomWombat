<?php
session_start();
// Load car data from JSON file
$jsonData = file_get_contents('cars.json');
$cars = json_decode($jsonData, true);

// Filter cars based on query and category parameters
$searchedCars = [];
$typeFilter = $_GET['type'] ?? '';
$brandFilter = $_GET['brand'] ?? '';

foreach ($cars as $car) {
    $typeMatch = empty($typeFilter) || strtolower($car['Type']) === strtolower($typeFilter);
    $brandMatch = empty($brandFilter) || strtolower($car['Brand']) === strtolower($brandFilter);
    if ($typeMatch && $brandMatch && isset($_GET['query']) && !empty($_GET['query'])) {
        $search_query = strtolower($_GET['query']);
        if (strpos(strtolower($car['Car Model']), $search_query) !== false) {
            $searchedCars[] = $car;
        }
    } elseif ($typeMatch && $brandMatch) {
        $searchedCars[] = $car;
    }
}

// Update recent searches array
if (!isset($_SESSION['recent_searches'])) {
    $_SESSION['recent_searches'] = [];
}
if (!empty($_GET['query'])) {
    array_unshift($_SESSION['recent_searches'], $_GET['query']);
    $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 5);
}
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
                <div id="recentSearches" style="display:none; position:absolute; background:white; border:1px solid #ccc; padding:10px;">
                </div>
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
<main class="main-content" style="margin-left: 60px;">
    <section class="search-results">
        <h2>All Products</h2>
        <div class="items-grid">
            <?php if (!empty($searchedCars)): ?>
                <?php foreach ($searchedCars as $car): ?>
                    <div class="item">
                        <img src="<?= htmlspecialchars($car["Images"]) ?>" alt="<?= htmlspecialchars($car["Car Model"]) ?>" class="item-image">
                        <div class="item-details">
                            <h3 class="item-name"><?= htmlspecialchars($car["Car Model"]) ?></h3>
                            <ul class="item-specs">
                                <li><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($car["Fuel Type"]) ?></li>
                                <li><i class="fas fa-users"></i> <?= htmlspecialchars($car["Seats"]) ?> seats</li>
                                <li><i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($car["Mileage"]) ?> km/L</li>
                            </ul>
                            <p class="item-price">$<?= htmlspecialchars($car["Price/Day"]) ?> per day</p>
                            <p class="item-availability"><?= $car["Quantity"] > 0 ? "Available" : "Not available" ?></p>
                            <form method="post" action="confirm_reservation.php">
                                <input type="hidden" name="car_model" value="<?= htmlspecialchars($car["Car Model"]) ?>">
                                <button type="submit" class="rent-button"><i class="fas fa-car-side"></i> Rent</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No results found.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
<script defer>
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    // Check if the sidebar style.left is exactly '-250px' or has not been set (could be an empty string initially)
    if (sidebar.style.left === '-250px' || sidebar.style.left === '') {
        sidebar.style.left = '0px'; // Move sidebar into view
    } else {
        sidebar.style.left = '-250px'; // Hide the sidebar
    }
}
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

function fetchSuggestions() {
    const searchBox = document.getElementById('searchBox');
    const recentSearchesContainer = document.getElementById('recentSearches');

    if (!searchBox.value) {
        showRecentSearches();
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'suggest.php?query=' + encodeURIComponent(searchBox.value));
    xhr.onload = function() {
        if (xhr.status === 200) {
            const suggestions = JSON.parse(xhr.responseText);
            let suggestionsHTML = '';
            suggestions.forEach(function(suggestion) {
                suggestionsHTML += `<div onclick="setSearch('${suggestion}')">${suggestion}</div>`;
            });
            recentSearchesContainer.innerHTML = suggestionsHTML;
            recentSearchesContainer.style.display = 'block';
        } else {
            recentSearchesContainer.style.display = 'none';
        }
    };
    xhr.send();
}

document.getElementById('searchBox').oninput = fetchSuggestions;
</script>
</body>
</html>
