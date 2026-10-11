<?php
session_start();

/* =========================
   ACCESS CONTROL
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'cashier') {
    header("Location: ../login.php");
    exit();
}

require_once "../db.php";


/* =========================
   DASHBOARD DATA
========================= */

/* Total products */
$product_sql = "SELECT COUNT(*) AS total FROM products";
$product_result = mysqli_query($conn, $product_sql);
$product_data = mysqli_fetch_assoc($product_result);
$total_products = (int)$product_data['total'];


/* Active promotions */
$promotion_sql = "
    SELECT COUNT(*) AS total
    FROM promotions
    WHERE status = 'active'
    AND CURDATE() BETWEEN start_date AND end_date
";

$promotion_result = mysqli_query($conn, $promotion_sql);
$promotion_data = mysqli_fetch_assoc($promotion_result);
$active_promotions = (int)$promotion_data['total'];


/* Today's sales */
$today_sales_sql = "
    SELECT
        COUNT(*) AS total_transactions,
        COALESCE(SUM(total_amount), 0) AS total_sales
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
";

$today_sales_result = mysqli_query($conn, $today_sales_sql);

if ($today_sales_result) {

    $today_sales_data =
        mysqli_fetch_assoc($today_sales_result);

    $today_transactions =
        (int)$today_sales_data['total_transactions'];

    $today_sales =
        (float)$today_sales_data['total_sales'];

} else {

    $today_transactions = 0;
    $today_sales = 0;
}


/* Recent sales */
$recent_sales_sql = "
    SELECT
        id,
        total_amount,
        payment_method,
        sale_date
    FROM sales
    ORDER BY id DESC
    LIMIT 8
";

$recent_sales_result =
    mysqli_query($conn, $recent_sales_sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Cashier Dashboard · Bookshop Store</title>


<style>

/* =========================
   RESET
========================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================
   BODY
========================= */

body {
    font-family: Arial, sans-serif;
    background: #f4eee3;
    color: #1b2a3a;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;

    top: 0;
    left: 0;

    width: 245px;
    height: 100vh;

    background: #1b2a3a;

    color: white;

    padding: 28px 18px;
}


.brand {
    padding: 0 12px;

    margin-bottom: 35px;
}


.brand h2 {
    font-family: Georgia, serif;

    font-size: 23px;

    margin-bottom: 5px;
}


.brand p {
    font-size: 13px;

    color: #d8dee4;
}


/* =========================
   MENU
========================= */

.menu-title {
    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;

    color: #9da9b4;

    padding: 0 12px;

    margin-bottom: 10px;
}


.menu a {
    display: block;

    text-decoration: none;

    color: #e8edf1;

    padding: 13px 12px;

    border-radius: 7px;

    margin-bottom: 5px;

    font-size: 14px;

    transition: 0.2s;
}


.menu a:hover {
    background: #2c3f54;
}


.menu a.active {
    background: #a9762f;

    color: white;

    font-weight: bold;
}


/* =========================
   LOGOUT
========================= */

.logout {
    position: absolute;

    bottom: 25px;

    left: 18px;

    right: 18px;
}


.logout a {
    display: block;

    text-align: center;

    text-decoration: none;

    background: #8a3324;

    color: white;

    padding: 12px;

    border-radius: 7px;

    font-size: 14px;
}


.logout a:hover {
    background: #70291e;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;

    padding: 35px 40px;
}


/* =========================
   HEADER
========================= */

.page-header {
    margin-bottom: 28px;
}


.page-header h1 {
    font-family: Georgia, serif;

    font-size: 29px;

    margin-bottom: 7px;
}


.page-header p {
    color: #68727d;

    font-size: 14px;
}


/* =========================
   STAT CARDS
========================= */

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}


.stat-card {
    background: #fffefb;

    border: 1px solid #e2d9c8;

    border-radius: 10px;

    padding: 20px;

    box-shadow:
        0 2px 8px
        rgba(27, 42, 58, 0.05);
}


.stat-label {
    color: #777f86;

    font-size: 12px;

    margin-bottom: 9px;
}


.stat-value {
    font-family: Georgia, serif;

    font-size: 25px;

    color: #1b2a3a;
}


.stat-card.sales {
    border-top: 4px solid #a9762f;
}


.stat-card.transaction {
    border-top: 4px solid #3f5d4a;
}


.stat-card.product {
    border-top: 4px solid #1b2a3a;
}


.stat-card.promotion {
    border-top: 4px solid #8a3324;
}


/* =========================
   QUICK ACTION
========================= */

.quick-action {
    background: #fffefb;

    border: 1px solid #e2d9c8;

    border-radius: 10px;

    padding: 22px;

    margin-bottom: 25px;

    box-shadow:
        0 2px 8px
        rgba(27, 42, 58, 0.05);
}


.quick-action h2 {
    font-family: Georgia, serif;

    font-size: 19px;

    margin-bottom: 6px;
}


