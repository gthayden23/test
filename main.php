<?php
session_start();
require 'dpprog2.php';

$dbh = connectDB();
// Handle password change
if (isset($_POST['change']) == 'change') {
    if(isset($_SESSION['customer_id'])){
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if new passwords match
    if ($newPassword !== $confirmPassword) {
        echo "Passwords do not match!";
    }

    // Get the current password from the database
    $userId = $_SESSION['customer_id'];
    $stmt = $dbh->prepare("SELECT password FROM customers WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (hash_equals($user['password'], hash('sha256', $currentPassword))) {
        // Update password
        $newPasswordHash = hash('sha256', $newPassword);
        $updateStmt = $dbh->prepare("UPDATE customers SET password = :new_password WHERE id = :id");
        $updateStmt->bindParam(':new_password', $newPasswordHash);
        $updateStmt->bindParam(':id', $userId);
        $updateStmt->execute();

        echo "Password changed successfully!";
    } else {
        echo "Current password is incorrect!";
    }
}else{
    echo "you must be logged in to change your password";
    
}
}

// Display categories
$stmt = $dbh->query("SELECT * FROM Category");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Categories</h2>";
echo "<ul>";
foreach ($categories as $category) {
    echo "<li><a href='main.php?category_id=" . $category['category_id'] . "'>" . $category['category_name'] . "</a></li>";
}
echo "</ul>";

if (isset($_GET['category_id'])) {
    $categoryId = $_GET['category_id'];

    // Fetch products by category
    $stmt = $dbh->prepare("SELECT * FROM products WHERE category_id = :category_id AND discontinued = 0");
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Products</h2>";
    echo "<ul>";
    foreach ($products as $product) {
        echo "<li>";
        echo "<img src='" . $product['product_image'] . "' alt='" . $product['product_name'] . "' />";
        echo "<p>" . $product['product_description'] . "</p>";
        echo "<p>Price: $" . $product['price'] . "</p>";

        if (isset($_SESSION['customer_id'])) {
            // Display "Add to Cart" button for logged-in users
            echo "<form action='' method='POST'>
                    <input type='hidden' name='product_id' value='" . $product['product_id'] . "'>
                    <input type='number' name='quantity' value='1' min='1'>
                    <button type='submit' name='add_to_cart'>Add to Cart</button>
                  </form>";
        } else {
            echo "<p><i>Please log in to add products to your cart.</i></p>";
        }

        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Select a category to view products.</p>";
}

// Check if the "Add to Cart" button is clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['customer_id'])) {
        $customerId = $_SESSION['customer_id'];
        $productId = $_POST['product_id'];
        $quantity = $_POST['quantity'];

        // Call the addToCart function
        $message = addToCart($dbh, $customerId, $productId, $quantity);
        echo "<p>$message</p>";
    } else {
        echo "<p>You must be logged in to add items to your cart.</p>";
    }
}
?>


<?php
// Check if the user is logged in
$is_logged_in = isset($_SESSION['customer_id']); // Replace with your login logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditional Content</title>
</head>
<body>
    <?php if (!$is_logged_in): ?>
        <!-- Show login button when not logged in -->
        <form action="login.php" method="get">
            <button type="submit">Go to Login</button>
        </form>
    <?php else: ?>
        <!-- Show other content when logged in -->
        <form action="view.php" method="get">
            <button type="submit">View Orders</button>
        </form>

        <form action="cart.php" method="get">
        <button type="submit">shopping cart</button>
        </form>

        <form method="POST" action="logout.php">
            <button type="submit">Logout</button>
        </form>

        <h2>Change Password</h2>
        <form action="" method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required><br>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required><br>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required><br>

            <button type="submit" name='change'>Change Password</button>
        </form>
    <?php endif; ?>
</body>
</html>
