<?php
session_start();
require_once "../db.php";

/* =========================
   CASHIER ACCESS
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../login.php");
    exit();
}

/* =========================
   CART CHECK
========================= */
if (!isset($_SESSION['cashier_cart']) || empty($_SESSION['cashier_cart'])) {
    header("Location: sales.php");
    exit();
}

$cart = $_SESSION['cashier_cart'];
$error = "";
$success = false;
$sale_id = null;
$receipt_items = [];
$receipt_total = 0;
$receipt_payment = "";

/* =========================
   FUNCTION:
   CALCULATE PROMOTION PRICE
========================= */
function getPromoPrice($conn, $product)
{
    $product_id = (int)$product['id'];
    $normal_price = (float)$product['price'];

    $stmt = $conn->prepare("
        SELECT discount_type, discount_value
        FROM promotions
        WHERE product_id = ?
        AND status = 'active'
        AND CURDATE() BETWEEN start_date AND end_date
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $promo = $result->fetch_assoc();

    $stmt->close();

    if (!$promo) {
        return $normal_price;
    }

    $discount_type = $promo['discount_type'];
    $discount_value = (float)$promo['discount_value'];

    if ($discount_type === 'percentage') {
        $promo_price = $normal_price - ($normal_price * $discount_value / 100);
    } else {
        $promo_price = $normal_price - $discount_value;
    }

    return max(0, $promo_price);
}

/* =========================
   LOAD CART PRODUCTS
========================= */
$checkout_items = [];
$total = 0;

foreach ($cart as $product_id => $quantity) {

    $product_id = (int)$product_id;
    $quantity = (int)$quantity;

    if ($quantity <= 0) {
        continue;
    }

    $stmt = $conn->prepare("
        SELECT id, barcode, product_name, category, price, stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $stmt->close();

    if (!$product) {
        continue;
    }

    $promo_price = getPromoPrice($conn, $product);
    $subtotal = $promo_price * $quantity;

    $checkout_items[] = [
        'id' => $product['id'],
        'barcode' => $product['barcode'],
        'product_name' => $product['product_name'],
        'category' => $product['category'],
        'normal_price' => (float)$product['price'],
        'price' => $promo_price,
        'stock' => (int)$product['stock'],
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];

    $total += $subtotal;
}

/* =========================
   PROCESS PAYMENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {

    $payment_method = trim($_POST['payment_method']);

    $allowed_methods = ['Cash', 'Card', 'Online Payment'];

    if (!in_array($payment_method, $allowed_methods, true)) {
        $error = "Please select a valid payment method.";
    } elseif (empty($checkout_items)) {
        $error = "Your cart is empty.";
    } else {

        /*
         * Re-check stock before completing payment.
         */
        foreach ($checkout_items as $item) {

            if ($item['quantity'] > $item['stock']) {
                $error = "Not enough stock for: " . $item['product_name'];
                break;
            }
        }

        if ($error === "") {

            /*
             * Start database transaction.
             */
            $conn->begin_transaction();

            try {

                /* =========================
                   INSERT SALE
                ========================= */
                $cashier_id = (int)$_SESSION['user_id'];

                $stmt = $conn->prepare("
                    INSERT INTO sales
                    (cashier_id, total_amount, payment_method, sale_date)
                    VALUES (?, ?, ?, NOW())
                ");

                $stmt->bind_param(
                    "ids",
                    $cashier_id,
                    $total,
                    $payment_method
                );

                if (!$stmt->execute()) {
                    throw new Exception("Unable to save sale.");
                }

                $sale_id = $conn->insert_id;

                $stmt->close();

                /* =========================
                   INSERT SALE ITEMS
                ========================= */
                foreach ($checkout_items as $item) {

                    $product_id = (int)$item['id'];
                    $quantity = (int)$item['quantity'];
                    $price = (float)$item['price'];
                    $subtotal = (float)$item['subtotal'];

                    $stmt = $conn->prepare("
                        INSERT INTO sale_items
                        (sale_id, product_id, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "iiidd",
                        $sale_id,
                        $product_id,
                        $quantity,
                        $price,
                        $subtotal
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Unable to save sale items.");
                    }

                    $stmt->close();

                    /* =========================
                       REDUCE STOCK
                    ========================= */
                    $stmt = $conn->prepare("
                        UPDATE products
                        SET stock = stock - ?
                        WHERE id = ?
                        AND stock >= ?
                    ");

                    $stmt->bind_param(
                        "iii",
                        $quantity,
                        $product_id,
                        $quantity
                    );

                    if (!$stmt->execute() || $stmt->affected_rows === 0) {
                        throw new Exception(
                            "Unable to update stock for " . $item['product_name']
                        );
                    }

                    $stmt->close();
                }

                /* =========================
                   COMMIT TRANSACTION
                ========================= */
                $conn->commit();

                /* =========================
                   SAVE RECEIPT DATA
                ========================= */
                $receipt_items = $checkout_items;
                $receipt_total = $total;
                $receipt_payment = $payment_method;

                /* =========================
                   CLEAR CART
                ========================= */
                unset($_SESSION['cashier_cart']);

                $success = true;

            } catch (Exception $e) {

                $conn->rollback();

                $sale_id = null;
                $error = $e->getMessage();
            }
        }
    }
}

/* =========================
   DATE / TIME
========================= */
$current_date = date("d/m/Y");
$current_time = date("h:i A");

?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Checkout · Cashier Panel</title>

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
    color: #fff;
    padding: 13px 12px;
    margin-bottom: 6px;
    border-radius: 7px;
    font-size: 14px;
}

.nav a:hover {
    background: #2c3f54;
}

.nav a.active {
    background: #A9762F;
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
   LAYOUT
========================= */

.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 25px;
    align-items: start;
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

.card h2 {
    font-family: Georgia, serif;
    margin: 0 0 20px;
    font-size: 21px;
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
    padding: 12px;
    border-bottom: 1px solid #e5dfd2;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid #eee8dc;
    font-size: 14px;
}

.product-name {
    font-weight: bold;
}

.small {
    font-size: 12px;
    color: #777;
    margin-top: 4px;
}

.promo-price {
    color: #3f5d4a;
    font-weight: bold;
}

.normal-price {
    color: #999;
    text-decoration: line-through;
    font-size: 12px;
    margin-right: 5px;
}

/* =========================
   SUMMARY
========================= */

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    font-size: 14px;
}

.summary-row.total {
    border-top: 1px solid #ddd4c4;
    margin-top: 10px;
    padding-top: 18px;
    font-size: 21px;
    font-weight: bold;
}

.total-price {
    color: #A9762F;
}

/* =========================
   PAYMENT
========================= */

.payment-title {
    font-family: Georgia, serif;
    font-size: 20px;
    margin-bottom: 18px;
}

.payment-option {
    display: block;
    border: 1px solid #ddd5c8;
    border-radius: 9px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    background: #fffefb;
}

.payment-option:hover {
    border-color: #A9762F;
    background: #faf6ed;
}

.payment-option input {
    margin-right: 10px;
}

.payment-name {
    font-weight: bold;
}

.payment-description {
    font-size: 12px;
    color: #777;
    margin-left: 25px;
    margin-top: 4px;
}

.pay-button {
    width: 100%;
    border: none;
    background: #A9762F;
    color: white;
    padding: 15px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
}

.pay-button:hover {
    opacity: 0.9;
}

.back-button {
    display: block;
    text-align: center;
    text-decoration: none;
    color: #1b2a3a;
    margin-top: 15px;
    font-size: 14px;
}

/* =========================
   ERROR
========================= */

.error {
    background: #f8dfda;
    color: #8a3324;
    border: 1px solid #e8b8af;
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

/* =========================
   SUCCESS
========================= */

.success-box {
    max-width: 800px;
    margin: 0 auto;
}

.success-icon {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: #3f5d4a;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 15px;
}

.success-title {
    text-align: center;
    font-family: Georgia, serif;
    font-size: 27px;
    margin-bottom: 5px;
}

.success-message {
    text-align: center;
    color: #666;
    margin-bottom: 25px;
}

.receipt {
    background: white;
    border: 1px solid #ddd5c8;
    padding: 30px;
    max-width: 600px;
    margin: auto;
}

.receipt-header {
    text-align: center;
    border-bottom: 1px dashed #aaa;
    padding-bottom: 20px;
    margin-bottom: 15px;
}

.receipt-header h2 {
    font-family: Georgia, serif;
    margin: 0 0 5px;
}

.receipt-header p {
    margin: 4px 0;
    font-size: 13px;
    color: #666;
}

.receipt-info {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 20px;
}

.receipt table td {
    padding: 10px 5px;
}

.receipt table th {
    padding: 10px 5px;
}

.receipt-total {
    border-top: 1px dashed #aaa;
    margin-top: 10px;
    padding-top: 15px;
}

.receipt-total-row {
    display: flex;
    justify-content: space-between;
    font-size: 20px;
    font-weight: bold;
}

.receipt-footer {
    text-align: center;
    border-top: 1px dashed #aaa;
    margin-top: 20px;
    padding-top: 15px;
    font-size: 12px;
    color: #777;
}

.receipt-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.print-button,
.new-sale-button {
    border: none;
    padding: 12px 20px;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.print-button {
    background: #A9762F;
    color: white;
}

.new-sale-button {
    background: #1b2a3a;
    color: white;
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

    .checkout-layout {
        grid-template-columns: 1fr;
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
}

/* =========================
   PRINT
========================= */

@media print {

    body {
        background: white;
    }

    .sidebar,
    .header,
    .receipt-actions {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    .success-box {
        max-width: 100%;
    }

    .receipt {
        border: none;
        box-shadow: none;
        max-width: 100%;
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
        <p>Cashier Panel</p>
    </div>

    <div class="nav-title">Menu</div>

    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="sales.php" class="active">New Sale</a>
    </div>

    <div class="logout">
        <a href="../logout.php">Logout</a>
    </div>

</div>

<!-- =========================
     MAIN
========================= -->

<div class="main">

<?php if ($success): ?>

    <!-- =========================
         PAYMENT SUCCESS
    ========================= -->

    <div class="success-box">

        <div class="card">

            <div class="success-icon">
                ✓
            </div>

            <div class="success-title">
                Payment Successful
            </div>

            <div class="success-message">
                Sale #<?= htmlspecialchars($sale_id) ?> has been completed successfully.
            </div>

            <!-- =========================
                 RECEIPT
            ========================= -->

            <div class="receipt" id="receipt">

                <div class="receipt-header">

                    <h2>Bookshop Store</h2>

                    <p>Sales Receipt</p>

                    <p>Thank you for your purchase!</p>

                </div>

                <div class="receipt-info">

                    <div>
                        <strong>Sale ID:</strong>
                        #<?= htmlspecialchars($sale_id) ?>
                    </div>

                    <div>
                        <strong>Date:</strong>
                        <?= $current_date ?>
                    </div>

                    <div>
                        <strong>Time:</strong>
                        <?= $current_time ?>
                    </div>

                </div>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($receipt_items as $item): ?>

                            <tr>

                                <td>
                                    <div class="product-name">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </div>

                                    <?php if ($item['normal_price'] != $item['price']): ?>

                                        <div class="small">
                                            Promotion applied
                                        </div>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $item['quantity'] ?>
                                </td>

                                <td>
                                    RM <?= number_format($item['price'], 2) ?>
                                </td>

                                <td>
                                    RM <?= number_format($item['subtotal'], 2) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="receipt-total">

                    <div class="receipt-total-row">

                        <span>Total</span>

                        <span>
                            RM <?= number_format($receipt_total, 2) ?>
                        </span>

                    </div>

                    <div class="summary-row">

                        <span>Payment Method</span>

                        <span>
                            <?= htmlspecialchars($receipt_payment) ?>
                        </span>

                    </div>

                </div>

                <div class="receipt-footer">

                    <p>Bookshop Store Price & Promotion System</p>
                    <p>Please keep this receipt for your reference.</p>

                </div>

            </div>

            <div class="receipt-actions">

                <button
                    type="button"
                    class="print-button"
                    onclick="window.print()"
                >
                    🖨 Print Receipt
                </button>

                <a
                    href="sales.php"
                    class="new-sale-button"
                >
                    New Sale
                </a>

            </div>

        </div>

    </div>

<?php else: ?>

    <!-- =========================
         CHECKOUT PAGE
    ========================= -->

    <div class="header">

        <div>
            <h1>Checkout</h1>
            <p>Review your order and complete the payment.</p>
        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="checkout-layout">

        <!-- =========================
             ORDER ITEMS
        ========================= -->

        <div class="card">

            <h2>Order Summary</h2>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($checkout_items)): ?>

                        <tr>
                            <td colspan="4" style="text-align:center;">
                                Your cart is empty.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($checkout_items as $item): ?>

                            <tr>

                                <td>

                                    <div class="product-name">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </div>

                                    <div class="small">
                                        <?= htmlspecialchars($item['barcode']) ?>
                                    </div>

                                </td>

                                <td>

                                    <?php if ($item['normal_price'] != $item['price']): ?>

                                        <span class="normal-price">
                                            RM <?= number_format($item['normal_price'], 2) ?>
                                        </span>

                                    <?php endif; ?>

                                    <span class="promo-price">
                                        RM <?= number_format($item['price'], 2) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= $item['quantity'] ?>
                                </td>

                                <td>
                                    <strong>
                                        RM <?= number_format($item['subtotal'], 2) ?>
                                    </strong>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- =========================
             PAYMENT
        ========================= -->

        <div class="card">

            <div class="payment-title">
                Payment
            </div>

            <div class="summary-row">

                <span>Total Items</span>

                <span>
                    <?= count($checkout_items) ?>
                </span>

            </div>

            <div class="summary-row total">

                <span>Total</span>

                <span class="total-price">
                    RM <?= number_format($total, 2) ?>
                </span>

            </div>

            <br>

            <form method="POST">

                <label class="payment-option">

                    <input
                        type="radio"
                        name="payment_method"
                        value="Cash"
                        required
                    >

                    <span class="payment-name">
                        Cash
                    </span>

                    <div class="payment-description">
                        Customer pays using cash.
                    </div>

                </label>

                <label class="payment-option">

                    <input
                        type="radio"
                        name="payment_method"
                        value="Card"
                    >

                    <span class="payment-name">
                        Card
                    </span>

                    <div class="payment-description">
                        Simulated card payment.
                    </div>

                </label>

                <label class="payment-option">

                    <input
                        type="radio"
                        name="payment_method"
                        value="Online Payment"
                    >

                    <span class="payment-name">
                        Online Payment
                    </span>

                    <div class="payment-description">
                        Demo payment — no real payment gateway required.
                    </div>

                </label>

                <button
                    type="submit"
                    class="pay-button"
                    <?= empty($checkout_items) ? 'disabled' : '' ?>
                >
                    Pay Now · RM <?= number_format($total, 2) ?>
                </button>

            </form>

            <a href="sales.php" class="back-button">
                ← Back to New Sale
            </a>

        </div>

    </div>

<?php endif; ?>

</div>

</body>
</html>