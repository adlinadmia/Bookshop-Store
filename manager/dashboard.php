<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];


/* =========================
   TOTAL PRODUCTS
========================= */

$productQuery = "SELECT COUNT(*) AS total FROM products";
$productResult = $conn->query($productQuery);

$totalProducts = 0;

if ($productResult) {
    $row = $productResult->fetch_assoc();
    $totalProducts = $row['total'];
}


/* =========================
   TOTAL STOCK
========================= */

$stockQuery = "
    SELECT COALESCE(SUM(stock), 0) AS total
    FROM products
";

$stockResult = $conn->query($stockQuery);

$totalStock = 0;

if ($stockResult) {
    $row = $stockResult->fetch_assoc();
    $totalStock = $row['total'];
}


/* =========================
   ACTIVE PROMOTIONS
========================= */

$promotionQuery = "
    SELECT COUNT(*) AS total
    FROM promotions
    WHERE status = 'active'
    AND CURDATE() BETWEEN start_date AND end_date
";

$promotionResult = $conn->query($promotionQuery);

$activePromotions = 0;

if ($promotionResult) {
    $row = $promotionResult->fetch_assoc();
    $activePromotions = $row['total'];
}


/* =========================
   TOTAL SALES
========================= */

$salesQuery = "
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM sales
";

$salesResult = $conn->query($salesQuery);

$totalSales = 0;

if ($salesResult) {
    $row = $salesResult->fetch_assoc();
    $totalSales = $row['total'];
}


/* =========================
   RECENT PRODUCTS
========================= */

$productsQuery = "
    SELECT
        id,
        barcode,
        product_name,
        category,
        price,
        stock
    FROM products
    ORDER BY id DESC
    LIMIT 10
";

$productsResult = $conn->query($productsQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manager Dashboard | Bookshop Store</title>


<style>

/* =========================
   GENERAL
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4eee3;
    color: #1b2a3a;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;

    left: 0;
    top: 0;

    width: 245px;
    height: 100vh;

    background: #1b2a3a;

    color: white;

    padding: 28px 16px;

    display: flex;
    flex-direction: column;

    z-index: 1000;
}

.logo {
    padding: 0 10px;

    margin-bottom: 38px;
}

.logo h2 {
    font-family: Georgia, serif;

    font-size: 22px;

    color: #f4eee3;
}

.logo p {
    margin-top: 6px;

    font-size: 11px;

    letter-spacing: 1.5px;

    text-transform: uppercase;

    color: #b8c1ca;
}


/* =========================
   MENU
========================= */

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 7px;
}

.menu a {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 14px;

    color: #cbd3da;

    text-decoration: none;

    border-radius: 6px;

    font-size: 14px;

    transition: 0.2s;
}

.menu a:hover {
    background: #2c3f54;

    color: white;
}

.menu a.active {
    background: #8a3324;

    color: white;

    font-weight: bold;
}

.menu-icon {
    width: 22px;

    text-align: center;

    font-size: 16px;
}


/* =========================
   LOGOUT
========================= */

.logout {
    margin-top: auto;
}

.logout a {
    display: flex;

    justify-content: center;

    align-items: center;

    gap: 8px;

    padding: 12px;

    background: #8a3324;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-size: 14px;

    transition: 0.2s;
}

.logout a:hover {
    background: #a9402f;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;

    padding: 32px;
}


/* =========================
   TOP HEADER
========================= */

.header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}

.header h1 {
    font-family: Georgia, serif;

    font-size: 30px;

    color: #1b2a3a;
}

.welcome {
    margin-top: 7px;

    color: #6d7378;

    font-size: 14px;
}

.manager-badge {
    padding: 10px 16px;

    background: white;

    border: 1px solid rgba(27,42,58,0.12);

    border-radius: 6px;

    font-size: 13px;

    font-weight: bold;

    color: #8a3324;
}


/* =========================
   STAT CARDS
========================= */

.cards {
    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 28px;
}

.card {
    background: white;

    border: 1px solid rgba(27,42,58,0.10);

    border-radius: 8px;

    padding: 22px;

    box-shadow: 0 5px 18px rgba(27,42,58,0.06);
}

.card-title {
    color: #777;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 0.8px;

    margin-bottom: 12px;
}

.card-number {
    font-family: Georgia, serif;

    font-size: 27px;

    font-weight: bold;

    color: #1b2a3a;
}


/* =========================
   SECTION
========================= */

