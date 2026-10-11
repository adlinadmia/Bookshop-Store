<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit();
}

require_once "../db.php";


/* =========================
   GET PRODUCT ID
========================= */

$product_id = 0;

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
}

if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}


/* =========================
   GET PRODUCT
========================= */

$sql = "
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$product) {
    header("Location: products.php");
    exit();
}


/* =========================
   UPDATE PRODUCT
========================= */

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $barcode = trim(
        $_POST['barcode'] ?? ""
    );

    $product_name = trim(
        $_POST['product_name'] ?? ""
    );

    $category = trim(
        $_POST['category'] ?? ""
    );

    $price = $_POST['price'] ?? "";

    $stock = $_POST['stock'] ?? "";


    if (
        $barcode === "" ||
        $product_name === "" ||
        $category === "" ||
        $price === "" ||
        $stock === ""
    ) {

        $error = "Please fill in all fields.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } elseif (!is_numeric($stock) || $stock < 0) {

        $error = "Please enter a valid stock quantity.";

    } else {


        /* =========================
           CHECK DUPLICATE BARCODE
        ========================= */

        $check_sql = "
            SELECT id
            FROM products
            WHERE barcode = ?
            AND id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        mysqli_stmt_bind_param(
            $check_stmt,
            "si",
            $barcode,
            $product_id
        );

        mysqli_stmt_execute(
            $check_stmt
        );

        $check_result =
            mysqli_stmt_get_result(
                $check_stmt
            );


        if (
            $check_result &&
            mysqli_num_rows($check_result) > 0
        ) {

            $error =
                "Barcode already belongs to another product.";

        } else {


            /* =========================
               UPDATE
            ========================= */

            $update_sql = "
                UPDATE products
                SET
                    barcode = ?,
                    product_name = ?,
                    category = ?,
                    price = ?,
                    stock = ?
                WHERE id = ?
            ";

            $update_stmt = mysqli_prepare(
                $conn,
                $update_sql
            );

            mysqli_stmt_bind_param(
                $update_stmt,
                "sssdii",
                $barcode,
                $product_name,
                $category,
                $price,
                $stock,
                $product_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {

                mysqli_stmt_close(
                    $update_stmt
                );

                mysqli_stmt_close(
                    $check_stmt
                );

                header(
                    "Location: products.php?success=Product updated successfully"
                );

                exit();

            } else {

                $error =
                    "Unable to update product.";

                mysqli_stmt_close(
                    $update_stmt
                );
            }
        }


        mysqli_stmt_close(
            $check_stmt
        );
    }
}


/* =========================
   KEEP POST VALUES
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product['barcode'] =
        $_POST['barcode'] ?? $product['barcode'];

    $product['product_name'] =
        $_POST['product_name'] ?? $product['product_name'];

    $product['category'] =
        $_POST['category'] ?? $product['category'];

    $product['price'] =
        $_POST['price'] ?? $product['price'];

    $product['stock'] =
        $_POST['stock'] ?? $product['stock'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Product | Manager Panel</title>


<style>

/* =========================
   GENERAL
========================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family: Arial, Helvetica, sans-serif;

    background: #f4eee3;

    color: #1b2a3a;
    font-weight : normal;
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

.menu {
    list-style: none;

    padding: 0;

    margin: 0;
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
}

.logout {
    margin-top: auto;
}

.logout a {
    display: block;

    text-align: center;

    padding: 12px;

    background: #8a3324;

    color: white;

    text-decoration: none;

    border-radius: 6px;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;

    padding: 32px;
}


/* =========================
   HEADER
========================= */

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    font-family: Georgia, serif;

    font-size: 29px;

    margin: 0;
}

.page-header p {
    margin-top: 7px;

    color: #777;

    font-size: 14px;
}


/* =========================
   FORM CARD
========================= */

.form-card {
    background: white;

    border-radius: 8px;

    padding: 30px;

    max-width: 750px;

    border: 1px solid rgba(27,42,58,0.10);

    box-shadow: 0 5px 18px rgba(27,42,58,0.06);
}


/* =========================
   ERROR
========================= */

.error-message {
    background: #f5dfda;

    color: #8a3324;

    border: 1px solid #e4bdb4;

    padding: 12px 15px;

    border-radius: 6px;

    margin-bottom: 20px;

    max-width: 750px;
}


/* =========================
   FORM
========================= */

.form-group {
    margin-bottom: 19px;
}

.form-group label {
    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: normal;
}

.form-group input, .form-group select {
    width: 100%;
    box-sizing : border-box;
    padding: 12px 13px;
    border: 1px solid #d5d5d5;
    border-radius: 5px;
    font-size: 14px;
    font-family : Arial, sans-serif;
    font-weight : normal;
    background : #fff;
    color : #1b2a3a;
    outline: none;
}

.form-group input:focus, .form-group select:focus {
    border-color: #8a3324;
}

.form-group select {
    cursor : pointer;
    appearance : auto;
}

.form-actions {
    display: flex;

    gap: 10px;

    margin-top: 25px;
}

.update-btn {
    background: #8a3324;

    color: white;

    border: none;

    padding: 12px 20px;

    border-radius: 5px;

    cursor: pointer;

    font-weight: bold;
}

.update-btn:hover {
    background: #a9402f;
}

.cancel-btn {
    background: #e8e5df;

    color: #1b2a3a;

    text-decoration: none;

    padding: 12px 20px;

    border-radius: 5px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 700px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;
    }

    .logout {
        margin-top: 20px;
    }

    .main {
        margin-left: 0;

        padding: 20px;
    }

}

</style>

</head>


<body>


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">

        <h2>Bookshop Store</h2>

        <p>Manager Panel</p>

    </div>


    <ul class="menu">

        <li>
            <a href="dashboard.php">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="products.php" class="active">
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


<!-- MAIN -->

<main class="main">


    <div class="page-header">

        <h1>Edit Product</h1>

        <p>
            Update product information, price and stock.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error-message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="form-card">


        <form
            method="POST"
            action=""
        >


            <div class="form-group">

                <label for="barcode">
                    Barcode
                </label>

                <input
                    type="text"
                    id="barcode"
                    name="barcode"
                    value="<?php echo htmlspecialchars($product['barcode']); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="product_name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    value="<?php echo htmlspecialchars($product['product_name']); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="category">
                    Category
                </label>

                <select name="category" required>
                    <option value="">Select Category</option>
                    <option value="Stationery">Stationery</option>
                    <option value="Books">Books</option>
                    <option value="Writing Supplies">Writing Supplies</option>
                    <option value="Art Supplies">Art Supplies</option>
                    <option value="Office Supplies">Office Supplies</option>
                    <option value="School Supplies">School Supplies</option>
                    <option value="Printing Supplies">Printing Supplies</option>
                    <option value="Others">Others</option>
                </select>

            </div>


            <div class="form-group">

                <label for="price">
                    Price (RM)
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    step="0.01"
                    min="0"
                    value="<?php echo htmlspecialchars($product['price']); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="stock">
                    Stock
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    value="<?php echo htmlspecialchars($product['stock']); ?>"
                    required
                >

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="update-btn"
                >
                    Update Product
                </button>


                <a
                    href="products.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</main>

</body>

</html>