<?php
session_start();
header('Content-Type: application/json');

// Load car data from JSON file
$jsonData = file_get_contents('cars.json');
$cars = json_decode($jsonData, true);

$searchQuery = strtolower($_GET['query'] ?? '');
$suggestions = [];


foreach ($cars as $car) {
    // Match and collect car types
    if (strpos(strtolower($car['Type']), $searchQuery) !== false) {
        $typeSuggestions[] = $car['Car Model'];  // Collecting models under the matched type
    }
    // Match and collect car models
    if (strpos(strtolower($car['Car Model']), $searchQuery) !== false) {
        $modelSuggestions[] = $car['Car Model'];
    }
}

// If the primary search is for type, prioritize type suggestions
$suggestions = !empty($typeSuggestions) ? $typeSuggestions : $modelSuggestions;

// Randomize and limit the output to 6 recommendations
shuffle($suggestions);
$suggestions = array_slice($suggestions, 0, 6);

echo json_encode($suggestions);
?>