.section {
    background: white;

    border: 1px solid rgba(27,42,58,0.10);

    border-radius: 8px;

    padding: 24px;

    margin-bottom: 22px;

    box-shadow: 0 5px 18px rgba(27,42,58,0.06);
}

.section-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}

.section-header h2 {
    font-family: Georgia, serif;

    font-size: 21px;

    color: #1b2a3a;
}


/* =========================
   BUTTONS
========================= */

.btn {
    display: inline-block;

    padding: 10px 15px;

    border-radius: 5px;

    text-decoration: none;

    border: none;

    cursor: pointer;

    font-size: 13px;
}

.btn-primary {
    background: #1b2a3a;

    color: white;
}

.btn-primary:hover {
    background: #2c3f54;
}


/* =========================
   TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;
}

table th,
table td {
    padding: 13px;

    text-align: left;

    border-bottom: 1px solid #eee;
}

table th {
    background: #f4eee3;

    color: #1b2a3a;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}

table td {
    font-size: 14px;
}

.stock-low {
    color: #b33a3a;

    font-weight: bold;
}

.stock-good {
    color: #3f5d4a;

    font-weight: bold;
}

.no-data {
    text-align: center;

    padding: 28px;

    color: #888;
}


/* =========================
   QUICK ACTIONS
========================= */

.quick-actions {
    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .logout {
        margin-top: 20px;
    }

    .main {
        margin-left: 0;

        padding: 20px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .header {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        <h2>Bookshop Store</h2>

        <p>Manager Panel</p>

    </div>


    <ul class="menu">

        <li>
            <a href="dashboard.php" class="active">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="products.php">
                <span class="menu-icon">▣</span>
                Products
            </a>
        </li>

        <li>
            <a href="promotions.php">
                <span class="menu-icon">◆</span>
                Promotions
            </a>
        </li>

        <li>
            <a href="sales.php">
                <span class="menu-icon">▤</span>
                Sales
            </a>
        </li>

        <li>
            <a href="users.php">
                <span class="menu-icon">♙</span>
                Users
            </a>
        </li>

    </ul>


    <div class="logout">

        <a href="../logout.php">
            ⇥ Logout
        </a>

    </div>

</aside>


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <div class="header">

        <div>

            <h1>Manager Dashboard</h1>

            <p class="welcome">
                Welcome back, <?php echo htmlspecialchars($username); ?>.
            </p>

        </div>


        <div class="manager-badge">
            MANAGER
        </div>

    </div>


    <!-- STATISTICS -->

    <div class="cards">


        <div class="card">

            <div class="card-title">
                Total Products
            </div>

            <div class="card-number">
                <?php echo $totalProducts; ?>
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Total Stock
            </div>

            <div class="card-number">
                <?php echo $totalStock; ?>
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Active Promotions
            </div>

            <div class="card-number">
                <?php echo $activePromotions; ?>
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Total Sales
            </div>

            <div class="card-number">

                RM
                <?php echo number_format($totalSales, 2); ?>

            </div>

        </div>


    </div>


    <!-- RECENT PRODUCTS -->

    <div class="section">

        <div class="section-header">

            <h2>Recent Products</h2>

            <a
                href="add_product.php"
                class="btn btn-primary"
            >
                + Add Product
            </a>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Barcode</th>

                        <th>Product Name</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Stock</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if ($productsResult && $productsResult->num_rows > 0) {

                    while ($product = $productsResult->fetch_assoc()) {

                ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($product['id']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($product['barcode']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($product['category']); ?>
                        </td>

                        <td>
                            RM <?php echo number_format($product['price'], 2); ?>
                        </td>

                        <td>

                            <?php

                            if ($product['stock'] <= 5) {

                                echo '<span class="stock-low">';
                                echo htmlspecialchars($product['stock']);
                                echo '</span>';

                            } else {

                                echo '<span class="stock-good">';
                                echo htmlspecialchars($product['stock']);
                                echo '</span>';

                            }

                            ?>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td
                            colspan="6"
                            class="no-data"
                        >
                            No products available yet.
                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="section">

        <div class="section-header">

            <h2>Quick Actions</h2>

        </div>


        <div class="quick-actions">

            <a
                href="add_product.php"
                class="btn btn-primary"
            >
                + Add Product
            </a>

            <a
                href="products.php"
                class="btn btn-primary"
            >
                Manage Products
            </a>

            <a
                href="promotions.php"
                class="btn btn-primary"
            >
                Manage Promotions
            </a>

            <a
                href="sales.php"
                class="btn btn-primary"
            >
                View Sales
            </a>

        </div>

    </div>


</main>

</body>

</html>