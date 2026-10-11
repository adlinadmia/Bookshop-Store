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
   DELETE PRODUCT
========================= */

if (isset($_GET['delete'])) {

    $delete_id = (int)$_GET['delete'];

    if ($delete_id > 0) {

        $delete_sql = "DELETE FROM products WHERE id = ?";

        $delete_stmt = mysqli_prepare($conn, $delete_sql);

        if ($delete_stmt) {

            mysqli_stmt_bind_param(
                $delete_stmt,
                "i",
                $delete_id
            );

            if (mysqli_stmt_execute($delete_stmt)) {

                mysqli_stmt_close($delete_stmt);

                header(
                    "Location: products.php?success=Product deleted successfully"
                );

                exit();

            } else {

                mysqli_stmt_close($delete_stmt);

                header(
                    "Location: products.php?success=Unable to delete product"
                );

                exit();
            }
        }
    }
}


/* =========================
   SEARCH
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

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Products | Manager Panel</title>


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
}

.logout {
    margin-top: auto;
}

.logout a {
    display: flex;

    justify-content: center;

    padding: 12px;

    background: #8a3324;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-size: 14px;
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
   HEADER
========================= */

.page-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

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

.add-btn {
    background: #8a3324;

    color: white;

    text-decoration: none;

    padding: 11px 17px;

    border-radius: 6px;

    font-size: 14px;

    font-weight: bold;
}

.add-btn:hover {
    background: #a9402f;
}


/* =========================
   SUCCESS
========================= */

.success-message {
    background: #e4efe7;

    color: #3f5d4a;

    border: 1px solid #c5d9cb;

    padding: 12px 15px;

    border-radius: 6px;

    margin-bottom: 20px;
}


/* =========================
   SEARCH
========================= */

.search-box {
    background: white;

    padding: 18px;

    border-radius: 8px;

    border: 1px solid rgba(27,42,58,0.10);

    margin-bottom: 20px;
}

.search-form {
    display: flex;

    gap: 10px;
}

.search-input {
    flex: 1;

    padding: 11px 13px;

    border: 1px solid #d5d5d5;

    border-radius: 5px;

    font-size: 14px;

    outline: none;
}

.search-input:focus {
    border-color: #8a3324;
}

.search-btn {
    background: #1b2a3a;

    color: white;

    border: none;

    padding: 11px 20px;

    border-radius: 5px;

    cursor: pointer;

    font-weight: bold;
}

.search-btn:hover {
    background: #2c3f54;
}

.clear-btn {
    background: #9a9a9a;

    color: white;

    text-decoration: none;

    padding: 11px 18px;

    border-radius: 5px;
}


/* =========================
   TABLE
========================= */

.table-container {
    background: white;

    border-radius: 8px;

    border: 1px solid rgba(27,42,58,0.10);

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;
}

th {
    background: #1b2a3a;

    color: white;

    padding: 14px 12px;

    text-align: left;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}

td {
    padding: 13px 12px;

    border-bottom: 1px solid #eee;

    font-size: 14px;
}

tr:hover td {
    background: #faf8f3;
}

.price {
    color: #3f5d4a;

    font-weight: bold;
}

.stock {
    font-weight: bold;
}

.no-product {
    text-align: center;

    padding: 30px;

    color: #777;
}


/* =========================
   ACTIONS
========================= */

.action-buttons {
    display: flex;

    gap: 7px;
}

.action-btn {
    padding: 7px 11px;

    border-radius: 5px;

    color: white;

    font-size: 12px;

    text-decoration: none;

    border: none;

    cursor: pointer;
}

.edit-btn {
    background: #3f5d4a;
}

.edit-btn:hover {
    background: #314a3b;
}

.delete-btn {
    background: #8a3324;
}

.delete-btn:hover {
    background: #a9402f;
}


/* =========================
   DELETE MODAL
========================= */

.modal-overlay {
    display: none;

    position: fixed;

    inset: 0;

    background: rgba(27,42,58,0.60);

    justify-content: center;

    align-items: center;

    z-index: 9999;
}

.modal-overlay.show {
    display: flex;
}

.delete-modal {
    background: #fff;

    width: 90%;

    max-width: 420px;

    padding: 28px;

    border-radius: 8px;

    text-align: center;

    box-shadow: 0 15px 45px rgba(0,0,0,0.25);
}

.delete-icon {
    width: 55px;

    height: 55px;

    margin: 0 auto 15px;

    border-radius: 50%;

    background: #f5dfda;

    color: #8a3324;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    font-weight: bold;
}

