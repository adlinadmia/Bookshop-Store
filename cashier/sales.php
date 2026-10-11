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
   CART
========================= */

if (!isset($_SESSION['cashier_cart'])) {
    $_SESSION['cashier_cart'] = [];
}


/* =========================
   ADD PRODUCT
========================= */

if (isset($_POST['add_product'])) {

    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($product_id > 0 && $quantity > 0) {

        $sql = "
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

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $product_id
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);

            $product =
                mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($product) {

                $stock = (int)$product['stock'];

                $existing_quantity =
                    $_SESSION['cashier_cart'][$product_id]['quantity']
                    ?? 0;

                $new_quantity =
                    $existing_quantity + $quantity;


                if ($new_quantity <= $stock) {

                    $_SESSION['cashier_cart'][$product_id] = [
                        'id' => (int)$product['id'],
                        'barcode' => $product['barcode'],
                        'product_name' => $product['product_name'],
                        'category' => $product['category'],
                        'price' => (float)$product['price'],
                        'quantity' => $new_quantity
                    ];

                } else {

                    $_SESSION['cashier_message'] =
                        "Not enough stock available.";
                }

            }
        }
    }

    header("Location: sales.php");
    exit();
}


/* =========================
   UPDATE QUANTITY
========================= */

if (isset($_POST['update_cart'])) {

    foreach ($_POST['quantity'] ?? [] as $product_id => $quantity) {

        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if (
            isset(
                $_SESSION['cashier_cart'][$product_id]
            )
        ) {

            $stock_sql = "
                SELECT stock
                FROM products
                WHERE id = ?
                LIMIT 1
            ";

            $stock_stmt =
                mysqli_prepare(
                    $conn,
                    $stock_sql
                );

            if ($stock_stmt) {

                mysqli_stmt_bind_param(
                    $stock_stmt,
                    "i",
                    $product_id
                );

                mysqli_stmt_execute(
                    $stock_stmt
                );

                $stock_result =
                    mysqli_stmt_get_result(
                        $stock_stmt
                    );

                $stock_data =
                    mysqli_fetch_assoc(
                        $stock_result
                    );

                mysqli_stmt_close(
                    $stock_stmt
                );


                $stock =
                    (int)($stock_data['stock'] ?? 0);


                if ($quantity <= 0) {

                    unset(
                        $_SESSION['cashier_cart'][$product_id]
                    );

                } elseif ($quantity <= $stock) {

                    $_SESSION['cashier_cart'][$product_id]['quantity'] =
                        $quantity;

                } else {

                    $_SESSION['cashier_cart'][$product_id]['quantity'] =
                        $stock;

                    $_SESSION['cashier_message'] =
                        "Quantity adjusted to available stock.";
                }
            }
        }
    }

    header("Location: sales.php");
    exit();
}


/* =========================
   REMOVE PRODUCT
========================= */

if (isset($_GET['remove'])) {

    $remove_id = (int)$_GET['remove'];

    if (
        isset(
            $_SESSION['cashier_cart'][$remove_id]
        )
    ) {

        unset(
            $_SESSION['cashier_cart'][$remove_id]
        );
    }

    header("Location: sales.php");
    exit();
}


/* =========================
   CLEAR CART
========================= */

if (isset($_GET['clear'])) {

    $_SESSION['cashier_cart'] = [];

    header("Location: sales.php");
    exit();
}


/* =========================
   SEARCH PRODUCTS
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


if ($search !== "") {

    $product_sql = "
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
        ORDER BY product_name ASC
    ";

    $stmt =
        mysqli_prepare(
            $conn,
            $product_sql
        );

    $search_value =
        "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $product_result =
        mysqli_stmt_get_result($stmt);

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
        ORDER BY product_name ASC
        LIMIT 30
    ";

    $product_result =
        mysqli_query(
            $conn,
            $product_sql
        );
}


/* =========================
   CART TOTAL
========================= */

$cart_subtotal = 0;
$cart_total = 0;
$cart_items = 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>New Sale · Cashier Panel</title>


<style>

/* =========================
   RESET
========================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


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
}


.menu a:hover {
    background: #2c3f54;
}


.menu a.active {
    background: #a9762f;
    color: white;
    font-weight: bold;
}


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
   MESSAGE
========================= */

