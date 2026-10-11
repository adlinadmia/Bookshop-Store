<?php
session_start();
require_once "../db.php";

/* =========================
   MANAGER ACCESS ONLY
========================= */
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "manager") {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];

/* =========================
   ACTIVITY LOG FUNCTION
========================= */
function logActivity($conn, $userId, $action, $description)
{
    $stmt = $conn->prepare("
        INSERT INTO activity_logs
        (user_id, action, description)
        VALUES (?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "iss",
            $userId,
            $action,
            $description
        );

        $stmt->execute();
        $stmt->close();
    }
}

/* =========================
   DELETE PROMOTION
========================= */
if (isset($_GET["delete"])) {

    $deleteId = (int) $_GET["delete"];

    /* Get promotion information first */
    $stmt = $conn->prepare("
        SELECT
            promotions.promotion_name,
            products.product_name
        FROM promotions
        INNER JOIN products
            ON promotions.product_id = products.id
        WHERE promotions.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $deleteId);
    $stmt->execute();

    $result = $stmt->get_result();
    $promotion = $result->fetch_assoc();

    $stmt->close();

    if ($promotion) {

        $stmt = $conn->prepare("
            DELETE FROM promotions
            WHERE id = ?
        ");

        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {

            $description =
                "Deleted promotion \"" .
                $promotion["promotion_name"] .
                "\" for " .
                $promotion["product_name"];

            logActivity(
                $conn,
                $userId,
                "Delete Promotion",
                $description
            );
        }

        $stmt->close();
    }

    header("Location: promotions.php");
    exit();
}


/* =========================
   ADD / EDIT PROMOTION
========================= */
$error = "";
$success = "";

/* =========================
   EDIT DATA
========================= */
$editPromotion = null;

if (isset($_GET["edit"])) {

    $editId = (int) $_GET["edit"];

    $stmt = $conn->prepare("
        SELECT
            id,
            product_id,
            promotion_name,
            discount_type,
            discount_value,
            start_date,
            end_date,
            status
        FROM promotions
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $editId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $editPromotion = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================
   FORM SUBMISSION
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $promotionId = (int) ($_POST["promotion_id"] ?? 0);

    $productId = (int) ($_POST["product_id"] ?? 0);

    $promotionName = trim(
        $_POST["promotion_name"] ?? ""
    );

    $discountType = trim(
        $_POST["discount_type"] ?? ""
    );

    $discountValue = (float) (
        $_POST["discount_value"] ?? 0
    );

    $startDate = $_POST["start_date"] ?? "";

    $endDate = $_POST["end_date"] ?? "";

    /* =========================
       VALIDATION
    ========================= */

    if (
        $productId <= 0 ||
        $promotionName === "" ||
        $discountType === "" ||
        $discountValue <= 0 ||
        $startDate === "" ||
        $endDate === ""
    ) {

        $error = "Please complete all promotion fields.";

    } elseif ($endDate < $startDate) {

        $error = "End date cannot be earlier than start date.";

    } elseif (
        $discountType === "percentage"
        && $discountValue > 100
    ) {

        $error = "Percentage discount cannot be more than 100%.";

    } elseif (
        !in_array(
            $discountType,
            ["percentage", "fixed"],
            true
        )
    ) {

        $error = "Invalid discount type.";

    } else {

        /* =========================
           ADD PROMOTION
        ========================= */

        if ($promotionId === 0) {

            /*
             * New promotions start as active.
             * Actual display status is calculated
             * from the promotion dates below.
             */

            $status = "active";

            $stmt = $conn->prepare("
                INSERT INTO promotions
                (
                    product_id,
                    promotion_name,
                    discount_type,
                    discount_value,
                    start_date,
                    end_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issdsss",
                $productId,
                $promotionName,
                $discountType,
                $discountValue,
                $startDate,
                $endDate,
                $status
            );

            if ($stmt->execute()) {

                /* Get product name */
                $productStmt = $conn->prepare("
                    SELECT product_name
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");

                $productStmt->bind_param(
                    "i",
                    $productId
                );

                $productStmt->execute();

                $productResult =
                    $productStmt->get_result();

                $product = $productResult->fetch_assoc();

                $productStmt->close();

                $productName =
                    $product["product_name"]
                    ?? "Unknown Product";

                if ($discountType === "percentage") {

                    $discountText =
                        number_format(
                            $discountValue,
                            2
                        ) . "%";

                } else {

                    $discountText =
                        "RM " .
                        number_format(
                            $discountValue,
                            2
                        );
                }

                $description =
                    "Added promotion \"" .
                    $promotionName .
                    "\" for " .
                    $productName .
                    " (" .
                    $discountText .
                    ")";

                logActivity(
                    $conn,
                    $userId,
                    "Add Promotion",
                    $description
                );

                $success =
                    "Promotion added successfully.";

            } else {

                $error =
                    "Unable to add promotion. Please try again.";
            }

            $stmt->close();

        }

        /* =========================
           EDIT PROMOTION
        ========================= */

        else {

            /* Get old information */
            $oldStmt = $conn->prepare("
                SELECT
                    promotions.promotion_name,
                    products.product_name
                FROM promotions
                INNER JOIN products
                    ON promotions.product_id = products.id
                WHERE promotions.id = ?
                LIMIT 1
            ");

            $oldStmt->bind_param(
                "i",
                $promotionId
            );

            $oldStmt->execute();

            $oldResult =
                $oldStmt->get_result();

            $oldPromotion =
                $oldResult->fetch_assoc();

            $oldStmt->close();


            $status = "active";

            $stmt = $conn->prepare("
                UPDATE promotions
                SET
                    product_id = ?,
                    promotion_name = ?,
                    discount_type = ?,
                    discount_value = ?,
                    start_date = ?,
                    end_date = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "issdsssi",
                $productId,
                $promotionName,
                $discountType,
                $discountValue,
                $startDate,
                $endDate,
                $status,
                $promotionId
            );

            if ($stmt->execute()) {

                $description =
                    "Updated promotion \"" .
                    $promotionName .
                    "\"";

                if ($oldPromotion) {

                    $description .=
                        " for " .
                        $oldPromotion["product_name"];
                }

                logActivity(
                    $conn,
                    $userId,
                    "Update Promotion",
                    $description
                );

                $success =
                    "Promotion updated successfully.";

            } else {

                $error =
                    "Unable to update promotion. Please try again.";
            }

            $stmt->close();
        }
    }

    /*
     * If form was submitted successfully,
     * remove edit mode from URL.
     */
    if ($success !== "") {
        $editPromotion = null;
    }
}


/* =========================
   GET PRODUCTS
========================= */
$products = [];

$productResult = $conn->query("
    SELECT
        id,
        product_name,
        price,
        stock
    FROM products
    ORDER BY product_name ASC
");

if ($productResult) {

    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}


/* =========================
   SEARCH PROMOTIONS
========================= */
$search = trim(
    $_GET["search"] ?? ""
);

$promotions = [];

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT
            promotions.id,
            promotions.product_id,
            promotions.promotion_name,
            promotions.discount_type,
            promotions.discount_value,
            promotions.start_date,
            promotions.end_date,
            promotions.status,
            products.product_name,
            products.price
        FROM promotions
        INNER JOIN products
            ON promotions.product_id = products.id
        WHERE
            promotions.promotion_name LIKE ?
            OR products.product_name LIKE ?
        ORDER BY promotions.start_date DESC,
                 promotions.id DESC
    ");

    $searchValue =
        "%" . $search . "%";

    $stmt->bind_param(
        "ss",
        $searchValue,
        $searchValue
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }

    $stmt->close();

} else {

    $result = $conn->query("
        SELECT
            promotions.id,
            promotions.product_id,
            promotions.promotion_name,
            promotions.discount_type,
            promotions.discount_value,
            promotions.start_date,
            promotions.end_date,
            promotions.status,
            products.product_name,
            products.price
        FROM promotions
        INNER JOIN products
            ON promotions.product_id = products.id
        ORDER BY promotions.start_date DESC,
                 promotions.id DESC
    ");

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $promotions[] = $row;
        }
    }
}


/* =========================
   DISPLAY STATUS
========================= */
function getPromotionStatus(
    $status,
    $startDate,
    $endDate
) {

    if ($status !== "active") {
        return "Inactive";
    }

    $today = date("Y-m-d");

    if ($today < $startDate) {
        return "Scheduled";
    }

    if ($today > $endDate) {
        return "Expired";
    }

    return "Active";
}


/* =========================
   STATUS CLASS
========================= */
function getStatusClass($displayStatus)
{
    if ($displayStatus === "Active") {
        return "status-active";
    }

    if ($displayStatus === "Scheduled") {
        return "status-scheduled";
    }

    if ($displayStatus === "Expired") {
        return "status-expired";
    }

    return "status-inactive";
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

    <title>Promotions · Manager Panel</title>

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
            background: #8a3324;
            color: white;
        }

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

        .page-header {
            margin-bottom: 25px;
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
           ALERT
        ========================= */

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #f4dddd;
            color: #8a3324;
            border: 1px solid #e2bcbc;
        }

        .alert-success {
            background: #e4eee6;
            color: #3f5d4a;
            border: 1px solid #c7dacb;
        }

        /* =========================
           CARD
        ========================= */

        .card {
            background: #fffefa;
            border: 1px solid #ddd6c9;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e2ddd4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
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
           BUTTONS
        ========================= */

        .btn {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #8a3324;
            color: white;
        }

        .btn-primary:hover {
            background: #70281d;
        }

        .btn-secondary {
            background: #eee9df;
            color: #1b2a3a;
        }

        .btn-secondary:hover {
            background: #e2dbcf;
        }

        .btn-edit {
            background: #1b2a3a;
            color: white;
        }

        .btn-edit:hover {
            background: #2c3f54;
        }

        .btn-delete {
            background: #f0dddd;
            color: #8a3324;
        }

        .btn-delete:hover {
            background: #e6caca;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-box {
            display: flex;
            gap: 8px;
        }

        .search-box input {
            width: 230px;
            padding: 10px 12px;
            border: 1px solid #d3ccc0;
            border-radius: 6px;
            outline: none;
            font-size: 13px;
        }

        .search-box input:focus {
            border-color: #8a3324;
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

        .promotion-name {
            font-weight: 600;
            color: #1b2a3a;
        }

        .product-name {
            color: #4d5963;
        }

        .price {
            white-space: nowrap;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #e2ebe4;
            color: #3f5d4a;
        }

        .status-scheduled {
            background: #eee6d7;
            color: #8a6226;
        }

        .status-expired {
            background: #eee2df;
            color: #8a3324;
        }

        .status-inactive {
            background: #e7e7e7;
            color: #666;
        }

        .discount {
            font-weight: 600;
            color: #8a3324;
        }

        .date {
            white-space: nowrap;
            color: #68727c;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 7px;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #7a8188;
        }

        /* =========================
           FORM
        ========================= */

        .form-card {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #384550;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d3ccc0;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            color: #1b2a3a;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #8a3324;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 9px;
        }

        .form-note {
            margin-top: 6px;
            color: #7b838a;
            font-size: 12px;
        }

        /* =========================
           MODAL
        ========================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(27, 42, 58, 0.45);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 1000;
        }

        .modal {
            width: 100%;
            max-width: 620px;
            background: #fffefa;
            border-radius: 10px;
            border: 1px solid #ddd6c9;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e2ddd4;
        }

        .modal-header h2 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 22px;
        }

        .modal-body {
            padding: 22px;
        }

        /* =========================
           DELETE MODAL
        ========================= */

        .delete-modal {
            max-width: 430px;
        }

        .delete-modal .modal-body {
            color: #59636c;
            line-height: 1.6;
        }

        .delete-modal strong {
            color: #1b2a3a;
        }

        .delete-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 20px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                flex: 1;
                width: auto;
            }

        }

        @media (max-width: 750px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
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

            <p>Manager Panel</p>

        </div>

        <div class="menu-title">
            Management
        </div>

        <nav class="nav">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="promotions.php" class="active">
                Promotions
            </a>

            <a href="sales.php">
                Sales
            </a>

            <a href="users.php">
                Users
            </a>

        </nav>

        <div class="logout">

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="main">

        <div class="page-header">

            <h1>Promotions</h1>

            <p>
                Manage product promotions, discounts and promotion periods.
            </p>

        </div>


        <!-- =========================
             ALERTS
        ========================= -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <!-- =========================
             ADD / EDIT FORM
        ========================= -->

        <?php if ($editPromotion !== null || isset($_GET["add"])): ?>

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2>
                            <?= $editPromotion
                                ? "Edit Promotion"
                                : "Add Promotion" ?>
                        </h2>

                        <p>
                            <?= $editPromotion
                                ? "Update the promotion information below."
                                : "Create a new promotion for a product." ?>
                        </p>

                    </div>

                </div>


                <div class="form-card">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="promotion_id"
                            value="<?= $editPromotion
                                ? (int) $editPromotion["id"]
                                : 0 ?>"
                        >

                        <div class="form-grid">

                            <!-- PRODUCT -->

                            <div class="form-group full">

                                <label for="product_id">
                                    Product
                                </label>

                                <select
                                    id="product_id"
                                    name="product_id"
                                    required
                                >

                                    <option value="">
                                        Select product
                                    </option>

                                    <?php foreach ($products as $product): ?>

                                        <option
                                            value="<?= (int) $product["id"] ?>"
                                            <?= (
                                                $editPromotion
                                                && $editPromotion["product_id"]
                                                == $product["id"]
                                            )
                                                ? "selected"
                                                : "" ?>
                                        >

                                            <?= htmlspecialchars(
                                                $product["product_name"]
                                            ) ?>

                                            —
                                            RM <?= number_format(
                                                $product["price"],
                                                2
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- PROMOTION NAME -->

                            <div class="form-group full">

                                <label for="promotion_name">
                                    Promotion Name
                                </label>

                                <input
                                    type="text"
                                    id="promotion_name"
                                    name="promotion_name"
                                    placeholder="Example: Back to School Sale"
                                    value="<?= $editPromotion
                                        ? htmlspecialchars(
                                            $editPromotion["promotion_name"]
                                        )
                                        : "" ?>"
                                    required
                                >

                            </div>


                            <!-- DISCOUNT TYPE -->

                            <div class="form-group">

                                <label for="discount_type">
                                    Discount Type
                                </label>

                                <select
                                    id="discount_type"
                                    name="discount_type"
                                    required
                                >

                                    <option value="">
                                        Select discount type
                                    </option>

                                    <option
                                        value="percentage"
                                        <?= (
                                            $editPromotion
                                            && $editPromotion["discount_type"]
                                            === "percentage"
                                        )
                                            ? "selected"
                                            : "" ?>
                                    >
                                        Percentage (%)
                                    </option>

                                    <option
                                        value="fixed"
                                        <?= (
                                            $editPromotion
                                            && $editPromotion["discount_type"]
                                            === "fixed"
                                        )
                                            ? "selected"
                                            : "" ?>
                                    >
                                        Fixed Amount (RM)
                                    </option>

                                </select>

                            </div>


                            <!-- DISCOUNT VALUE -->

                            <div class="form-group">

                                <label for="discount_value">
                                    Discount Value
                                </label>

                                <input
                                    type="number"
                                    id="discount_value"
                                    name="discount_value"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="Example: 10"
                                    value="<?= $editPromotion
                                        ? htmlspecialchars(
                                            $editPromotion["discount_value"]
                                        )
                                        : "" ?>"
                                    required
                                >

                            </div>


                            <!-- START DATE -->

                            <div class="form-group">

                                <label for="start_date">
                                    Start Date
                                </label>

                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    value="<?= $editPromotion
                                        ? htmlspecialchars(
                                            $editPromotion["start_date"]
                                        )
                                        : "" ?>"
                                    required
                                >

                            </div>


                            <!-- END DATE -->

                            <div class="form-group">

                                <label for="end_date">
                                    End Date
                                </label>

                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    value="<?= $editPromotion
                                        ? htmlspecialchars(
                                            $editPromotion["end_date"]
                                        )
                                        : "" ?>"
                                    required
                                >

                            </div>

                        </div>


                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <?= $editPromotion
                                    ? "Update Promotion"
                                    : "Add Promotion" ?>

                            </button>

                            <a
                                href="promotions.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </section>

        <?php else: ?>

            <!-- =========================
                 PROMOTION LIST HEADER
            ========================= -->

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2>Promotion List</h2>

                        <p>
                            Current and previous product promotions.
                        </p>

                    </div>


                    <div style="display:flex; gap:8px; align-items:center;">

                        <form
                            method="GET"
                            class="search-box"
                        >

                            <input
                                type="text"
                                name="search"
                                placeholder="Search promotion or product..."
                                value="<?= htmlspecialchars($search) ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-secondary"
                            >
                                Search
                            </button>

                        </form>


                        <?php if ($search !== ""): ?>

                            <a
                                href="promotions.php"
                                class="btn btn-secondary"
                            >
                                Clear
                            </a>

                        <?php endif; ?>


                        <a
                            href="promotions.php?add=1"
                            class="btn btn-primary"
                        >
                            + Add Promotion
                        </a>

                    </div>

                </div>


                <!-- =========================
                     PROMOTION TABLE
                ========================= -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Promotion</th>

                                <th>Product</th>

                                <th>Discount</th>

                                <th>Start Date</th>

                                <th>End Date</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (count($promotions) > 0): ?>

                            <?php foreach ($promotions as $promotion): ?>

                                <?php

                                $displayStatus =
                                    getPromotionStatus(
                                        $promotion["status"],
                                        $promotion["start_date"],
                                        $promotion["end_date"]
                                    );

                                $statusClass =
                                    getStatusClass(
                                        $displayStatus
                                    );

                                ?>

                                <tr>

                                    <!-- PROMOTION -->

                                    <td>

                                        <div class="promotion-name">

                                            <?= htmlspecialchars(
                                                $promotion["promotion_name"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-name">

                                            <?= htmlspecialchars(
                                                $promotion["product_name"]
                                            ) ?>

                                        </div>

                                        <div
                                            style="
                                                font-size:12px;
                                                color:#899099;
                                                margin-top:3px;
                                            "
                                        >

                                            RM <?= number_format(
                                                $promotion["price"],
                                                2
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- DISCOUNT -->

                                    <td>

                                        <span class="discount">

                                            <?php if (
                                                $promotion["discount_type"]
                                                === "percentage"
                                            ): ?>

                                                <?= number_format(
                                                    $promotion["discount_value"],
                                                    2
                                                ) ?>%

                                            <?php else: ?>

                                                RM <?= number_format(
                                                    $promotion["discount_value"],
                                                    2
                                                ) ?>

                                            <?php endif; ?>

                                        </span>

                                    </td>


                                    <!-- START DATE -->

                                    <td class="date">

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $promotion["start_date"]
                                            )
                                        ) ?>

                                    </td>


                                    <!-- END DATE -->

                                    <td class="date">

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $promotion["end_date"]
                                            )
                                        ) ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status <?= $statusClass ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $displayStatus
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div class="actions">

                                            <a
                                                href="promotions.php?edit=<?= (int) $promotion["id"] ?>"
                                                class="btn btn-edit"
                                            >
                                                Edit
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-delete"
                                                onclick="openDeleteModal(
                                                    <?= (int) $promotion["id"] ?>,
                                                    '<?= htmlspecialchars(
                                                        $promotion["promotion_name"],
                                                        ENT_QUOTES
                                                    ) ?>'
                                                )"
                                            >
                                                Delete
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty"
                                >
                                    No promotions found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>

    </main>

</div>


<!-- =========================
     DELETE MODAL
========================= -->

<div
    class="modal-overlay"
    id="deleteModal"
    style="display:none;"
>

    <div class="modal delete-modal">

        <div class="modal-header">

            <h2>
                Delete Promotion
            </h2>

        </div>


        <div class="modal-body">

            <p>
                Are you sure you want to delete
                <strong id="deletePromotionName"></strong>?
            </p>

            <p>
                This action cannot be undone.
            </p>


            <div class="delete-actions">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeDeleteModal()"
                >
                    Cancel
                </button>

                <a
                    href="#"
                    id="confirmDelete"
                    class="btn btn-delete"
                >
                    Delete
                </a>

            </div>

        </div>

    </div>

</div>


<script>

function openDeleteModal(id, name)
{
    document.getElementById(
        "deletePromotionName"
    ).textContent = name;

    document.getElementById(
        "confirmDelete"
    ).href = "promotions.php?delete=" + id;

    document.getElementById(
        "deleteModal"
    ).style.display = "flex";
}


function closeDeleteModal()
{
    document.getElementById(
        "deleteModal"
    ).style.display = "none";
}


/* Close when clicking outside modal */

document.getElementById(
    "deleteModal"
).addEventListener(
    "click",
    function(event)
    {
        if (event.target === this) {
            closeDeleteModal();
        }
    }
);


/* =========================
   DATE VALIDATION
========================= */

const startDate =
    document.getElementById("start_date");

const endDate =
    document.getElementById("end_date");

if (startDate && endDate) {

    startDate.addEventListener(
        "change",
        function()
        {
            endDate.min = this.value;

            if (
                endDate.value !== ""
                && endDate.value < this.value
            ) {
                endDate.value = this.value;
            }
        }
    );

}

</script>

</body>

</html>