.delete-modal h2 {
    font-family: Georgia, serif;

    color: #1b2a3a;

    margin-bottom: 10px;
}

.delete-modal p {
    color: #777;

    font-size: 14px;

    line-height: 1.5;
}

.product-delete-name {
    color: #1b2a3a;

    font-weight: bold;
}

.modal-buttons {
    display: flex;

    justify-content: center;

    gap: 10px;

    margin-top: 22px;
}

.modal-btn {
    padding: 11px 22px;

    border-radius: 5px;

    border: none;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    text-decoration: none;
}

.cancel-modal-btn {
    background: #e8e5df;

    color: #1b2a3a;
}

.confirm-delete-btn {
    background: #8a3324;

    color: white;
}

.confirm-delete-btn:hover {
    background: #a9402f;
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

    .page-header {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-btn,
    .clear-btn {
        text-align: center;

        width: 100%;
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

        <div>

            <h1>Products</h1>

            <p>
                Manage product information, prices and stock.
            </p>

        </div>


        <a
            href="add_product.php"
            class="add-btn"
        >
            + Add Product
        </a>

    </div>


    <!-- SUCCESS -->

    <?php if (isset($_GET['success'])): ?>

        <div class="success-message">

            <?php
            echo htmlspecialchars($_GET['success']);
            ?>

        </div>

    <?php endif; ?>


    <!-- SEARCH -->

    <div class="search-box">

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by barcode, product name or category..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button
                type="submit"
                class="search-btn"
            >
                Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="products.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- TABLE -->

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>No.</th>

                    <th>Barcode</th>

                    <th>Product Name</th>

                    <th>Category</th>

                    <th>Price</th>

                    <th>Stock</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php

            $counter = 1;

            if ($result && mysqli_num_rows($result) > 0):

                while ($product = mysqli_fetch_assoc($result)):

            ?>

                <tr>

                    <td>
                        <?php echo $counter++; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $product['barcode']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $product['product_name']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $product['category']
                        );
                        ?>
                    </td>

                    <td class="price">

                        RM
                        <?php
                        echo number_format(
                            $product['price'],
                            2
                        );
                        ?>

                    </td>

                    <td class="stock">

                        <?php
                        echo (int)$product['stock'];
                        ?>

                    </td>

                    <td>

                        <div class="action-buttons">

                            <a
                                href="edit_product.php?id=<?php echo (int)$product['id']; ?>"
                                class="action-btn edit-btn"
                            >
                                Edit
                            </a>


                            <button
                                type="button"
                                class="action-btn delete-btn"
                                onclick='openDeleteModal(
                                    <?php echo (int)$product["id"]; ?>,
                                    <?php echo json_encode($product["product_name"]); ?>
                                )'
                            >
                                Delete
                            </button>

                        </div>

                    </td>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td
                        colspan="7"
                        class="no-product"
                    >

                        <?php if ($search !== ""): ?>

                            No product found for
                            "<strong><?php echo htmlspecialchars($search); ?></strong>".

                        <?php else: ?>

                            No products available.

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


</main>


<!-- DELETE MODAL -->

<div
    class="modal-overlay"
    id="deleteModal"
>

    <div class="delete-modal">

        <div class="delete-icon">
            !
        </div>


        <h2>
            Delete Product?
        </h2>


        <p>

            Are you sure you want to delete

            <span
                class="product-delete-name"
                id="deleteProductName"
            ></span>?

        </p>


        <p>
            This action cannot be undone.
        </p>


        <div class="modal-buttons">

            <button
                type="button"
                class="modal-btn cancel-modal-btn"
                onclick="closeDeleteModal()"
            >
                Cancel
            </button>


            <a
                href="#"
                id="confirmDeleteBtn"
                class="modal-btn confirm-delete-btn"
            >
                Delete
            </a>

        </div>

    </div>

</div>


<script>

function openDeleteModal(id, productName) {

    document.getElementById(
        "deleteProductName"
    ).textContent = productName;


    document.getElementById(
        "confirmDeleteBtn"
    ).href = "products.php?delete=" + id;


    document.getElementById(
        "deleteModal"
    ).classList.add("show");
}


function closeDeleteModal() {

    document.getElementById(
        "deleteModal"
    ).classList.remove("show");
}


document.getElementById(
    "deleteModal"
).addEventListener(
    "click",
    function(event) {

        if (event.target === this) {

            closeDeleteModal();

        }

    }
);


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeDeleteModal();

        }

    }
);

</script>


</body>

</html>