.quick-action p {
    color: #777f86;

    font-size: 13px;

    margin-bottom: 17px;
}


.sale-button {
    display: inline-block;

    text-decoration: none;

    background: #a9762f;

    color: white;

    padding: 11px 18px;

    border-radius: 7px;

    font-size: 13px;

    font-weight: bold;
}


.sale-button:hover {
    background: #8d6224;
}


/* =========================
   RECENT SALES PANEL
========================= */

.panel {
    background: #fffefb;

    border: 1px solid #e2d9c8;

    border-radius: 10px;

    overflow: hidden;

    box-shadow:
        0 2px 8px
        rgba(27, 42, 58, 0.05);
}


.panel-header {
    padding: 18px 20px;

    border-bottom:
        1px solid #e4dccd;
}


.panel-header h2 {
    font-family: Georgia, serif;

    font-size: 19px;

    margin-bottom: 4px;
}


.panel-header p {
    font-size: 12px;

    color: #777f86;
}


.table-wrapper {
    overflow-x: auto;
}


table {
    width: 100%;

    border-collapse: collapse;
}


th {
    background: #1b2a3a;

    color: white;

    padding: 13px 14px;

    text-align: left;

    font-size: 12px;

    font-weight: normal;
}


td {
    padding: 13px 14px;

    border-bottom:
        1px solid #eee7da;

    font-size: 12px;
}


tr:last-child td {
    border-bottom: none;
}


.amount {
    font-weight: bold;

    color: #a9762f;
}


.payment {
    text-transform: capitalize;
}


.no-data {
    padding: 30px;

    text-align: center;

    color: #888;

    font-size: 13px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 900px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;

        padding: 25px 20px;
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


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">


    <div class="brand">

        <h2>
            Bookshop Store
        </h2>

        <p>
            Cashier Panel
        </p>

    </div>


    <div class="menu-title">
        Menu
    </div>


    <div class="menu">

        <a
            href="dashboard.php"
            class="active"
        >
            Dashboard
        </a>

        <a href="sales.php">
            New Sale
        </a>

    </div>


    <div class="logout">

        <a href="../logout.php">
            Logout
        </a>

    </div>


</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- HEADER -->

    <div class="page-header">

        <h1>
            Cashier Dashboard
        </h1>

        <p>
            Manage sales and process customer payments.
        </p>

    </div>


    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


        <div class="stat-card sales">

            <div class="stat-label">
                Today's Sales
            </div>

            <div class="stat-value">
                RM <?php echo number_format($today_sales, 2); ?>
            </div>

        </div>


        <div class="stat-card transaction">

            <div class="stat-label">
                Today's Transactions
            </div>

            <div class="stat-value">
                <?php echo $today_transactions; ?>
            </div>

        </div>


        <div class="stat-card product">

            <div class="stat-label">
                Products
            </div>

            <div class="stat-value">
                <?php echo $total_products; ?>
            </div>

        </div>


        <div class="stat-card promotion">

            <div class="stat-label">
                Active Promotions
            </div>

            <div class="stat-value">
                <?php echo $active_promotions; ?>
            </div>

        </div>


    </div>


    <!-- =========================
         QUICK ACTION
    ========================= -->

    <div class="quick-action">

        <h2>
            Ready for a new sale?
        </h2>

        <p>
            Start a new transaction and process the customer's payment.
        </p>

        <a
            href="sales.php"
            class="sale-button"
        >
            + New Sale
        </a>

    </div>


    <!-- =========================
         RECENT SALES
    ========================= -->

    <div class="panel">


        <div class="panel-header">

            <h2>
                Recent Sales
            </h2>

            <p>
                Latest transactions recorded in the system.
            </p>

        </div>


        <div class="table-wrapper">


            <table>

                <thead>

                    <tr>

                        <th>
                            Sale ID
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment Method
                        </th>

                        <th>
                            Date
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (
                    $recent_sales_result &&
                    mysqli_num_rows($recent_sales_result) > 0
                ):

                    while (
                        $sale =
                        mysqli_fetch_assoc(
                            $recent_sales_result
                        )
                    ):

                ?>


                    <tr>

                        <td>
                            #<?php
                            echo (int)$sale['id'];
                            ?>
                        </td>


                        <td class="amount">

                            RM

                            <?php

                            echo number_format(
                                (float)$sale['total_amount'],
                                2
                            );

                            ?>

                        </td>


                        <td class="payment">

                            <?php

                            echo htmlspecialchars(
                                $sale['payment_method']
                                ?? '-'
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            if (!empty($sale['sale_date'])) {

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $sale['sale_date']
                                    )
                                );

                            } else {

                                echo "-";
                            }

                            ?>

                        </td>

                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="4"
                            class="no-data"
                        >

                            No sales recorded yet.

                        </td>

                    </tr>


                <?php

                endif;

                ?>


                </tbody>

            </table>


        </div>

    </div>


</div>


</body>

</html>