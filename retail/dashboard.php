<?php
session_start();

require_once "../db.php";

/* =========================
   RETAIL STAFF ACCESS ONLY
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "retail"
) {
    header("Location: ../login.php");
    exit();
}


/* =========================
   PRODUCT COUNT
========================= */

$productCount = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $row = $result->fetch_assoc();
    $productCount = (int) $row["total"];
}


/* =========================
   ACTIVE PROMOTIONS
========================= */

$activePromotions = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM promotions
    WHERE status = 'active'
    AND CURDATE() BETWEEN start_date AND end_date
");

if ($result) {
    $row = $result->fetch_assoc();
    $activePromotions = (int) $row["total"];
}


/* =========================
   LOW STOCK PRODUCTS
========================= */

$lowStock = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock <= 5
");

if ($result) {
    $row = $result->fetch_assoc();
    $lowStock = (int) $row["total"];
}


/* =========================
   PRODUCTS WITH PROMOTIONS
========================= */

$promotionProducts = 0;

$result = $conn->query("
    SELECT COUNT(DISTINCT product_id) AS total
    FROM promotions
    WHERE status = 'active'
    AND CURDATE() BETWEEN start_date AND end_date
");

if ($result) {
    $row = $result->fetch_assoc();
    $promotionProducts = (int) $row["total"];
}


/* =========================
   RECENT PRODUCTS
========================= */

$recentProducts = [];

$result = $conn->query("
    SELECT
        id,
        barcode,
        product_name,
        category,
        price,
        stock
    FROM products
    ORDER BY id DESC
    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $recentProducts[] = $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Retail Dashboard · Bookshop Store</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4eee3;
            color: #1b2a3a;
        }


        /* =========================
           LAYOUT
        ========================= */

        .layout {
            display: flex;
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 245px;
            background: #1b2a3a;
            color: white;
            padding: 30px 20px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .brand {
            margin-bottom: 40px;
        }

        .brand h2 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 24px;
        }

        .brand p {
            margin: 7px 0 0;
            font-size: 13px;
            color: #c8d0d8;
        }


        /* =========================
           NAVIGATION
        ========================= */

        .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9eabb8;
            margin-bottom: 12px;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .nav a {
            text-decoration: none;
            color: #e8edf1;
            padding: 12px 14px;
            border-radius: 7px;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav a:hover {
            background: #2c3f54;
        }

        .nav a.active {
            background: #3f5d4a;
            color: white;
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 25px;
        }

        .logout a {
            display: block;
            text-align: center;
            background: #8a3324;
            color: white;
            padding: 11px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
        }

        .logout a:hover {
            background: #70281d;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            padding: 35px;
        }


        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 32px;
        }

        .page-header p {
            margin: 8px 0 0;
            color: #68727c;
            font-size: 14px;
        }


        /* =========================
           STAT CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fffefa;
            border: 1px solid #ddd6c9;
            border-radius: 10px;
            padding: 22px;
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #747b82;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: Georgia, serif;
            font-size: 30px;
            color: #1b2a3a;
        }

        .stat-note {
            margin-top: 7px;
            font-size: 12px;
            color: #899099;
        }


        /* =========================
           CONTENT CARD
        ========================= */

        .card {
            background: #fffefa;
            border: 1px solid #ddd6c9;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e2ddd4;
        }

        .card-header h2 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 21px;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #747b82;
            font-size: 13px;
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

        th {
            text-align: left;
            background: #f5f1e9;
            padding: 13px 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #59636c;
            border-bottom: 1px solid #ddd6c9;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee9df;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #faf8f3;
        }


        /* =========================
           PRODUCT DETAILS
        ========================= */

        .product-name {
            font-weight: 600;
            color: #1b2a3a;
        }

        .barcode {
            font-family: monospace;
            color: #68727c;
        }

        .category {
            color: #59636c;
        }

        .price {
            white-space: nowrap;
            font-weight: 600;
        }


        /* =========================
           STOCK STATUS
        ========================= */

        .stock {
            font-weight: 600;
        }

        .stock-good {
            color: #3f5d4a;
        }

        .stock-low {
            color: #8a6226;
        }

        .stock-out {
            color: #8a3324;
        }


        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 22px;
        }

        .quick-action {
            display: block;
            text-decoration: none;
            border: 1px solid #ddd6c9;
            border-radius: 8px;
            padding: 18px;
            background: #faf8f3;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #f1ece2;
            border-color: #cfc6b7;
        }

        .quick-action h3 {
            margin: 0 0 7px;
            font-family: Georgia, serif;
            font-size: 17px;
            color: #1b2a3a;
        }

        .quick-action p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            color: #68727c;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
                padding: 25px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="layout">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="brand">

            <h2>Bookshop Store</h2>

            <p>Retail Panel</p>

        </div>


        <div class="menu-title">
            Retail
        </div>


        <nav class="nav">

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="promotions.php">
                Promotions
            </a>

            <a href="barcode.php">
                Barcode
            </a>

        </nav>


        <div class="logout">

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </aside>



    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main">


        <div class="page-header">

            <h1>Retail Dashboard</h1>

            <p>
                View products, promotions and barcode information.
            </p>

        </div>



        <!-- =========================
             STATISTICS
        ========================= -->

        <section class="stats">


            <div class="stat-card">

                <div class="stat-label">
                    Total Products
                </div>

                <div class="stat-value">
                    <?= $productCount ?>
                </div>

                <div class="stat-note">
                    Products available in store
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Active Promotions
                </div>

                <div class="stat-value">
                    <?= $activePromotions ?>
                </div>

                <div class="stat-note">
                    Promotions currently running
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Promotion Products
                </div>

                <div class="stat-value">
                    <?= $promotionProducts ?>
                </div>

                <div class="stat-note">
                    Products with active promotions
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Low Stock
                </div>

                <div class="stat-value">
                    <?= $lowStock ?>
                </div>

                <div class="stat-note">
                    Products with 5 or fewer items
                </div>

            </div>


        </section>



        <!-- =========================
             QUICK ACTIONS
        ========================= -->

        <section class="card">

            <div class="card-header">

                <h2>Quick Access</h2>

                <p>
                    Common tasks for retail staff.
                </p>

            </div>


            <div class="quick-actions">


                <a
                    href="products.php"
                    class="quick-action"
                >

                    <h3>
                        View Products
                    </h3>

                    <p>
                        Check product names, categories,
                        prices, stock and barcodes.
                    </p>

                </a>



                <a
                    href="promotions.php"
                    class="quick-action"
                >

                    <h3>
                        Check Promotions
                    </h3>

                    <p>
                        View current promotions and
                        promotion periods.
                    </p>

                </a>



                <a
                    href="barcode.php"
                    class="quick-action"
                >

                    <h3>
                        Barcode
                    </h3>

                    <p>
                        Search products and view their
                        barcode and pricing information.
                    </p>

                </a>


            </div>

        </section>



        <!-- =========================
             RECENT PRODUCTS
        ========================= -->

        <section class="card">

            <div class="card-header">

                <h2>Recent Products</h2>

                <p>
                    Recently added products in the system.
                </p>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Barcode</th>

                            <th>Category</th>

                            <th>Price</th>

                            <th>Stock</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (count($recentProducts) > 0): ?>

                        <?php foreach ($recentProducts as $product): ?>

                            <?php

                            $stock = (int) $product["stock"];

                            if ($stock <= 0) {

                                $stockClass = "stock-out";

                            } elseif ($stock <= 5) {

                                $stockClass = "stock-low";

                            } else {

                                $stockClass = "stock-good";

                            }

                            ?>

                            <tr>

                                <td>

                                    <div class="product-name">

                                        <?= htmlspecialchars(
                                            $product["product_name"]
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <span class="barcode">

                                        <?= htmlspecialchars(
                                            $product["barcode"]
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="category">

                                        <?= htmlspecialchars(
                                            $product["category"]
                                        ) ?>

                                    </span>

                                </td>


                                <td class="price">

                                    RM
                                    <?= number_format(
                                        $product["price"],
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="stock <?= $stockClass ?>"
                                    >

                                        <?= $stock ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                style="
                                    text-align:center;
                                    padding:35px;
                                    color:#7a8188;
                                "
                            >

                                No products found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


    </main>

</div>

</body>

</html>