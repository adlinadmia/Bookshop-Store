<?php
session_start();
require_once "../db.php";

/* =========================
   MANAGER ACCESS
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit();
}

/* =========================
   SEARCH
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

/* =========================
   VIEW SALE DETAILS
========================= */
$view_sale = null;
$sale_items = [];

if (isset($_GET['view'])) {

    $sale_id = (int)$_GET['view'];

    /* Get sale information */
    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.total_amount,
            s.payment_method,
            s.sale_date,
            u.username AS cashier_name
        FROM sales s
        LEFT JOIN users u ON s.cashier_id = u.id
        WHERE s.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $sale_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $view_sale = $result->fetch_assoc();

    $stmt->close();

    /* Get sale items */
    if ($view_sale) {

        $stmt = $conn->prepare("
            SELECT
                si.quantity,
                si.price,
                si.subtotal,
                p.product_name,
                p.barcode
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
            ORDER BY si.id ASC
        ");

        $stmt->bind_param("i", $sale_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sale_items[] = $row;
        }

        $stmt->close();
    }
}

/* =========================
   SALES LIST
========================= */

if ($search !== "") {

    $search_like = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.total_amount,
            s.payment_method,
            s.sale_date,
            u.username AS cashier_name
        FROM sales s
        LEFT JOIN users u ON s.cashier_id = u.id
        WHERE
            CAST(s.id AS CHAR) LIKE ?
            OR u.username LIKE ?
            OR s.payment_method LIKE ?
        ORDER BY s.id DESC
    ");

    $stmt->bind_param(
        "sss",
        $search_like,
        $search_like,
        $search_like
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.total_amount,
            s.payment_method,
            s.sale_date,
            u.username AS cashier_name
        FROM sales s
        LEFT JOIN users u ON s.cashier_id = u.id
        ORDER BY s.id DESC
    ");
}

$stmt->execute();
$sales_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sales · Manager Panel</title>

<style>

/* =========================
   RESET
========================= */

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
   SIDEBAR
========================= */

.sidebar {
    width: 245px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
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
    margin: 0 0 5px;
}

.brand p {
    font-size: 12px;
    opacity: 0.65;
    margin: 0;
}

.nav-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.5;
    padding: 0 12px;
    margin-bottom: 10px;
}

.nav a {
    display: block;
    text-decoration: none;
    color: white;
    padding: 13px 12px;
    margin-bottom: 6px;
    border-radius: 7px;
    font-size: 14px;
}

.nav a:hover {
    background: #2c3f54;
}

.nav a.active {
    background: #8a3324;
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
    color: white;
    background: #8a3324;
    padding: 12px;
    border-radius: 7px;
    font-size: 14px;
}

.logout a:hover {
    opacity: 0.9;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;
    padding: 35px;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.header h1 {
    font-family: Georgia, serif;
    font-size: 30px;
    margin: 0;
}

.header p {
    margin: 7px 0 0;
    color: #6d6d6d;
    font-size: 14px;
}

/* =========================
   CARD
========================= */

.card {
    background: #fffefb;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(27, 42, 58, 0.08);
}

/* =========================
   SEARCH
========================= */

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
}

.search-form input {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid #d9d1c3;
    border-radius: 7px;
    background: white;
    font-size: 14px;
    outline: none;
}

.search-form input:focus {
    border-color: #8a3324;
}

.search-button {
    border: none;
    background: #1b2a3a;
    color: white;
    padding: 12px 20px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 14px;
}

.search-button:hover {
    background: #2c3f54;
}

.clear-button {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #666;
    padding: 0 10px;
    font-size: 14px;
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
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b6b6b;
    padding: 13px 12px;
    border-bottom: 1px solid #ddd5c8;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid #eee8dc;
    font-size: 14px;
}

td strong {
    color: #1b2a3a;
}

.sale-id {
    font-weight: bold;
    color: #8a3324;
}

.amount {
    font-weight: bold;
}

.payment {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    background: #eee8dc;
}

.view-button {
    display: inline-block;
    text-decoration: none;
    background: #1b2a3a;
    color: white;
    padding: 7px 12px;
    border-radius: 6px;
    font-size: 12px;
}

.view-button:hover {
    background: #2c3f54;
}

/* =========================
   EMPTY
========================= */

.empty {
    text-align: center;
    padding: 45px 20px;
    color: #777;
}

.empty h3 {
    font-family: Georgia, serif;
    color: #1b2a3a;
    margin-bottom: 8px;
}

/* =========================
   DETAILS
========================= */

.details-card {
    margin-top: 25px;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.details-header h2 {
    font-family: Georgia, serif;
    margin: 0;
    font-size: 22px;
}

.close-button {
    text-decoration: none;
    color: #8a3324;
    font-size: 14px;
}

.sale-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.info-box {
    background: #f4eee3;
    border-radius: 8px;
    padding: 15px;
}

.info-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #777;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.info-value {
    font-size: 14px;
    font-weight: bold;
}

.details-total {
    margin-top: 20px;
    border-top: 1px solid #ddd5c8;
    padding-top: 18px;
    display: flex;
    justify-content: flex-end;
    gap: 35px;
    font-size: 20px;
    font-weight: bold;
}

.details-total span:last-child {
    color: #8a3324;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 900px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }

    .sale-info {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 650px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .logout {
        position: static;
        margin-top: 20px;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .header {
        display: block;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        min-width: 100%;
    }

    .sale-info {
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
        <h2>Bookshop Store</h2>
        <p>Manager Panel</p>
    </div>

    <div class="nav-title">Management</div>

    <div class="nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="promotions.php">
            Promotions
        </a>

        <a href="sales.php" class="active">
            Sales
        </a>

        <a href="users.php">
            Users
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

    <div class="header">

        <div>

            <h1>Sales</h1>

            <p>
                View and monitor sales transactions made by cashiers.
            </p>

        </div>

    </div>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="card">

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search sale ID, cashier or payment method..."
                value="<?= htmlspecialchars($search) ?>"
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


        <!-- =========================
             SALES TABLE
        ========================= -->

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>Sale ID</th>

                        <th>Cashier</th>

                        <th>Date & Time</th>

                        <th>Payment</th>

                        <th>Total</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($sales_result->num_rows > 0): ?>

                    <?php while ($sale = $sales_result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <span class="sale-id">
                                    #<?= htmlspecialchars($sale['id']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($sale['cashier_name'] ?? 'Unknown') ?>
                            </td>

                            <td>
                                <?= date(
                                    "d M Y, h:i A",
                                    strtotime($sale['sale_date'])
                                ) ?>
                            </td>

                            <td>

                                <span class="payment">
                                    <?= htmlspecialchars($sale['payment_method']) ?>
                                </span>

                            </td>

                            <td>

                                <span class="amount">
                                    RM <?= number_format(
                                        (float)$sale['total_amount'],
                                        2
                                    ) ?>
                                </span>

                            </td>

                            <td>

                                <a
                                    href="sales.php?view=<?= (int)$sale['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty">

                                <h3>No Sales Found</h3>

                                <p>
                                    No sales transactions are available yet.
                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- =========================
         SALE DETAILS
    ========================= -->

    <?php if ($view_sale): ?>

        <div class="card details-card">

            <div class="details-header">

                <h2>
                    Sale #<?= htmlspecialchars($view_sale['id']) ?>
                </h2>

                <a
                    href="sales.php"
                    class="close-button"
                >
                    Close
                </a>

            </div>


            <!-- SALE INFORMATION -->

            <div class="sale-info">

                <div class="info-box">

                    <div class="info-label">
                        Cashier
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars(
                            $view_sale['cashier_name'] ?? 'Unknown'
                        ) ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="info-label">
                        Date
                    </div>

                    <div class="info-value">
                        <?= date(
                            "d M Y",
                            strtotime($view_sale['sale_date'])
                        ) ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="info-label">
                        Time
                    </div>

                    <div class="info-value">
                        <?= date(
                            "h:i A",
                            strtotime($view_sale['sale_date'])
                        ) ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="info-label">
                        Payment
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars(
                            $view_sale['payment_method']
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- SALE ITEMS -->

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Barcode</th>

                            <th>Quantity</th>

                            <th>Price</th>

                            <th>Subtotal</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($sale_items)): ?>

                        <?php foreach ($sale_items as $item): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $item['product_name'] ?? 'Unknown Product'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $item['barcode'] ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int)$item['quantity'] ?>
                                </td>

                                <td>
                                    RM <?= number_format(
                                        (float)$item['price'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    RM <?= number_format(
                                        (float)$item['subtotal'],
                                        2
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5" style="text-align:center;">
                                No item details found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <div class="details-total">

                <span>
                    Total
                </span>

                <span>
                    RM <?= number_format(
                        (float)$view_sale['total_amount'],
                        2
                    ) ?>
                </span>

            </div>

        </div>

    <?php endif; ?>

</div>


</body>

</html>