.message {
    background: #eee5d3;

    border: 1px solid #d9caa8;

    color: #80652d;

    padding: 12px 15px;

    border-radius: 7px;

    margin-bottom: 20px;

    font-size: 13px;
}


/* =========================
   SEARCH PANEL
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
    border-color: #a9762f;
}


.search-button {
    padding: 12px 22px;

    border: none;

    border-radius: 7px;

    background: #a9762f;

    color: white;

    cursor: pointer;

    font-size: 14px;
}


.search-button:hover {
    background: #8d6224;
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


/* =========================
   CONTENT GRID
========================= */

.content-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(360px, 1fr);

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


.product-name {
    font-weight: bold;
}


.barcode {
    font-family: monospace;
    color: #56616b;
}


.price {
    font-weight: bold;
}


.stock {
    color: #3f5d4a;
}


.stock.out {
    color: #8a3324;
}


.add-button {
    display: inline-block;

    border: none;

    background: #a9762f;

    color: white;

    padding: 7px 11px;

    border-radius: 5px;

    cursor: pointer;

    font-size: 11px;
}


.add-button:hover {
    background: #8d6224;
}


.add-button:disabled {
    background: #aaa;

    cursor: not-allowed;
}


/* =========================
   CART
========================= */

.cart-panel {
    position: sticky;
    top: 25px;
}


.cart-table th {
    background: #3f2e18;
}


.cart-product {
    font-weight: bold;
}


.quantity-input {
    width: 55px;

    padding: 6px;

    border: 1px solid #d8cfbf;

    border-radius: 5px;

    text-align: center;
}


.remove-button {
    color: #8a3324;

    text-decoration: none;

    font-weight: bold;
}


.cart-summary {
    padding: 20px;

    border-top:
        1px solid #e4dccd;
}


.summary-row {
    display: flex;

    justify-content: space-between;

    margin-bottom: 10px;

    font-size: 13px;
}


.summary-total {
    display: flex;

    justify-content: space-between;

    padding-top: 12px;

    margin-top: 12px;

    border-top:
        1px solid #ddd3c2;

    font-family: Georgia, serif;

    font-size: 20px;

    font-weight: bold;
}


.summary-total span:last-child {
    color: #a9762f;
}


.cart-actions {
    display: flex;

    gap: 10px;

    margin-top: 18px;
}


.update-button {
    flex: 1;

    padding: 11px;

    border: none;

    border-radius: 7px;

    background: #1b2a3a;

    color: white;

    cursor: pointer;
}


.checkout-button {
    flex: 1;

    display: block;

    text-align: center;

    text-decoration: none;

    padding: 11px;

    border-radius: 7px;

    background: #a9762f;

    color: white;

    font-weight: bold;

    font-size: 13px;
}


.checkout-button:hover {
    background: #8d6224;
}


.clear-cart {
    display: block;

    text-align: center;

    margin-top: 12px;

    color: #8a3324;

    font-size: 12px;

    text-decoration: none;
}


