<?php
session_start();

$jsonData = file_get_contents('cars.json');
$cars = json_decode($jsonData, true);

$selectedCarModel = $_POST['car_model'] ?? $_SESSION['last_uncompleted_order']['car_model'] ?? null;

$selectedCar = null;
if ($selectedCarModel) {
    foreach ($cars as $car) {
        if ($car['Car Model'] === $selectedCarModel) {
            $selectedCar = $car;
            $_SESSION['last_uncompleted_order']['car_model'] = $selectedCarModel;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $_SESSION['last_uncompleted_order'] = [
        'car_model' => $selectedCarModel,
        'quantity' => $_POST['quantity'] ?? 1,
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'has_license' => isset($_POST['has_license'])
    ];

    header("Location: generate_confirmation.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Reservation</title>
    <link rel="stylesheet" href="reservation.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitButton = document.querySelector('#submit-button');
            const emailInput = document.querySelector('#email');
            const phoneInput = document.querySelector('#phone');
            const allInputs = form.querySelectorAll('input[required]');

            function validateEmail(email) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailPattern.test(email);
            }

            function validatePhoneNumber(phone) {
                const phonePattern = /^\d{10}$/; // Adjust pattern based on your requirement
                return phonePattern.test(phone);
            }

            function validateForm() {
                const isEmailValid = validateEmail(emailInput.value);
                const isPhoneValid = validatePhoneNumber(phoneInput.value);
                let areAllFieldsFilled = Array.from(allInputs).every(input => input.value.trim() !== '');

                const isFormValid = isEmailValid && isPhoneValid && areAllFieldsFilled;

                submitButton.disabled = !isFormValid;
                if (isFormValid) {
                    submitButton.classList.add('enabled');
                    submitButton.classList.remove('disabled');
                } else {
                    submitButton.classList.remove('enabled');
                    submitButton.classList.add('disabled');
                }

                return isFormValid;
            }

            function calculateCost() {
                const pricePerDay = parseFloat(document.getElementById('pricePerDay').textContent);
                const quantity = parseInt(document.getElementById('quantity').value);
                const startDate = new Date(document.getElementById('start_date').value);
                const endDate = new Date(document.getElementById('end_date').value);
                const timeDiff = endDate - startDate;
                const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

                if (days > 0 && quantity > 0) {
                    const totalCost = pricePerDay * quantity * days;
                    document.getElementById('totalCost').textContent = totalCost.toFixed(2);
                } else {
                    document.getElementById('totalCost').textContent = '0';
                }
            }

            document.getElementById('quantity').addEventListener('change', calculateCost);
            document.getElementById('start_date').addEventListener('change', calculateCost);
            document.getElementById('end_date').addEventListener('change', calculateCost);

            form.addEventListener('input', validateForm);
        });
    </script>
</head>
<body>
<header class="header">
    <div class="top-bar">
        <a href="index.php">
            <img src="https://raw.githubusercontent.com/Stellacho0325/VoomWombat/fe44050f2442a6373ae0aac698cbcfb40721f3fd/Photo/logo2.webp" alt="Car Rental Logo" class="logo">
        </a>
    </div>
</header>
<main class="main-content" style="margin-left: 60px;">
    <h1>Car Reservation</h1>
    <form method="post" action="confirm_reservation.php">
        <input type="hidden" name="car_model" value="<?= htmlspecialchars($selectedCarModel) ?>">
        <p>Model: <?= htmlspecialchars($selectedCarModel) ?></p>
        <?php if ($selectedCar && isset($selectedCar['Images'])): ?>
            <img src="<?= htmlspecialchars($selectedCar['Images']) ?>" alt="Car Image" style="width: 300px;">
        <?php endif; ?>
        <p>Price per Day: $<span id="pricePerDay"><?= htmlspecialchars($selectedCar['Price/Day'] ?? '0') ?></span></p>
        
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="quantity" value="1" min="1">
        
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>
        
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>
        
        <p>Total Cost: $<span id="totalCost">0</span></p>

        <label for="name">Full Name: *required</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required>

        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" required>

        <label for="has-license">Valid Driver’s License:</label>
        <input type="checkbox" id="has-license" name="has_license" required>
        
        <button type="submit" id="submit-button" class="disabled" disabled>Submit Reservation</button>
        <button type="button" id="cancel-button" onclick="window.location.href='index.php';">Cancel</button>
    </form>
</main>
</body>
</html>


