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

/* =========================
   GET USERS
========================= */
$users = [];

$userQuery = "
    SELECT id, username, role
    FROM users
    ORDER BY
        CASE
            WHEN role = 'manager' THEN 1
            WHEN role = 'retail' THEN 2
            WHEN role = 'cashier' THEN 3
            ELSE 4
        END,
        username ASC
";

$userResult = mysqli_query($conn, $userQuery);

if ($userResult) {
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
}

/* =========================
   SEARCH USERS
========================= */
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $searchUsers = [];

    $stmt = $conn->prepare("
        SELECT id, username, role
        FROM users
        WHERE username LIKE ?
           OR role LIKE ?
        ORDER BY username ASC
    ");

    $searchValue = "%" . $search . "%";
    $stmt->bind_param("ss", $searchValue, $searchValue);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $searchUsers[] = $row;
    }

    $stmt->close();

    $users = $searchUsers;
}

/* =========================
   GET ACTIVITY HISTORY
========================= */
$activities = [];

$activityQuery = "
    SELECT
        activity_logs.id,
        activity_logs.action,
        activity_logs.description,
        activity_logs.created_at,
        users.username,
        users.role
    FROM activity_logs
    INNER JOIN users
        ON activity_logs.user_id = users.id
    ORDER BY activity_logs.created_at DESC
    LIMIT 100
";

$activityResult = mysqli_query($conn, $activityQuery);

if ($activityResult) {
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $activities[] = $row;
    }
}

/* =========================
   ROLE LABEL
========================= */
function roleLabel($role)
{
    if ($role === "manager") {
        return "Manager";
    }

    if ($role === "retail") {
        return "Retail";
    }

    if ($role === "cashier") {
        return "Cashier";
    }

    return ucfirst($role);
}

/* =========================
   ACTION CLASS
========================= */
function actionClass($action)
{
    $action = strtolower($action);

    if (
        strpos($action, "delete") !== false ||
        strpos($action, "cancel") !== false
    ) {
        return "danger";
    }

    if (
        strpos($action, "update") !== false ||
        strpos($action, "edit") !== false
    ) {
        return "warning";
    }

    if (
        strpos($action, "add") !== false ||
        strpos($action, "create") !== false ||
        strpos($action, "complete") !== false
    ) {
        return "success";
    }

    return "normal";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users · Manager Panel</title>

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
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 32px;
            color: #1b2a3a;
        }

        .page-header p {
            margin: 8px 0 0;
            color: #68727c;
            font-size: 14px;
        }

        /* =========================
           CARDS
        ========================= */

        .card {
            background: #fffefa;
            border: 1px solid #ddd6c9;
            border-radius: 10px;
            margin-bottom: 28px;
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
            font-size: 13px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #8a3324;
        }

        .search-btn {
            border: none;
            background: #1b2a3a;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .search-btn:hover {
            background: #2c3f54;
        }

        .clear-btn {
            text-decoration: none;
            background: #eee9df;
            color: #1b2a3a;
            padding: 10px 14px;
            border-radius: 6px;
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
            padding: 13px 20px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #59636c;
            border-bottom: 1px solid #ddd6c9;
        }

        td {
            padding: 15px 20px;
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

        .username {
            font-weight: 600;
            color: #1b2a3a;
        }

        /* =========================
           ROLE BADGES
        ========================= */

        .role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-manager {
            background: #f1ddd8;
            color: #8a3324;
        }

        .role-retail {
            background: #e2ebe4;
            color: #3f5d4a;
        }

        .role-cashier {
            background: #f1e7d6;
            color: #8a6226;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #3f5d4a;
            font-size: 13px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: #3f5d4a;
            border-radius: 50%;
        }

        /* =========================
           ACTIVITY
        ========================= */

        .activity-action {
            font-weight: 600;
        }

        .activity-action.success {
            color: #3f5d4a;
        }

        .activity-action.warning {
            color: #8a6226;
        }

        .activity-action.danger {
            color: #8a3324;
        }

        .activity-action.normal {
            color: #1b2a3a;
        }

        .activity-description {
            color: #626b73;
            font-size: 13px;
        }

        .date-time {
            white-space: nowrap;
            color: #68727c;
            font-size: 13px;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #7a8188;
            font-size: 14px;
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
                width: calc(100% - 210px);
                padding: 25px;
            }

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

        <div class="menu-title">Management</div>

        <nav class="nav">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="promotions.php">
                Promotions
            </a>

            <a href="sales.php">
                Sales
            </a>

            <a href="users.php" class="active">
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
         MAIN CONTENT
    ========================= -->

    <main class="main">

        <div class="page-header">
            <h1>Users</h1>
            <p>View system users and monitor their activity.</p>
        </div>


        <!-- =========================
             USER ACCOUNTS
        ========================= -->

        <section class="card">

            <div class="card-header">

                <div>
                    <h2>User Accounts</h2>
                    <p>Registered users and their assigned system roles.</p>
                </div>

                <form method="GET" class="search-box">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search username or role..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <button type="submit" class="search-btn">
                        Search
                    </button>

                    <?php if ($search !== ""): ?>

                        <a href="users.php" class="clear-btn">
                            Clear
                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <span class="username">
                                        <?= htmlspecialchars($user["username"]) ?>
                                    </span>
                                </td>

                                <td>

                                    <span class="role role-<?= htmlspecialchars($user["role"]) ?>">
                                        <?= htmlspecialchars(roleLabel($user["role"])) ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="status">
                                        <span class="status-dot"></span>
                                        Active
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3" class="empty">
                                No users found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =========================
             ACTIVITY HISTORY
        ========================= -->

        <section class="card">

            <div class="card-header">

                <div>
                    <h2>Activity History</h2>
                    <p>
                        Recent activities performed by users in the system.
                    </p>
                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Activity</th>
                            <th>Details</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($activities) > 0): ?>

                        <?php foreach ($activities as $activity): ?>

                            <tr>

                                <td class="date-time">
                                    <?= date(
                                        "d M Y, h:i A",
                                        strtotime($activity["created_at"])
                                    ) ?>
                                </td>

                                <td>
                                    <span class="username">
                                        <?= htmlspecialchars($activity["username"]) ?>
                                    </span>
                                </td>

                                <td>

                                    <span class="role role-<?= htmlspecialchars($activity["role"]) ?>">
                                        <?= htmlspecialchars(roleLabel($activity["role"])) ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="activity-action <?= actionClass($activity["action"]) ?>">
                                        <?= htmlspecialchars($activity["action"]) ?>
                                    </span>

                                </td>

                                <td class="activity-description">
                                    <?= htmlspecialchars($activity["description"]) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                No activity has been recorded yet.
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