.empty-cart {
    padding: 35px 20px;

    text-align: center;

    color: #888;

    font-size: 13px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .content-grid {
        grid-template-columns: 1fr;
    }

    .cart-panel {
        position: static;
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

    .search-form {
        flex-direction: column;
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

        <a href="dashboard.php">
            Dashboard
        </a>

        <a
            href="sales.php"
            class="active"
        >
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


    <div class="page-header">

        <h1>
            New Sale
        </h1>

        <p>
            Select products and prepare the customer's order.
        </p>

    </div>


    <?php if (isset($_SESSION['cashier_message'])): ?>

        <div class="message">

            <?php

            echo htmlspecialchars(
                $_SESSION['cashier_message']
            );

            unset(
                $_SESSION['cashier_message']
            );

            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="search-box">

        <form
            method="GET"
            action="sales.php"
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
                    href="sales.php"
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
             PRODUCTS
        ========================= -->

        <div class="panel">


            <div class="panel-header">

                <h2>
                    Products
                </h2>

                <p>
                    Select a product to add it to the sale.
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
                                Stock
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if (
                        $product_result &&
                        mysqli_num_rows(
                            $product_result
                        ) > 0
                    ):

                        while (
                            $product =
                            mysqli_fetch_assoc(
                                $product_result
                            )
                        ):

                            $product_stock =
                                (int)$product['stock'];

                    ?>


                        <tr>


                            <td>

                                <div class="product-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );

                                    ?>

                                </div>

                            </td>


                            <td class="barcode">

                                <?php

                                echo htmlspecialchars(
                                    $product['barcode']
                                );

                                ?>

                            </td>


                            <td class="price">

                                RM

                                <?php

                                echo number_format(
                                    (float)$product['price'],
                                    2
                                );

                                ?>

                            </td>


                            <td
                                class="<?php
                                    echo $product_stock > 0
                                        ? 'stock'
                                        : 'stock out';
                                ?>"
                            >

                                <?php
                                echo $product_stock;
                                ?>

                            </td>


                            <td>

                                <form
                                    method="POST"
                                    action="sales.php"
                                >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php
                                            echo (int)$product['id'];
                                        ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="quantity"
                                        value="1"
                                    >


                                    <button
                                        type="submit"
                                        name="add_product"
                                        class="add-button"
                                        <?php
                                        echo $product_stock <= 0
                                            ? 'disabled'
                                            : '';
                                        ?>
                                    >
                                        Add
                                    </button>

                                </form>

                            </td>


                        </tr>


                    <?php

                        endwhile;

                    else:

                    ?>


                        <tr>

                            <td
                                colspan="5"
                                class="empty-cart"
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
             CART
        ========================= -->

        <div class="panel cart-panel">


            <div class="panel-header">

                <h2>
                    Current Sale
                </h2>

                <p>
                    Review the customer's items before checkout.
                </p>

            </div>


            <?php if (!empty($_SESSION['cashier_cart'])): ?>


                <form
                    method="POST"
                    action="sales.php"
                >

                    <div class="table-wrapper">

                        <table class="cart-table">

                            <thead>

                                <tr>

                                    <th>
                                        Item
                                    </th>

                                    <th>
                                        Qty
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php

                            foreach (
                                $_SESSION['cashier_cart']
                                as $cart_id => $item
                            ):

                                $quantity =
                                    (int)$item['quantity'];

                                $price =
                                    (float)$item['price'];

                                $item_total =
                                    $price * $quantity;

                                $cart_subtotal +=
                                    $item_total;

                                $cart_items +=
                                    $quantity;

                            ?>


                                <tr>


                                    <td class="cart-product">

                                        <?php

                                        echo htmlspecialchars(
                                            $item['product_name']
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            name="quantity[<?php
                                                echo (int)$cart_id;
                                            ?>]"
                                            value="<?php
                                                echo $quantity;
                                            ?>"
                                            min="1"
                                            class="quantity-input"
                                        >

                                    </td>


                                    <td>

                                        RM

                                        <?php

                                        echo number_format(
                                            $price,
                                            2
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        RM

                                        <?php

                                        echo number_format(
                                            $item_total,
                                            2
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="sales.php?remove=<?php
                                                echo (int)$cart_id;
                                            ?>"
                                            class="remove-button"
                                        >
                                            ×
                                        </a>

                                    </td>


                                </tr>


                            <?php

                            endforeach;

                            ?>


                            </tbody>

                        </table>

                    </div>


                    <div class="cart-summary">


                        <div class="summary-row">

                            <span>
                                Items
                            </span>

                            <span>
                                <?php
                                echo $cart_items;
                                ?>
                            </span>

                        </div>


                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <span>

                                RM

                                <?php

                                echo number_format(
                                    $cart_subtotal,
                                    2
                                );

                                ?>

                            </span>

                        </div>


                        <div class="cart-actions">


                            <button
                                type="submit"
                                name="update_cart"
                                class="update-button"
                            >
                                Update
                            </button>


                            <a
                                href="checkout.php"
                                class="checkout-button"
                            >
                                Checkout
                            </a>


                        </div>


                        <a
                            href="sales.php?clear=1"
                            class="clear-cart"
                        >
                            Clear Sale
                        </a>


                    </div>

                </form>


            <?php else: ?>


                <div class="empty-cart">

                    No products added yet.

                    <br><br>

                    Search for a product and click
                    <strong>Add</strong>.

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>