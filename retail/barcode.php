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
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* =========================
   SELECTED PRODUCT
========================= */

$selected_id = 0;

if (isset($_GET['id'])) {
    $selected_id = (int) $_GET['id'];
}


/* =========================
   PRODUCT LIST
========================= */

$product_result = false;

if ($search !== "") {

    $search_sql = "
        SELECT
            id,
            barcode,
            product_name,
            category,
            price,
            stock
        FROM products
        WHERE barcode LIKE ?
        OR product_name LIKE ?
        OR category LIKE ?
        ORDER BY id DESC
    ";

    $stmt = mysqli_prepare($conn, $search_sql);

    if ($stmt) {

        $search_value = "%" . $search . "%";

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $search_value,
            $search_value,
            $search_value
        );

        mysqli_stmt_execute($stmt);

        $product_result = mysqli_stmt_get_result($stmt);
    }

} else {

    $product_sql = "
        SELECT
            id,
            barcode,
            product_name,
            category,
            price,
            stock
        FROM products
        ORDER BY id DESC
    ";

    $product_result = mysqli_query(
        $conn,
        $product_sql
    );
}


/* =========================
   SELECTED PRODUCT DETAILS
========================= */

$selected_product = null;

if ($selected_id > 0) {

    $selected_sql = "
        SELECT
            id,
            barcode,
            product_name,
            category,
            price,
            stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ";

    $selected_stmt = mysqli_prepare(
        $conn,
        $selected_sql
    );

    if ($selected_stmt) {

        mysqli_stmt_bind_param(
            $selected_stmt,
            "i",
            $selected_id
        );

        mysqli_stmt_execute(
            $selected_stmt
        );

        $selected_result = mysqli_stmt_get_result(
            $selected_stmt
        );

        if (
            $selected_result &&
            mysqli_num_rows($selected_result) > 0
        ) {

            $selected_product = mysqli_fetch_assoc(
                $selected_result
            );
        }

        mysqli_stmt_close($selected_stmt);
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

<title>Barcode / Tags · Retail Staff Panel</title>


<!-- JsBarcode -->

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>


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
   CONTENT GRID
========================= */

.content-grid {
    display: grid;

    grid-template-columns: 1.2fr 1fr;

    gap: 22px;

    align-items: start;
}


/* =========================
   PANEL
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


/* =========================
   PRODUCT TABLE
========================= */

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

    padding: 12px;

    text-align: left;

    font-size: 12px;

    font-weight: normal;
}


td {
    padding: 12px;

    border-bottom:
        1px solid #eee7da;

    font-size: 12px;
}


tr:last-child td {
    border-bottom: none;
}


.barcode-text {
    font-family: monospace;
    color: #56616b;
}


.select-button {
    display: inline-block;

    padding: 7px 11px;

    background: #3f5d4a;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 11px;
}


.select-button:hover {
    background: #304b3b;
}


/* =========================
   TAG
========================= */

.tag-wrapper {
    padding: 25px;
}


.price-tag {
    background: white;

    border: 2px solid #1b2a3a;

    border-radius: 8px;

    padding: 25px;

    text-align: center;
}


.store-name {
    font-family: Georgia, serif;

    font-size: 18px;

    font-weight: bold;

    margin-bottom: 15px;
}


.tag-product {
    font-family: Georgia, serif;

    font-size: 22px;

    font-weight: bold;

    margin-bottom: 7px;
}


.tag-category {
    font-size: 12px;

    color: #777;

    margin-bottom: 15px;
}


.tag-normal-only {
    font-family: Georgia, serif;

    font-size: 30px;

    font-weight: bold;

    color: #3f5d4a;

    margin-bottom: 15px;
}


.barcode-container {
    margin-top: 15px;

    padding-top: 15px;

    border-top: 1px solid #ddd5c6;
}


.barcode-container svg {
    max-width: 100%;

    height: 80px;
}


.barcode-number {
    font-family: monospace;

    font-size: 12px;

    color: #56616b;

    margin-top: 5px;
}


/* =========================
   BUTTON
========================= */

.print-button {
    width: 100%;

    border: none;

    background: #3f5d4a;

    color: white;

    padding: 13px;

    border-radius: 7px;

    margin-top: 18px;

    cursor: pointer;

    font-size: 14px;
}


.print-button:hover {
    background: #304b3b;
}


.no-selection {
    padding: 45px 25px;

    text-align: center;

    color: #888;

    font-size: 13px;
}


.no-data {
    padding: 30px;

    text-align: center;

    color: #888;

    font-size: 13px;
}


/* =========================
   PRINT
========================= */

@media print {

    body {
        background: white;
    }

    .sidebar,
    .page-header,
    .search-box,
    .product-panel,
    .print-button {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    .content-grid {
        display: block;
    }

    .tag-panel {
        border: none;
        box-shadow: none;
    }

    .tag-wrapper {
        padding: 20px;
    }

    .price-tag {
        width: 100%;
        max-width: 500px;
        margin: auto;
    }

}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {

    .content-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 700px) {

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

        <a href="products.php">
            Products
        </a>

        <a href="promotions.php">
            Promotions
        </a>

        <a
            href="barcode.php"
            class="active"
        >
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
            Barcode / Product Tags
        </h1>

        <p>
            Select a product to view its barcode and current price.
        </p>

    </div>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="search-box">

        <form
            method="GET"
            action="barcode.php"
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
                    href="barcode.php"
                    class="clear-button"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- =========================
         CONTENT
    ========================= -->

    <div class="content-grid">


        <!-- =========================
             PRODUCT LIST
        ========================= -->

        <div class="panel product-panel">


            <div class="panel-header">

                <h2>
                    Select Product
                </h2>

                <p>
                    Choose a product to display its barcode.
                </p>

            </div>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Barcode
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Select
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if (
                        $product_result &&
                        mysqli_num_rows($product_result) > 0
                    ):

                        while (
                            $product =
                            mysqli_fetch_assoc($product_result)
                        ):

                    ?>


                        <tr>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $product['product_name']
                                );
                                ?>

                            </td>


                            <td class="barcode-text">

                                <?php
                                echo htmlspecialchars(
                                    $product['barcode']
                                );
                                ?>

                            </td>


                            <td>

                                RM

                                <?php
                                echo number_format(
                                    $product['price'],
                                    2
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="barcode.php?id=<?php
                                    echo $product['id'];
                                    ?>"
                                    class="select-button"
                                >
                                    Select
                                </a>

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


        <!-- =========================
             BARCODE TAG
        ========================= -->

        <div
            class="panel tag-panel"
        >


            <div class="panel-header">

                <h2>
                    Product Tag
                </h2>

                <p>
                    Preview the product price tag before printing.
                </p>

            </div>


            <?php if ($selected_product): ?>


                <div class="tag-wrapper">


                    <div class="price-tag">


                        <div class="store-name">

                            Bookshop Store

                        </div>


                        <div class="tag-product">

                            <?php
                            echo htmlspecialchars(
                                $selected_product['product_name']
                            );
                            ?>

                        </div>


                        <div class="tag-category">

                            <?php
                            echo htmlspecialchars(
                                $selected_product['category']
                            );
                            ?>

                        </div>


                        <div class="tag-normal-only">

                            RM

                            <?php
                            echo number_format(
                                $selected_product['price'],
                                2
                            );
                            ?>

                        </div>


                        <div class="barcode-container">

                            <svg
                                id="barcode"
                            ></svg>


                            <div class="barcode-number">

                                <?php
                                echo htmlspecialchars(
                                    $selected_product['barcode']
                                );
                                ?>

                            </div>

                        </div>


                    </div>


                    <button
                        type="button"
                        class="print-button"
                        onclick="window.print()"
                    >
                        Print Product Tag
                    </button>


                </div>


            <?php else: ?>


                <div class="no-selection">

                    Select a product from the list
                    to view its barcode and price tag.

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


<?php if ($selected_product): ?>

<script>

JsBarcode(
    "#barcode",
    "<?php
    echo htmlspecialchars(
        $selected_product['barcode'],
        ENT_QUOTES
    );
    ?>",
    {
        format: "CODE128",
        lineColor: "#1b2a3a",
        width: 2,
        height: 70,
        displayValue: false,
        margin: 5
    }
);

</script>

<?php endif; ?>


</body>

</html>