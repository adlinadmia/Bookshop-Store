<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'retail') {
    header("Location: ../login.php");
    exit();
}

require_once "../db.php";


/* =========================
   SEARCH PRODUCTS
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


if ($search !== "") {

    $search_sql = "
        SELECT *
        FROM products
        WHERE barcode LIKE ?
        OR product_name LIKE ?
        OR category LIKE ?
        ORDER BY id DESC
    ";

    $stmt = mysqli_prepare($conn, $search_sql);

    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $sql = "
        SELECT *
        FROM products
        ORDER BY id DESC
    ";

    $result = mysqli_query($conn, $sql);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Products · Retail Staff Panel</title>


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
    background: #3f5d4a;
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


.page-header {
    margin-bottom: 25px;
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
   SEARCH
========================= */

.search-box {
    background: #fffefb;

    border: 1px solid #e2d9c8;

    border-radius: 10px;

    padding: 18px;

    margin-bottom: 22px;
}


.search-form {
    display: flex;
    gap: 10px;
}


.search-input {
    flex: 1;

    padding: 12px 14px;

    border: 1px solid #d8cfbf;

    border-radius: 7px;

    background: white;

    font-size: 14px;

    outline: none;
}


.search-input:focus {
    border-color: #3f5d4a;
}


.search-button {
    padding: 12px 22px;

    border: none;

    border-radius: 7px;

    background: #3f5d4a;

    color: white;

    cursor: pointer;

    font-size: 14px;
}


.search-button:hover {
    background: #304b3b;
}


.clear-button {
    display: inline-block;

    padding: 12px 18px;

    border-radius: 7px;

    background: #e7dfd0;

    color: #1b2a3a;

    text-decoration: none;

    font-size: 14px;
}


.clear-button:hover {
    background: #dcd2c0;
}


/* =========================
   TABLE PANEL
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


.barcode {
    font-family: monospace;

    color: #56616b;
}


.price {
    color: #3f5d4a;

    font-weight: bold;
}


.stock {
    font-weight: bold;
}


.stock.low {
    color: #8a3324;
}


.stock.good {
    color: #3f5d4a;
}


.view-button {
    display: inline-block;

    padding: 7px 11px;

    background: #3f5d4a;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 11px;
}


.view-button:hover {
    background: #304b3b;
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

@media (max-width: 800px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 25px 20px;
    }

    .search-form {
        flex-wrap: wrap;
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
            Retail Staff Panel
        </p>

    </div>


    <div class="menu-title">
        Menu
    </div>


    <div class="menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php" class="active">
            Products
        </a>

        <a href="promotions.php">
            Promotions
        </a>

        <a href="barcode.php">
            Barcode / Tags
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


    <div class="page-header">

        <h1>
            Products
        </h1>

        <p>
            View product information and current stock.
        </p>

    </div>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="search-box">

        <form
            method="GET"
            action="products.php"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search barcode, product name or category..."
                value="<?php echo htmlspecialchars($search); ?>"
            >


            <button
                type="submit"
                class="search-button"
            >
                Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="products.php"
                    class="clear-button"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- =========================
         PRODUCT TABLE
    ========================= -->

    <div class="panel">


        <div class="panel-header">

            <h2>
                Product List
            </h2>

            <p>
                Products currently recorded in the system.
            </p>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            No.
                        </th>

                        <th>
                            Barcode
                        </th>

                        <th>
                            Product Name
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Barcode
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $number = 1;

                if (
                    $result &&
                    mysqli_num_rows($result) > 0
                ):

                    while (
                        $row =
                        mysqli_fetch_assoc($result)
                    ):

                        $stock_class =
                            ($row['stock'] <= 5)
                            ? 'low'
                            : 'good';

                ?>


                    <tr>


                        <td>
                            <?php
                            echo $number++;
                            ?>
                        </td>


                        <td class="barcode">

                            <?php
                            echo htmlspecialchars(
                                $row['barcode']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['product_name']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['category']
                            );
                            ?>

                        </td>


                        <td class="price">

                            RM

                            <?php
                            echo number_format(
                                $row['price'],
                                2
                            );
                            ?>

                        </td>


                        <td
                            class="stock
                            <?php
                            echo $stock_class;
                            ?>"
                        >

                            <?php
                            echo $row['stock'];
                            ?>

                        </td>


                        <td>

                            <a
                                href="barcode.php?id=<?php echo $row['id']; ?>"
                                class="view-button"
                            >
                                View
                            </a>

                        </td>


                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="7"
                            class="no-data"
                        >

                            No products found.

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